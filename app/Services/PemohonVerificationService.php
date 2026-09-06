<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Filament\Resources\Pemohons\PemohonResource;
use App\Models\Pemohon;
use App\Models\PemohonVerificationHistory;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PemohonVerificationService
{
    /** @var array<int, string> */
    private const SNAPSHOT_FIELDS = [
        'jenis_pemohon', 'nama', 'nik', 'nib', 'npwp', 'nomor_telepon',
        'email', 'alamat', 'kelurahan', 'kecamatan', 'kota',
    ];

    public function submit(Pemohon $pemohon, array $data, string $aksi = 'diajukan'): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole(UserRole::PEMOHON), 403);

        $validated = $this->validateData($data, $pemohon);
        $oldStatus = $pemohon->status_verifikasi;
        $isNew = blank($pemohon->nomor_antrian);
        $versiData = max(1, (int) ($pemohon->versi_data ?? 1));

        DB::transaction(function () use ($pemohon, $validated, $oldStatus, $isNew, $aksi, $user, $versiData): void {
            if ($isNew) {
                $pemohon->user_id = $user->id;
                $pemohon->nomor_antrian = $this->nextQueueNumber();
            }

            if (! $isNew && $oldStatus === 'terverifikasi') {
                $versiData++;
            }

            $pemohon->versi_data = $versiData;
            $pemohon->fill($validated);
            $pemohon->email = $user->email;
            $pemohon->status_verifikasi = 'menunggu_verifikasi';
            $pemohon->diajukan_pada = now();
            $pemohon->diverifikasi_oleh = null;
            $pemohon->diverifikasi_pada = null;
            $pemohon->save();

            $user->update(['name' => $pemohon->nama]);

            $this->history($pemohon, $aksi, $oldStatus, 'menunggu_verifikasi', $pemohon->alasan_perubahan);
        });

        $this->notifyReviewers($pemohon);
        Notification::make()
            ->success()
            ->title('Data pemohon berhasil dikirim')
            ->body("Nomor antrian {$pemohon->nomor_antrian}. Data Anda sedang menunggu verifikasi petugas.")
            ->send();
    }

    public function requestChange(Pemohon $pemohon, string $reason): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole(UserRole::PEMOHON), 403);
        abort_unless($pemohon->user_id === $user->id && $pemohon->isTerverifikasi(), 403);

        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('Alasan perubahan wajib diisi.');
        }

        $oldStatus = $pemohon->status_verifikasi;
        $pemohon->update([
            'status_verifikasi' => 'perlu_perubahan',
            'alasan_perubahan' => $reason,
            'diverifikasi_oleh' => null,
            'diverifikasi_pada' => null,
            'diajukan_pada' => now(),
        ]);

        $this->history($pemohon, 'permintaan_perubahan', $oldStatus, 'perlu_perubahan', $reason);

        $this->notifyReviewers($pemohon, 'Permintaan perubahan data pemohon', "{$pemohon->nama} mengajukan perubahan data. Alasan: {$reason}", 'warning');
    }

    public function approve(Pemohon $pemohon): void
    {
        $this->assertReviewer();
        $oldStatus = $pemohon->status_verifikasi;

        $pemohon->update([
            'status_verifikasi' => 'terverifikasi',
            'diverifikasi_oleh' => auth()->id(),
            'diverifikasi_pada' => now(),
        ]);

        $this->history($pemohon, 'disetujui', $oldStatus, 'terverifikasi', null);

        $this->notifyPemohon($pemohon, 'Data pemohon terverifikasi', 'Data pemohon Anda telah diverifikasi. Anda sekarang dapat mengajukan permohonan IPPT.', 'success');
        Notification::make()->success()->title('Data pemohon terverifikasi')->body("{$pemohon->nama} dapat mengajukan IPPT.")->send();
    }

    public function reject(Pemohon $pemohon, string $reason): void
    {
        $this->assertReviewer();
        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('Alasan perbaikan wajib diisi.');
        }

        $oldStatus = $pemohon->status_verifikasi;
        $pemohon->update([
            'status_verifikasi' => 'perlu_perbaikan',
            'alasan_perubahan' => $reason,
            'diverifikasi_oleh' => auth()->id(),
            'diverifikasi_pada' => now(),
        ]);

        $this->history($pemohon, 'ditolak', $oldStatus, 'perlu_perbaikan', $reason);
        $this->notifyPemohon($pemohon, 'Data pemohon perlu diperbaiki', "Petugas meminta perbaikan: {$reason}", 'warning');
        Notification::make()->warning()->title('Perbaikan diminta')->body('Alasan sudah dikirim kepada pemohon.')->send();
    }

    private function assertReviewer(): void
    {
        abort_unless(auth()->user()?->hasAnyRole([UserRole::ADMIN, UserRole::STAFF]), 403);
    }

    /** @return array<string, mixed> */
    private function validateData(array $data, ?Pemohon $record = null): array
    {
        $validated = validator($data, [
            'jenis_pemohon' => ['required', Rule::in(['perorangan', 'badan'])],
            'nama' => ['required', 'string', 'max:150'],
            'nik' => ['nullable', 'digits:16', Rule::unique('pemohon', 'nik')->ignore($record?->id)],
            'nib' => ['nullable', 'digits:13', Rule::unique('pemohon', 'nib')->ignore($record?->id)],
            'npwp' => ['nullable', 'string', 'max:16'],
            'nomor_telepon' => ['required', 'string', 'max:16'],
            'alamat' => ['required', 'string', 'max:255'],
            'kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:100'],
        ])->validate();

        if ($validated['jenis_pemohon'] === 'perorangan') {
            validator($validated, ['nik' => ['required', 'digits:16']])->validate();
            $validated['nib'] = null;
        } else {
            validator($validated, ['nib' => ['required', 'digits:13']])->validate();
            $validated['nik'] = null;
        }

        return $validated;
    }

    private function nextQueueNumber(): string
    {
        $prefix = 'PMH-' . now()->format('Y') . '-';
        $last = Pemohon::query()
            ->where('nomor_antrian', 'like', $prefix . '%')
            ->orderByDesc('nomor_antrian')
            ->value('nomor_antrian');
        $sequence = $last ? ((int) substr($last, -5)) + 1 : 1;

        return $prefix . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    private function history(Pemohon $pemohon, string $aksi, ?string $oldStatus, string $newStatus, ?string $note): void
    {
        PemohonVerificationHistory::create([
            'pemohon_id' => $pemohon->id,
            'versi_data' => max(1, (int) ($pemohon->versi_data ?? 1)),
            'aksi' => $aksi,
            'status_sebelumnya' => $oldStatus,
            'status_sesudahnya' => $newStatus,
            'catatan' => $note,
            'snapshot' => $pemohon->only(self::SNAPSHOT_FIELDS),
            'dilakukan_oleh' => auth()->id(),
            'dilakukan_pada' => now(),
        ]);
    }

    private function notifyReviewers(Pemohon $pemohon, string $title = 'Data pemohon baru menunggu verifikasi', ?string $body = null, string $level = 'info'): void
    {
        $body ??= "{$pemohon->nama} mengirim data pemohon dan membutuhkan pemeriksaan.";
        $url = PemohonResource::getUrl('edit', ['record' => $pemohon]);
        $users = \App\Models\User::query()->whereIn('role', [UserRole::ADMIN->value, UserRole::STAFF->value])->get();

        foreach ($users as $user) {
            $notification = Notification::make()->title($title)->body($body)->actions([
                Action::make('review')->label('Tinjau Data Pemohon')->url($url),
            ]);
            match ($level) {
                'success' => $notification->success(),
                'warning' => $notification->warning(),
                'danger' => $notification->danger(),
                default => $notification->info(),
            };
            $notification->sendToDatabase($user, isEventDispatched: true);
        }
    }

    private function notifyPemohon(Pemohon $pemohon, string $title, string $body, string $level): void
    {
        $user = $pemohon->loadMissing('user')->user;
        if (! $user) return;
        $notification = Notification::make()->title($title)->body($body)->actions([
            Action::make('open')->label('Buka Dasbor')->url(\App\Filament\Pages\Dashboard::getUrl()),
        ]);
        match ($level) {
            'success' => $notification->success(),
            'warning' => $notification->warning(),
            'danger' => $notification->danger(),
            default => $notification->info(),
        };
        $notification->sendToDatabase($user, isEventDispatched: true);
    }
}
