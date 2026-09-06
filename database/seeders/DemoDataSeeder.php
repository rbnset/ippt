<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\HasilPemeriksaan;
use App\Enums\HasilRekomendasi;
use App\Enums\HasilRisalah;
use App\Enums\JenisDokumen;
use App\Enums\JenisKeputusan;
use App\Enums\StatusDokumen;
use App\Enums\StatusPermohonan;
use App\Enums\StatusPersetujuan;
use App\Enums\UserRole;
use App\Models\DokumenPermohonan;
use App\Models\KeputusanIppt;
use App\Models\PemeriksaanLapangan;
use App\Models\Pemohon;
use App\Models\Permohonan;
use App\Models\RekomendasiTeknis;
use App\Models\RisalahPertimbangan;
use App\Models\User;
use App\Services\PemeriksaanLapanganPdfService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Menyiapkan data demo IPPT...');

        $users = User::query()->get()->keyBy(fn (User $user) => $user->role?->value ?? $user->email);

        $admin = $this->userByRole($users, UserRole::ADMIN);
        $pemohonAccount = $this->userByEmail($users, 'pemohon@ippt.test');
        $this->ensureDemoPemohonAccounts();
        $users = User::query()->get()->keyBy(fn (User $user) => $user->role?->value ?? $user->email);
        $pemohonAccount = $this->userByEmail($users, 'pemohon@ippt.test');
        $staff = $this->userByRole($users, UserRole::STAFF);
        $teknis = $this->userByRole($users, UserRole::TIM_TEKNIS);
        $kabid = $this->userByRole($users, UserRole::KABID);
        $kadis = $this->userByRole($users, UserRole::KADIS);

        Storage::disk('public')->makeDirectory('ippt/demo');

        $pemohons = $this->seedPemohons($pemohonAccount, $staff);
        $this->seedPermohonan($pemohons, $admin, $staff, $teknis, $kabid, $kadis);

        $this->command?->info('Data demo IPPT selesai dibuat.');
    }

    private function ensureDemoPemohonAccounts(): void
    {
        $accounts = [
            ['name' => 'Siti Rahmawati', 'email' => 'siti@ippt.test'],
            ['name' => 'Legal CV Griya Lestari', 'email' => 'legal@griyalestari.test'],
            ['name' => 'Perizinan PT Artha Bangun', 'email' => 'perizinan@arthabangun.test'],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'password' => 'password', 'role' => UserRole::PEMOHON, 'status_akun' => 'aktif'],
            );
        }
    }

    private function seedPemohons(?User $pemohonAccount, User $staff): array
    {
        $data = [
            ['key' => 'andi', 'user_id' => $pemohonAccount?->id, 'jenis_pemohon' => 'perorangan', 'nama' => 'Andi Pratama', 'nik' => '3471011505850001', 'nib' => null, 'npwp' => '7192847365123456', 'telepon' => '081234560001', 'email' => 'andi.pratama@example.test', 'alamat' => 'Jl. Kaliurang KM 7 No. 18', 'kelurahan' => 'Sinduharjo', 'kecamatan' => 'Ngaglik', 'kota' => 'Sleman'],
            ['key' => 'siti', 'user_id' => User::where('email', 'siti@ippt.test')->value('id'), 'jenis_pemohon' => 'perorangan', 'nama' => 'Siti Rahmawati', 'nik' => '3471024803900002', 'nib' => null, 'npwp' => null, 'telepon' => '081234560002', 'email' => 'siti.rahmawati@example.test', 'alamat' => 'Jl. Parangtritis No. 45', 'kelurahan' => 'Mantrijeron', 'kecamatan' => 'Mantrijeron', 'kota' => 'Yogyakarta'],
            ['key' => 'budi', 'user_id' => null, 'jenis_pemohon' => 'perorangan', 'nama' => 'Budi Santoso', 'nik' => '3404012107820003', 'nib' => null, 'npwp' => '7192847365123457', 'telepon' => '082200330003', 'email' => 'budi.santoso@example.test', 'alamat' => 'Dusun Karangasem RT 02/RW 04', 'kelurahan' => 'Sumberagung', 'kecamatan' => 'Moyudan', 'kota' => 'Sleman'],
            ['key' => 'rani', 'user_id' => null, 'jenis_pemohon' => 'perorangan', 'nama' => 'Rani Kusuma Dewi', 'nik' => '3471046201970004', 'nib' => null, 'npwp' => null, 'telepon' => '083800440004', 'email' => 'rani.kusuma@example.test', 'alamat' => 'Jl. Wonosari KM 9 No. 7', 'kelurahan' => 'Banguntapan', 'kecamatan' => 'Banguntapan', 'kota' => 'Bantul'],
            ['key' => 'cv_griya', 'user_id' => User::where('email', 'legal@griyalestari.test')->value('id'), 'jenis_pemohon' => 'badan', 'nama' => 'CV Griya Lestari', 'nik' => null, 'nib' => '9120304501234', 'npwp' => '7192847365123458', 'telepon' => '02744550005', 'email' => 'legal@griyalestari.example.test', 'alamat' => 'Jl. Magelang KM 8 No. 22', 'kelurahan' => 'Sinduadi', 'kecamatan' => 'Mlati', 'kota' => 'Sleman'],
            ['key' => 'pt_artha', 'user_id' => User::where('email', 'perizinan@arthabangun.test')->value('id'), 'jenis_pemohon' => 'badan', 'nama' => 'PT Artha Bangun Sejahtera', 'nik' => null, 'nib' => '9120304501235', 'npwp' => '7192847365123459', 'telepon' => '02748880006', 'email' => 'perizinan@arthabangun.example.test', 'alamat' => 'Jl. Ring Road Utara No. 101', 'kelurahan' => 'Condongcatur', 'kecamatan' => 'Depok', 'kota' => 'Sleman'],
            ['key' => 'koperasi', 'user_id' => null, 'jenis_pemohon' => 'badan', 'nama' => 'Koperasi Maju Bersama', 'nik' => null, 'nib' => '9120304501236', 'npwp' => '7192847365123460', 'telepon' => '02747770007', 'email' => 'koperasi.maju@example.test', 'alamat' => 'Jl. Godean KM 5 No. 12', 'kelurahan' => 'Sidomoyo', 'kecamatan' => 'Godean', 'kota' => 'Sleman'],
            ['key' => 'dwi', 'user_id' => null, 'jenis_pemohon' => 'perorangan', 'nama' => 'Dwi Hartono', 'nik' => '3404020509760008', 'nib' => null, 'npwp' => null, 'telepon' => '085700880008', 'email' => 'dwi.hartono@example.test', 'alamat' => 'Dusun Nglanggeran RT 03/RW 02', 'kelurahan' => 'Nglanggeran', 'kecamatan' => 'Patuk', 'kota' => 'Gunungkidul'],
            ['key' => 'maya', 'user_id' => null, 'jenis_pemohon' => 'perorangan', 'nama' => 'Maya Laksmi', 'nik' => '3471054404880009', 'nib' => null, 'npwp' => null, 'telepon' => '081900990009', 'email' => 'maya.laksmi@example.test', 'alamat' => 'Jl. Imogiri Timur KM 6', 'kelurahan' => 'Giriloyo', 'kecamatan' => 'Imogiri', 'kota' => 'Bantul'],
        ];

        $result = [];
        foreach ($data as $row) {
            $result[$row['key']] = Pemohon::create([
                'user_id' => $row['user_id'],
                'jenis_pemohon' => $row['jenis_pemohon'],
                'nama' => $row['nama'],
                'nik' => $row['nik'],
                'nib' => $row['nib'],
                'npwp' => $row['npwp'],
                'nomor_telepon' => $row['telepon'],
                'email' => $row['email'],
                'alamat' => $row['alamat'],
                'kelurahan' => $row['kelurahan'],
                'kecamatan' => $row['kecamatan'],
                'kota' => $row['kota'],
                'versi_data' => 1,
                'status_verifikasi' => 'terverifikasi',
                'nomor_antrian' => 'PMH-' . now()->format('Y') . '-' . str_pad((string) ($row['key'] === 'andi' ? 1 : count($result) + 1), 5, '0', STR_PAD_LEFT),
                'diajukan_pada' => now()->subDays(2),
            ]);
        }

        return $result;
    }

    private function seedPermohonan(array $p, User $admin, User $staff, User $teknis, User $kabid, User $kadis): void
    {
        $cases = [
            ['no' => 'IPPT/2026/09/0001', 'pemohon' => 'andi', 'date' => '2026-09-01', 'status' => StatusPermohonan::Diajukan, 'lokasi' => 'Jl. Kaliurang KM 7, Sinduharjo, Ngaglik, Sleman', 'luas' => 420, 'hak' => 'SHM No. 01872/Sinduharjo', 'now' => 'Tanah pekarangan', 'request' => 'Rumah tinggal', 'docs' => 'minimal'],
            ['no' => 'IPPT/2026/09/0002', 'pemohon' => 'siti', 'date' => '2026-08-30', 'status' => StatusPermohonan::Verifikasi, 'lokasi' => 'Jl. Parangtritis No. 45, Mantrijeron, Yogyakarta', 'luas' => 315, 'hak' => 'SHM No. 00981/Mantrijeron', 'now' => 'Pekarangan', 'request' => 'Rumah kos', 'docs' => 'pending'],
            ['no' => 'IPPT/2026/09/0003', 'pemohon' => 'budi', 'date' => '2026-08-27', 'status' => StatusPermohonan::Dikembalikan, 'diwakilkan' => true, 'nama_kuasa' => 'Dewi Anggraini', 'nik_kuasa' => '3471015807900011', 'lokasi' => 'Sumberagung, Moyudan, Sleman', 'luas' => 680, 'hak' => 'SHM No. 00215/Sumberagung', 'now' => 'Sawah', 'request' => 'Rumah tinggal', 'docs' => 'returned'],
            ['no' => 'IPPT/2026/09/0004', 'pemohon' => 'rani', 'date' => '2026-08-18', 'status' => StatusPermohonan::ProsesTeknis, 'lokasi' => 'Banguntapan, Bantul', 'luas' => 510, 'hak' => 'SHM No. 01440/Banguntapan', 'now' => 'Pekarangan', 'request' => 'Perumahan skala kecil', 'docs' => 'complete'],
            ['no' => 'IPPT/2026/09/0005', 'pemohon' => 'cv_griya', 'date' => '2026-08-12', 'status' => StatusPermohonan::Rekomendasi, 'diwakilkan' => true, 'nama_kuasa' => 'Rizky Maulana', 'nik_kuasa' => '3471041201840022', 'lokasi' => 'Sinduadi, Mlati, Sleman', 'luas' => 1850, 'hak' => 'SHGB No. 00321/Sinduadi', 'now' => 'Lahan kosong', 'request' => 'Pergudangan dan kantor', 'docs' => 'complete'],
            ['no' => 'IPPT/2026/09/0006', 'pemohon' => 'pt_artha', 'date' => '2026-07-25', 'status' => StatusPermohonan::MenungguRisalah, 'lokasi' => 'Condongcatur, Depok, Sleman', 'luas' => 3200, 'hak' => 'SHGB No. 00654/Condongcatur', 'now' => 'Pekarangan', 'request' => 'Apartemen dan fasilitas pendukung', 'docs' => 'complete'],
            ['no' => 'IPPT/2026/09/0007', 'pemohon' => 'koperasi', 'date' => '2026-07-10', 'status' => StatusPermohonan::Keputusan, 'lokasi' => 'Sidomoyo, Godean, Sleman', 'luas' => 1200, 'hak' => 'SHM No. 01123/Sidomoyo', 'now' => 'Pekarangan', 'request' => 'Pasar rakyat dan fasilitas koperasi', 'docs' => 'complete'],
            ['no' => 'IPPT/2026/08/0008', 'pemohon' => 'dwi', 'date' => '2026-06-28', 'status' => StatusPermohonan::Diterbitkan, 'lokasi' => 'Nglanggeran, Patuk, Gunungkidul', 'luas' => 750, 'hak' => 'SHM No. 00771/Nglanggeran', 'now' => 'Tegalan', 'request' => 'Homestay dan fasilitas wisata', 'docs' => 'complete'],
            ['no' => 'IPPT/2026/08/0009', 'pemohon' => 'maya', 'date' => '2026-06-15', 'status' => StatusPermohonan::Ditolak, 'lokasi' => 'Giriloyo, Imogiri, Bantul', 'luas' => 900, 'hak' => 'SHM No. 02190/Giriloyo', 'now' => 'Sawah produktif', 'request' => 'Gudang komersial', 'docs' => 'complete'],
            ['no' => 'IPPT/2026/06/0010', 'pemohon' => 'andi', 'date' => '2026-06-02', 'status' => StatusPermohonan::Diterbitkan, 'lokasi' => 'Sinduharjo, Ngaglik, Sleman', 'luas' => 600, 'hak' => 'SHM No. 01901/Sinduharjo', 'now' => 'Pekarangan', 'request' => 'Rumah tinggal dan toko kecil', 'docs' => 'complete'],
        ];

        foreach ($cases as $case) {
            $permohonan = Permohonan::create([
                'pemohon_id' => $p[$case['pemohon']]->id,
                'diwakilkan' => $case['diwakilkan'] ?? false,
                'nama_pemegang_kuasa' => $case['nama_kuasa'] ?? null,
                'nik_pemegang_kuasa' => $case['nik_kuasa'] ?? null,
                'nomor_permohonan' => $case['no'],
                'tanggal_permohonan' => $case['date'],
                'status' => $case['status'],
                'lokasi_tanah' => $case['lokasi'],
                'luas_tanah' => $case['luas'],
                'nomor_hak' => $case['hak'],
                'penggunaan_sekarang' => $case['now'],
                'penggunaan_dimohonkan' => $case['request'],
                'keterangan' => $this->keterangan($case['status']),
            ]);

            $this->seedDocuments($permohonan, $p[$case['pemohon']], $staff, $case['docs']);
            $this->seedWorkflow($permohonan, $case['status'], $staff, $teknis, $kabid, $kadis);
        }
    }

    private function seedDocuments(Permohonan $permohonan, Pemohon $pemohon, User $staff, string $mode): void
    {
        $required = [
            JenisDokumen::Ktp,
            JenisDokumen::BuktiHak,
            JenisDokumen::SuratTidakSengketa,
            JenisDokumen::Pbb,
            JenisDokumen::SitePlan,
        ];

        if ($permohonan->diwakilkan) {
            $required[] = JenisDokumen::KtpPemegangKuasa;
            $required[] = JenisDokumen::SuratKuasa;
        }

        foreach ($required as $jenis) {
            $status = match ($mode) {
                'minimal' => in_array($jenis, [JenisDokumen::Ktp, JenisDokumen::BuktiHak], true) ? StatusDokumen::Menunggu : null,
                'pending' => $jenis === JenisDokumen::Pbb ? StatusDokumen::Menunggu : StatusDokumen::Diterima,
                'returned' => $jenis === JenisDokumen::SitePlan ? StatusDokumen::Ditolak : StatusDokumen::Diterima,
                default => StatusDokumen::Diterima,
            };

            if ($status === null) {
                continue;
            }

            $filename = strtolower($permohonan->nomor_permohonan) . '_' . $jenis->value . '.pdf';
            $path = 'ippt/demo/' . $filename;
            $content = $this->pdfContent($jenis->getLabel(), $pemohon->nama, $permohonan->nomor_permohonan, $status->getLabel());
            Storage::disk('public')->put($path, $content);

            DokumenPermohonan::create([
                'permohonan_id' => $permohonan->id,
                'diunggah_oleh' => $pemohon->user_id ?? $staff->id,
                'jenis_dokumen' => $jenis,
                'nama_file' => $filename,
                'lokasi_file' => $path,
                'tipe_file' => 'pdf',
                'ukuran_file' => strlen($content),
                'status' => $status,
                'catatan' => $status === StatusDokumen::Ditolak ? 'Site plan belum memuat titik koordinat dan batas persil secara lengkap; mohon diperbaiki.' : null,
            ]);
        }
    }

    private function seedWorkflow(Permohonan $permohonan, StatusPermohonan $status, User $staff, User $teknis, User $kabid, User $kadis): void
    {
        if (in_array($status, [StatusPermohonan::Verifikasi, StatusPermohonan::Dikembalikan, StatusPermohonan::ProsesTeknis, StatusPermohonan::Rekomendasi, StatusPermohonan::MenungguRisalah, StatusPermohonan::Keputusan, StatusPermohonan::Diterbitkan, StatusPermohonan::Ditolak], true)) {
            $permohonan->update([
                'diverifikasi_oleh' => $staff->id,
                'diverifikasi_pada' => Carbon::parse($permohonan->tanggal_permohonan)->addDays(2),
            ]);
        }

        if (in_array($status, [StatusPermohonan::ProsesTeknis, StatusPermohonan::Rekomendasi, StatusPermohonan::MenungguRisalah, StatusPermohonan::Keputusan, StatusPermohonan::Diterbitkan, StatusPermohonan::Ditolak], true)) {
            $permohonan->update([
                'direview_teknis_oleh' => $teknis->id,
                'direview_teknis_pada' => Carbon::parse($permohonan->tanggal_permohonan)->addDays(8),
            ]);

            $hasilPemeriksaan = $status === StatusPermohonan::Ditolak ? HasilPemeriksaan::TidakSesuai : HasilPemeriksaan::Sesuai;
            $tanggal = Carbon::parse($permohonan->tanggal_permohonan)->addDays(7);
            $pemeriksaan = PemeriksaanLapangan::create([
                'permohonan_id' => $permohonan->id,
                'dibuat_oleh' => $teknis->id,
                'tanggal_pemeriksaan' => $tanggal,
                'waktu_mulai' => '09:00',
                'waktu_selesai' => '11:00',
                'nama_tim' => $teknis->name . ', Tim Pemeriksa Lapangan IPPT',
                'cuaca' => 'Cerah',
                'latitude' => $status === StatusPermohonan::Ditolak ? -7.8015000 : -7.7971000,
                'longitude' => $status === StatusPermohonan::Ditolak ? 110.3657000 : 110.3688000,
                'alamat_lokasi' => $permohonan->lokasi_tanah,
                'checklist' => [
                    'identitas_lokasi', 'batas_bidang', 'akses_jalan', 'penggunaan_eksisting',
                    'kondisi_fisik', 'denah_lokasi', 'koordinat', 'lingkungan',
                ],
                'kondisi_eksisting' => $hasilPemeriksaan === HasilPemeriksaan::Sesuai
                    ? 'Kondisi fisik lokasi, akses, batas persil, dan penggunaan eksisting dapat diidentifikasi serta sesuai dengan data permohonan.'
                    : 'Pada saat peninjauan ditemukan kondisi eksisting yang berbeda dari rencana penggunaan yang diajukan dan perlu ditindaklanjuti dalam kajian teknis.',
                'hasil_pemeriksaan' => $hasilPemeriksaan === HasilPemeriksaan::Sesuai
                    ? 'Kondisi fisik, akses jalan, batas persil, dan penggunaan eksisting sesuai dengan dokumen permohonan.'
                    : 'Ditemukan ketidaksesuaian antara penggunaan eksisting dan rencana penggunaan yang dimohonkan serta kendala kesesuaian tata ruang.',
                'temuan' => $hasilPemeriksaan === HasilPemeriksaan::Sesuai
                    ? 'Tidak ditemukan perbedaan material antara kondisi lapangan dengan data lokasi yang diajukan.'
                    : 'Ditemukan perbedaan kondisi eksisting dengan rencana pemanfaatan serta indikasi kendala kesesuaian tata ruang.',
                'kesimpulan' => $hasilPemeriksaan === HasilPemeriksaan::Sesuai
                    ? 'Lokasi dapat dilanjutkan ke tahapan teknis berikutnya berdasarkan hasil pemeriksaan lapangan.'
                    : 'Permohonan belum dapat direkomendasikan berdasarkan hasil pemeriksaan lapangan dan perlu tindak lanjut sesuai ketentuan yang berlaku.',
                'rekomendasi' => $hasilPemeriksaan === HasilPemeriksaan::Sesuai
                    ? 'Dilanjutkan ke penyusunan rekomendasi teknis dengan tetap memperhatikan ketentuan tata ruang dan kondisi lapangan.'
                    : 'Dilakukan perbaikan substansi permohonan atau konsultasi teknis sebelum dapat diproses lebih lanjut.',
                'hasil' => $hasilPemeriksaan,
                'status' => \App\Enums\StatusPemeriksaan::Final,
                'nomor_bap' => 'BAP/IPPT/2026/' . str_pad((string) $permohonan->id, 5, '0', STR_PAD_LEFT),
                'difinalisasi_oleh' => $teknis->id,
                'difinalisasi_pada' => $tanggal->copy()->addDay()->setTime(14, 0),
                'foto_lapangan' => [],
            ]);
            $pemeriksaan->refresh();
            $bapPath = app(PemeriksaanLapanganPdfService::class)->store($pemeriksaan);
            $pemeriksaan->update(['generated_bap_path' => $bapPath]);
        }

        if (in_array($status, [StatusPermohonan::Rekomendasi, StatusPermohonan::MenungguRisalah, StatusPermohonan::Keputusan, StatusPermohonan::Diterbitkan, StatusPermohonan::Ditolak], true)) {
            $approved = $status !== StatusPermohonan::Ditolak;
            $hasil = $approved ? HasilRekomendasi::Direkomendasikan : HasilRekomendasi::TidakDirekomendasikan;
            $recommendationStatus = $status === StatusPermohonan::Rekomendasi ? StatusPersetujuan::Diajukan : ($approved ? StatusPersetujuan::Disetujui : StatusPersetujuan::Ditolak);
            $date = Carbon::parse($permohonan->tanggal_permohonan)->addDays(12);
            $content = $this->pdfContent('Rekomendasi Teknis', $permohonan->pemohon->nama, $permohonan->nomor_permohonan, $hasil->getLabel());
            $path = 'ippt/demo/' . strtolower($permohonan->nomor_permohonan) . '_rekomendasi.pdf';
            Storage::disk('public')->put($path, $content);
            RekomendasiTeknis::create([
                'permohonan_id' => $permohonan->id,
                'nomor_rekomendasi' => 'RT/IPPT/2026/' . str_pad((string) $permohonan->id, 4, '0', STR_PAD_LEFT),
                'tanggal_rekomendasi' => $date,
                'hasil' => $hasil,
                'pertimbangan' => $approved ? 'Permohonan dapat dipertimbangkan setelah memperhatikan hasil pemeriksaan lapangan dan kesesuaian rencana pemanfaatan tanah.' : 'Rencana pemanfaatan tidak dapat direkomendasikan karena terdapat ketidaksesuaian substansi dan tata ruang.',
                'ketentuan' => $approved ? 'Pelaksanaan wajib mengikuti ketentuan tata ruang, batas persil, akses jalan, dan peraturan bangunan yang berlaku.' : null,
                'disusun_oleh' => $teknis->id,
                'direview_oleh' => $teknis->id,
                'disetujui_oleh' => $approved && $status !== StatusPermohonan::Rekomendasi ? $kabid->id : null,
                'lokasi_file' => $path,
                'status' => $recommendationStatus,
            ]);
            if ($approved && $status !== StatusPermohonan::Rekomendasi) {
                $permohonan->update([
                    'disetujui_rekomendasi_oleh' => $kabid->id,
                    'disetujui_rekomendasi_pada' => $date->copy()->addDay(),
                ]);
            }
        }

        if (in_array($status, [StatusPermohonan::MenungguRisalah, StatusPermohonan::Keputusan, StatusPermohonan::Diterbitkan], true)) {
            $date = Carbon::parse($permohonan->tanggal_permohonan)->addDays(16);
            $content = $this->pdfContent('Risalah Pertimbangan', $permohonan->pemohon->nama, $permohonan->nomor_permohonan, HasilRisalah::Mendukung->getLabel());
            $path = 'ippt/demo/' . strtolower($permohonan->nomor_permohonan) . '_risalah.pdf';
            Storage::disk('public')->put($path, $content);
            RisalahPertimbangan::create([
                'permohonan_id' => $permohonan->id,
                'diterima_oleh' => $staff->id,
                'nomor_risalah' => 'RIS/IPPT/2026/' . str_pad((string) $permohonan->id, 4, '0', STR_PAD_LEFT),
                'tanggal_risalah' => $date,
                'hasil' => HasilRisalah::Mendukung,
                'lokasi_file' => $path,
                'catatan' => 'Risalah pertimbangan diterima dan dicatat untuk proses keputusan IPPT.',
            ]);
        }

        if (in_array($status, [StatusPermohonan::Keputusan, StatusPermohonan::Diterbitkan], true)) {
            $isIssued = $status === StatusPermohonan::Diterbitkan;
            $date = Carbon::parse($permohonan->tanggal_permohonan)->addDays(20);
            $content = $this->pdfContent('Keputusan IPPT', $permohonan->pemohon->nama, $permohonan->nomor_permohonan, $isIssued ? 'IPPT Diterbitkan' : 'Menunggu Persetujuan Kepala Dinas');
            $path = 'ippt/demo/' . strtolower($permohonan->nomor_permohonan) . '_keputusan.pdf';
            Storage::disk('public')->put($path, $content);
            KeputusanIppt::create([
                'permohonan_id' => $permohonan->id,
                'disusun_oleh' => $kadis->id,
                'disetujui_oleh' => $isIssued ? $kadis->id : null,
                'nomor_keputusan' => $isIssued ? 'SK/IPPT/2026/' . str_pad((string) $permohonan->id, 4, '0', STR_PAD_LEFT) : null,
                'tanggal_keputusan' => $isIssued ? $date : null,
                'jenis_keputusan' => JenisKeputusan::Terbit,
                'status' => $isIssued ? StatusPersetujuan::Disetujui : StatusPersetujuan::Diajukan,
                'alasan' => null,
                'lokasi_file' => $path,
            ]);
            if ($isIssued) {
                $permohonan->update([
                    'disetujui_keputusan_oleh' => $kadis->id,
                    'disetujui_keputusan_pada' => $date->copy()->addDay(),
                ]);
            }
        }

        if ($status === StatusPermohonan::Ditolak) {
            $date = Carbon::parse($permohonan->tanggal_permohonan)->addDays(14);
            $content = $this->pdfContent('Keputusan IPPT', $permohonan->pemohon->nama, $permohonan->nomor_permohonan, 'Permohonan Ditolak');
            $path = 'ippt/demo/' . strtolower($permohonan->nomor_permohonan) . '_keputusan_tolak.pdf';
            Storage::disk('public')->put($path, $content);
            KeputusanIppt::create([
                'permohonan_id' => $permohonan->id,
                'disusun_oleh' => $kadis->id,
                'disetujui_oleh' => $kadis->id,
                'nomor_keputusan' => 'SK/IPPT-TOLAK/2026/' . str_pad((string) $permohonan->id, 4, '0', STR_PAD_LEFT),
                'tanggal_keputusan' => $date,
                'jenis_keputusan' => JenisKeputusan::Tolak,
                'status' => StatusPersetujuan::Disetujui,
                'alasan' => 'Rencana pemanfaatan tanah tidak dapat diproses lebih lanjut karena hasil pemeriksaan menunjukkan ketidaksesuaian substansi dengan ketentuan tata ruang yang berlaku.',
                'lokasi_file' => $path,
            ]);
            $permohonan->update([
                'disetujui_keputusan_oleh' => $kadis->id,
                'disetujui_keputusan_pada' => $date->copy()->addDay(),
            ]);
        }
    }

    private function keterangan(StatusPermohonan $status): string
    {
        return match ($status) {
            StatusPermohonan::Diajukan => 'Permohonan baru dikirim oleh pemohon dan menunggu pemeriksaan administratif.',
            StatusPermohonan::Verifikasi => 'Berkas sedang diperiksa petugas untuk memastikan persyaratan administratif terpenuhi.',
            StatusPermohonan::Dikembalikan => 'Berkas dikembalikan kepada pemohon untuk melengkapi dan memperbaiki dokumen yang belum sesuai.',
            StatusPermohonan::ProsesTeknis => 'Berkas administratif telah memenuhi syarat dan sedang masuk pemeriksaan teknis/lapangan.',
            StatusPermohonan::Rekomendasi => 'Pemeriksaan lapangan selesai dan rekomendasi teknis sedang disusun untuk persetujuan.',
            StatusPermohonan::MenungguRisalah => 'Rekomendasi teknis telah disetujui dan permohonan menunggu risalah pertimbangan.',
            StatusPermohonan::Keputusan => 'Risalah pertimbangan telah diterima dan rancangan keputusan menunggu persetujuan Kepala Dinas.',
            StatusPermohonan::Diterbitkan => 'Keputusan telah disetujui dan dokumen IPPT telah diterbitkan.',
            StatusPermohonan::Ditolak => 'Permohonan telah diputuskan tidak dapat diterbitkan berdasarkan hasil pemeriksaan dan pertimbangan teknis.',
        };
    }

    private function pdfContent(string $title, string $name, string $number, string $status): string
    {
        $escape = static fn (string $value): string => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
        $lines = [$title, 'Data Demo Sistem IPPT', 'Pemohon: ' . $name, 'Nomor Permohonan: ' . $number, 'Status: ' . $status, 'Dokumen contoh untuk pengembangan dan pengujian aplikasi.'];
        $stream = "BT\n/F1 11 Tf\n50 760 Td\n";
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $stream .= "0 -24 Td\n";
            }
            $stream .= '(' . $escape($line) . ") Tj\n";
        }
        $stream .= "ET\n";
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream';
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[$i + 1] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }

    private function userByRole($users, UserRole $role): User
    {
        return $users->first(fn (User $user) => $user->role === $role) ?? throw new \RuntimeException('User role ' . $role->value . ' belum tersedia. Jalankan UserSeeder terlebih dahulu.');
    }

    private function userByEmail($users, string $email): ?User
    {
        return $users->first(fn (User $user) => $user->email === $email);
    }
}
