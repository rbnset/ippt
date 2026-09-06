<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Services\PemohonVerificationService;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class Dashboard extends BaseDashboard
{
    /** @var view-string */
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Dasbor';
    protected static ?string $navigationLabel = 'Dasbor';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = -2;

    /** @var array<string, mixed> */
    public array $data = [];

    public string $alasanPerubahan = '';

    public function mount(): void
    {
        if (! auth()->user()?->hasRole(UserRole::PEMOHON)) return;
        $record = $this->getPemohonRecord();
        $this->data = $record ? $this->recordToData($record) : $this->emptyData();
    }

    public function content(Schema $schema): Schema
    {
        return auth()->user()?->hasRole(UserRole::PEMOHON) ? $schema : parent::content($schema);
    }

    public function savePemohon(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole(UserRole::PEMOHON), 403);
        $record = $this->getPemohonRecord();

        if ($record?->isTerverifikasi()) {
            Notification::make()->info()->title('Data terkunci')->body('Ajukan perubahan data terlebih dahulu jika ada informasi yang perlu diperbarui.')->send();
            return;
        }

        // Record yang baru dibuat bisa sudah ada di database dengan status_verifikasi NULL
        // (mis. hasil seed / data lama). Status NULL tetap diperlakukan sebagai
        // "belum pernah diajukan", sehingga pemohon boleh mengisi dan mengirimnya.
        if ($record && filled($record->status_verifikasi) && ! $record->isEditableByPemohon()) {
            Notification::make()
                ->warning()
                ->title('Data sedang diproses')
                ->body('Formulir tidak dapat diubah selama data sedang menunggu atau sudah selesai diverifikasi.')
                ->send();
            return;
        }

        $this->resetValidation();

        app(PemohonVerificationService::class)->submit(
            $record ?? new Pemohon(),
            $this->data,
            $record ? 'dikirim_ulang' : 'diajukan',
        );

        // Ambil ulang dari database agar state Livewire langsung berpindah ke
        // tampilan "Sedang ditinjau" pada response yang sama.
        $freshRecord = $this->getPemohonRecord()?->fresh();
        if (! $freshRecord) {
            throw new \RuntimeException('Data pemohon berhasil diproses tetapi tidak dapat dimuat kembali.');
        }

        $this->data = $this->recordToData($freshRecord);
        $this->dispatch('pemohon-submitted');
    }

    public function requestChange(): void
    {
        $record = $this->getPemohonRecord();
        abort_unless($record && $record->isTerverifikasi(), 403);
        $reason = trim($this->alasanPerubahan);
        if ($reason === '') {
            $this->addError('alasanPerubahan', 'Alasan perubahan wajib diisi.');
            return;
        }

        app(PemohonVerificationService::class)->requestChange($record, $reason);
        $this->alasanPerubahan = '';
        $this->data = $this->recordToData($record->fresh());
        Notification::make()->success()->title('Permintaan perubahan dicatat')->body('Form data pemohon sekarang terbuka. Setelah dikirim, data akan diverifikasi ulang.')->send();
    }

    public function getPemohonRecord(): ?Pemohon
    {
        return auth()->user()?->pemohon()->first();
    }

    /** @return array<string, int> */
    public function getPermohonanStats(): array
    {
        $record = $this->getPemohonRecord();
        if (! $record) return ['total' => 0, 'diproses' => 0, 'selesai' => 0, 'ditolak' => 0];
        $query = Permohonan::query()->where('pemohon_id', $record->id);
        return [
            'total' => (clone $query)->count(),
            'diproses' => (clone $query)->whereNotIn('status', ['diterbitkan', 'ditolak'])->count(),
            'selesai' => (clone $query)->where('status', 'diterbitkan')->count(),
            'ditolak' => (clone $query)->where('status', 'ditolak')->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function emptyData(): array
    {
        return ['jenis_pemohon'=>'perorangan','nama'=>auth()->user()?->name,'nik'=>null,'nib'=>null,'npwp'=>null,'nomor_telepon'=>null,'email'=>auth()->user()?->email,'alamat'=>null,'kelurahan'=>null,'kecamatan'=>null,'kota'=>'Yogyakarta'];
    }

    /** @return array<string, mixed> */
    private function recordToData(Pemohon $record): array
    {
        return [
            'jenis_pemohon'=>$record->jenis_pemohon ?: 'perorangan','nama'=>$record->nama ?: auth()->user()?->name,'nik'=>$record->nik,'nib'=>$record->nib,'npwp'=>$record->npwp,'nomor_telepon'=>$record->nomor_telepon,'email'=>$record->email ?: auth()->user()?->email,'alamat'=>$record->alamat,'kelurahan'=>$record->kelurahan,'kecamatan'=>$record->kecamatan,'kota'=>$record->kota ?: 'Yogyakarta',
        ];
    }

    public static function canAccess(): bool { return auth()->check(); }
}
