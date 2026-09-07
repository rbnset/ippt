<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\StatusDokumen;
use App\Enums\StatusPemeriksaan;
use App\Enums\StatusPersetujuan;
use App\Enums\StatusPermohonan;
use App\Enums\UserRole;
use App\Filament\Resources\Pemohons\PemohonResource;
use App\Filament\Resources\Permohonans\PermohonanResource;
use App\Models\DokumenPermohonan;
use App\Models\KeputusanIppt;
use App\Models\PemeriksaanLapangan;
use App\Models\RekomendasiTeknis;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Services\PemohonVerificationService;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

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
        if (! auth()->user()?->hasRole(UserRole::PEMOHON)) {
            return;
        }

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
        Notification::make()->success()->title('Permintaan perubahan dikirim')->body('Permintaan Anda sedang menunggu izin Admin/Staff. Formulir belum dibuka.')->send();
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
            'diproses' => (clone $query)->whereNotIn('status', [StatusPermohonan::Diterbitkan->value, StatusPermohonan::Ditolak->value])->count(),
            'selesai' => (clone $query)->where('status', StatusPermohonan::Diterbitkan->value)->count(),
            'ditolak' => (clone $query)->where('status', StatusPermohonan::Ditolak->value)->count(),
        ];
    }

    /** @return array<string, mixed> */
    /** @return array<string, mixed> */
    public function getBackofficeDashboard(): array
    {
        $user = auth()->user();
        if (! $user || $user->hasRole(UserRole::PEMOHON)) {
            return [];
        }

        $role = $user->hasRole(UserRole::ADMIN)
            ? UserRole::ADMIN
            : ($user->hasRole(UserRole::STAFF)
                ? UserRole::STAFF
                : ($user->hasRole(UserRole::TIM_TEKNIS)
                    ? UserRole::TIM_TEKNIS
                    : ($user->hasRole(UserRole::KABID) ? UserRole::KABID : UserRole::KADIS)));

        $permohonan = Permohonan::query()->visibleTo($user);
        $allPermohonan = Permohonan::query();
        $notifications = $user->unreadNotifications()->count();

        $recent = (clone $permohonan)
            ->with('pemohon')
            ->latest('created_at')
            ->limit(7)
            ->get()
            ->map(fn (Permohonan $item): array => [
                'id' => $item->id,
                'nomor' => $item->nomor_permohonan,
                'pemohon' => $item->pemohon?->nama ?? '—',
                'status' => $item->status instanceof StatusPermohonan ? $item->status->getLabel() : (string) $item->status,
                'status_color' => $item->status instanceof StatusPermohonan ? $item->status->getColor() : 'gray',
                'tanggal' => $item->created_at?->format('d M Y H:i'),
            ])
            ->all();

        $pendingPemohon = Pemohon::query()->where('status_verifikasi', 'menunggu_verifikasi')->count();
        $perbaikanPemohon = Pemohon::query()->where('status_verifikasi', 'perlu_perbaikan')->count();
        $perubahanPemohon = Pemohon::query()->where('status_verifikasi', 'menunggu_perubahan')->count();
        $pendingDocuments = DokumenPermohonan::query()
            ->where('status', StatusDokumen::Menunggu->value)
            ->whereHas('permohonan', fn ($query) => $query->whereIn('id', (clone $permohonan)->select('id')))
            ->count();

        $bapDraft = PemeriksaanLapangan::query()
            ->where('status', StatusPemeriksaan::Draf->value)
            ->whereHas('permohonan', fn ($query) => $query->whereIn('id', (clone $permohonan)->select('id')))
            ->count();
        $bapFinal = PemeriksaanLapangan::query()
            ->where('status', StatusPemeriksaan::Final->value)
            ->whereHas('permohonan', fn ($query) => $query->whereIn('id', (clone $permohonan)->select('id')))
            ->count();
        $recommendationDraft = RekomendasiTeknis::query()
            ->whereIn('status', [StatusPersetujuan::Draf->value, StatusPersetujuan::Ditolak->value])
            ->whereHas('permohonan', fn ($query) => $query->whereIn('id', (clone $permohonan)->select('id')))
            ->count();
        $recommendationReview = RekomendasiTeknis::query()
            ->where('status', StatusPersetujuan::Diajukan->value)
            ->whereHas('permohonan', fn ($query) => $query->whereIn('id', (clone $permohonan)->select('id')))
            ->count();
        $risalahPending = (clone $permohonan)->where('status', StatusPermohonan::MenungguRisalah->value)->count();
        $decisionDraft = KeputusanIppt::query()
            ->whereIn('status', [StatusPersetujuan::Draf->value, StatusPersetujuan::Ditolak->value])
            ->whereHas('permohonan', fn ($query) => $query->whereIn('id', (clone $permohonan)->select('id')))
            ->count();
        $decisionReview = KeputusanIppt::query()
            ->where('status', StatusPersetujuan::Diajukan->value)
            ->whereHas('permohonan', fn ($query) => $query->whereIn('id', (clone $permohonan)->select('id')))
            ->count();
        $technicalProcess = (clone $permohonan)->whereIn('status', [
            StatusPermohonan::Verifikasi->value,
            StatusPermohonan::ProsesTeknis->value,
            StatusPermohonan::Rekomendasi->value,
        ])->count();

        $roleConfig = match ($role) {
            UserRole::ADMIN => [
                'role_label' => 'Administrator',
                'headline' => 'Kendali operasional layanan IPPT',
                'description' => 'Lihat seluruh antrean, pengecualian, dan tahapan yang membutuhkan perhatian sebelum masuk ke detail permohonan.',
                'stats' => [
                    ['label' => 'Pemohon Terdaftar', 'value' => Pemohon::query()->count(), 'description' => 'Seluruh data pemohon', 'icon' => 'heroicon-o-users'],
                    ['label' => 'Verifikasi Pemohon', 'value' => $pendingPemohon, 'description' => 'Perlu ditinjau petugas', 'icon' => 'heroicon-o-check-badge'],
                    ['label' => 'Izin Perubahan', 'value' => $perubahanPemohon, 'description' => 'Menunggu izin petugas', 'icon' => 'heroicon-o-pencil-square'],
                    ['label' => 'Dokumen Menunggu', 'value' => $pendingDocuments, 'description' => 'Perlu verifikasi', 'icon' => 'heroicon-o-document-check'],
                    ['label' => 'BAP Draf', 'value' => $bapDraft, 'description' => 'Belum difinalkan', 'icon' => 'heroicon-o-clipboard-document-check'],
                    ['label' => 'Rekomendasi Review', 'value' => $recommendationReview, 'description' => 'Menunggu persetujuan Kabid', 'icon' => 'heroicon-o-document-text'],
                    ['label' => 'Keputusan Review', 'value' => $decisionReview, 'description' => 'Menunggu pengesahan Kadis', 'icon' => 'heroicon-o-check-circle'],
                ],
                'tasks' => [
                    ['title' => 'Verifikasi pemohon', 'count' => $pendingPemohon, 'description' => 'Periksa data yang baru diajukan.', 'icon' => 'heroicon-o-check-badge', 'url' => PemohonResource::getUrl('index')],
                    ['title' => 'Izin perubahan data', 'count' => $perubahanPemohon, 'description' => 'Tinjau permintaan perubahan dari pemohon.', 'icon' => 'heroicon-o-pencil-square', 'url' => PemohonResource::getUrl('index')],
                    ['title' => 'Verifikasi dokumen', 'count' => $pendingDocuments, 'description' => 'Pastikan dokumen persyaratan diproses.', 'icon' => 'heroicon-o-document-check', 'url' => PermohonanResource::getUrl('index')],
                    ['title' => 'Monitor pengesahan', 'count' => $decisionReview, 'description' => 'Keputusan yang sedang menunggu Kadis.', 'icon' => 'heroicon-o-check-circle', 'url' => PermohonanResource::getUrl('index')],
                ],
            ],
            UserRole::STAFF => [
                'role_label' => 'Staff IPPT',
                'headline' => 'Antrean administrasi yang perlu diselesaikan',
                'description' => 'Prioritaskan verifikasi pemohon, dokumen, penerimaan Risalah, dan penyusunan keputusan sesuai tahap permohonan.',
                'stats' => [
                    ['label' => 'Verifikasi Pemohon', 'value' => $pendingPemohon, 'description' => 'Perlu diperiksa', 'icon' => 'heroicon-o-check-badge'],
                    ['label' => 'Izin Perubahan', 'value' => $perubahanPemohon, 'description' => 'Menunggu izin petugas', 'icon' => 'heroicon-o-pencil-square'],
                    ['label' => 'Dokumen Menunggu', 'value' => $pendingDocuments, 'description' => 'Perlu diverifikasi', 'icon' => 'heroicon-o-document-check'],
                    ['label' => 'Menunggu Risalah', 'value' => $risalahPending, 'description' => 'Risalah perlu diterima', 'icon' => 'heroicon-o-document-text'],
                    ['label' => 'Draf Keputusan', 'value' => $decisionDraft, 'description' => 'Perlu disusun/diperbaiki', 'icon' => 'heroicon-o-pencil-square'],
                    ['label' => 'Menunggu Tindak Lanjut', 'value' => (clone $permohonan)->whereIn('status', [StatusPermohonan::Diajukan->value, StatusPermohonan::Verifikasi->value, StatusPermohonan::Dikembalikan->value])->count(), 'description' => 'Antrean administrasi', 'icon' => 'heroicon-o-clipboard-document-check'],
                    ['label' => 'Notifikasi Belum Dibaca', 'value' => $notifications, 'description' => 'Perlu diperhatikan', 'icon' => 'heroicon-o-bell-alert'],
                ],
                'tasks' => [
                    ['title' => 'Verifikasi data pemohon', 'count' => $pendingPemohon, 'description' => 'Setujui atau minta perbaikan data.', 'icon' => 'heroicon-o-check-badge', 'url' => PemohonResource::getUrl('index')],
                    ['title' => 'Izin perubahan data', 'count' => $perubahanPemohon, 'description' => 'Tinjau permintaan perubahan dari pemohon.', 'icon' => 'heroicon-o-pencil-square', 'url' => PemohonResource::getUrl('index')],
                    ['title' => 'Periksa dokumen', 'count' => $pendingDocuments, 'description' => 'Terima atau tolak dengan arahan yang jelas.', 'icon' => 'heroicon-o-document-check', 'url' => PermohonanResource::getUrl('index')],
                    ['title' => 'Terima Risalah', 'count' => $risalahPending, 'description' => 'Catat dokumen resmi yang diterima dari Kantor Pertanahan.', 'icon' => 'heroicon-o-document-text', 'url' => PermohonanResource::getUrl('index')],
                    ['title' => 'Siapkan keputusan', 'count' => $decisionDraft, 'description' => 'Susun draf sebelum diajukan kepada Kadis.', 'icon' => 'heroicon-o-pencil-square', 'url' => PermohonanResource::getUrl('index')],
                ],
            ],
            UserRole::TIM_TEKNIS => [
                'role_label' => 'Tim Teknis',
                'headline' => 'Fokus pemeriksaan lapangan dan rekomendasi teknis',
                'description' => 'Dashboard ini menunjukkan pekerjaan teknis yang belum selesai: pemeriksaan, finalisasi BAP, dan penyusunan rekomendasi.',
                'stats' => [
                    ['label' => 'Proses Teknis', 'value' => $technicalProcess, 'description' => 'Permohonan dalam tahap teknis', 'icon' => 'heroicon-o-clipboard-document-check'],
                    ['label' => 'BAP Draf', 'value' => $bapDraft, 'description' => 'Perlu dilengkapi/final', 'icon' => 'heroicon-o-pencil-square'],
                    ['label' => 'BAP Final', 'value' => $bapFinal, 'description' => 'Sudah difinalkan', 'icon' => 'heroicon-o-check-circle'],
                    ['label' => 'Rekomendasi Draf', 'value' => $recommendationDraft, 'description' => 'Perlu disusun/diperbaiki', 'icon' => 'heroicon-o-document-text'],
                    ['label' => 'Menunggu Review Kabid', 'value' => $recommendationReview, 'description' => 'Sudah diajukan', 'icon' => 'heroicon-o-check-badge'],
                ],
                'tasks' => [
                    ['title' => 'Laksanakan pemeriksaan lapangan', 'count' => (clone $permohonan)->whereIn('status', [StatusPermohonan::Verifikasi->value, StatusPermohonan::ProsesTeknis->value])->count(), 'description' => 'Pastikan pemeriksaan dan data lapangan terdokumentasi.', 'icon' => 'heroicon-o-clipboard-document-check', 'url' => PermohonanResource::getUrl('index')],
                    ['title' => 'Finalisasi BAP', 'count' => $bapDraft, 'description' => 'Lengkapi temuan, kesimpulan, foto, lalu finalkan BAP.', 'icon' => 'heroicon-o-check-circle', 'url' => PermohonanResource::getUrl('index')],
                    ['title' => 'Susun rekomendasi teknis', 'count' => $recommendationDraft, 'description' => 'Gunakan BAP final sebagai dasar rekomendasi.', 'icon' => 'heroicon-o-document-text', 'url' => PermohonanResource::getUrl('index')],
                ],
            ],
            UserRole::KABID => [
                'role_label' => 'Kepala Bidang',
                'headline' => 'Pekerjaan yang menunggu review dan persetujuan',
                'description' => 'Prioritas utama adalah menilai rekomendasi teknis yang sudah diajukan dan memastikan keputusan teknis dapat dilanjutkan.',
                'stats' => [
                    ['label' => 'Menunggu Review', 'value' => $recommendationReview, 'description' => 'Rekomendasi perlu diperiksa', 'icon' => 'heroicon-o-document-check'],
                    ['label' => 'Tahap Rekomendasi', 'value' => (clone $permohonan)->whereIn('status', [StatusPermohonan::ProsesTeknis->value, StatusPermohonan::Rekomendasi->value])->count(), 'description' => 'Dalam ruang lingkup bidang', 'icon' => 'heroicon-o-document-text'],
                    ['label' => 'BAP Final', 'value' => $bapFinal, 'description' => 'Bahan pemeriksaan tersedia', 'icon' => 'heroicon-o-check-circle'],
                    ['label' => 'Notifikasi Belum Dibaca', 'value' => $notifications, 'description' => 'Pemberitahuan untuk Anda', 'icon' => 'heroicon-o-bell-alert'],
                ],
                'tasks' => [
                    ['title' => 'Review rekomendasi teknis', 'count' => $recommendationReview, 'description' => 'Periksa BAP, dasar teknis, hasil, dan ketentuan sebelum menyetujui.', 'icon' => 'heroicon-o-document-check', 'url' => PermohonanResource::getUrl('index')],
                    ['title' => 'Pantau BAP final', 'count' => $bapFinal, 'description' => 'Gunakan BAP sebagai bahan review rekomendasi.', 'icon' => 'heroicon-o-clipboard-document-check', 'url' => PermohonanResource::getUrl('index')],
                ],
            ],
            UserRole::KADIS => [
                'role_label' => 'Kepala Dinas',
                'headline' => 'Pengesahan keputusan yang menunggu Anda',
                'description' => 'Prioritas dashboard adalah keputusan IPPT yang telah disusun dan diajukan untuk pengesahan akhir.',
                'stats' => [
                    ['label' => 'Menunggu Pengesahan', 'value' => $decisionReview, 'description' => 'Keputusan perlu ditetapkan', 'icon' => 'heroicon-o-check-badge'],
                    ['label' => 'Tahap Keputusan', 'value' => (clone $permohonan)->where('status', StatusPermohonan::Keputusan->value)->count(), 'description' => 'Permohonan menunggu keputusan', 'icon' => 'heroicon-o-document-check'],
                    ['label' => 'Menunggu Risalah', 'value' => $risalahPending, 'description' => 'Dalam pemantauan proses', 'icon' => 'heroicon-o-document-text'],
                    ['label' => 'Notifikasi Belum Dibaca', 'value' => $notifications, 'description' => 'Pemberitahuan untuk Anda', 'icon' => 'heroicon-o-bell-alert'],
                ],
                'tasks' => [
                    ['title' => 'Sahkan keputusan IPPT', 'count' => $decisionReview, 'description' => 'Periksa hasil pertimbangan dan tetapkan keputusan akhir.', 'icon' => 'heroicon-o-check-badge', 'url' => PermohonanResource::getUrl('index')],
                    ['title' => 'Pantau permohonan tahap keputusan', 'count' => (clone $permohonan)->where('status', StatusPermohonan::Keputusan->value)->count(), 'description' => 'Pastikan tidak ada keputusan yang tertahan.', 'icon' => 'heroicon-o-document-check', 'url' => PermohonanResource::getUrl('index')],
                ],
            ],
            default => [
                'role_label' => $role->getLabel(),
                'headline' => 'Dasbor layanan IPPT',
                'description' => 'Pantau pekerjaan sesuai kewenangan akun Anda.',
                'stats' => [],
                'tasks' => [],
            ],
        };

        $roleConfig['greeting'] = $this->greeting();
        $roleConfig['notifications'] = $notifications;
        $roleConfig['recent'] = $recent;
        $roleConfig['links'] = [
            'pemohon' => PemohonResource::getUrl('index'),
            'permohonan' => PermohonanResource::getUrl('index'),
        ];

        return $roleConfig;
    }
    private function greeting(): string
    {
        $hour = Carbon::now()->hour;
        return match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
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
