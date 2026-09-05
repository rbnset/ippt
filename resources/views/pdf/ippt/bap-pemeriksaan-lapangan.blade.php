<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAP Pemeriksaan Lapangan - {{ $pemeriksaan->nomor_bap }}</title>
    <style>
        @page { margin: 1.8cm 2cm 1.8cm 2cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.45; color: #111; }
        .kop { border-bottom: 3px solid #111; padding-bottom: 7px; margin-bottom: 18px; }
        .kop-table { width: 100%; border-collapse: collapse; }
        .kop-logo { width: 76px; text-align: center; vertical-align: middle; }
        .kop-logo img { width: 58px; max-height: 62px; }
        .kop-text { text-align: center; vertical-align: middle; }
        .pemkot { font-size: 12pt; font-weight: bold; }
        .dinas { font-size: 14pt; font-weight: bold; }
        .alamat { font-size: 8.5pt; }
        .judul { text-align: center; font-weight: bold; margin: 16px 0; }
        .judul .main { font-size: 12pt; text-decoration: underline; }
        .meta { text-align: center; margin-bottom: 16px; }
        .section-title { font-weight: bold; margin: 12px 0 5px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.data th, table.data td { border: 1px solid #444; padding: 5px; vertical-align: top; }
        table.data th { text-align: center; background: #f2f2f2; }
        table.clean { width: 100%; border-collapse: collapse; }
        table.clean td { padding: 2px 0; vertical-align: top; }
        .label { width: 175px; }
        .text-justify { text-align: justify; }
        .check { width: 24px; text-align: center; }
        .photo-grid { width: 100%; border-collapse: collapse; }
        .photo-grid td { width: 50%; padding: 5px; text-align: center; vertical-align: top; }
        .photo-grid img { max-width: 100%; max-height: 190px; }
        .photo-caption { font-weight: bold; margin-top: 3px; }
        .photo-type { font-size: 8pt; color: #444; }
        .signature { margin-top: 28px; width: 100%; border-collapse: collapse; }
        .signature td { width: 50%; text-align: center; vertical-align: top; }
        .space-sign { height: 65px; }
        .footer { position: fixed; bottom: -8px; left: 0; right: 0; text-align: center; font-size: 7.5pt; color: #555; }
    </style>
</head>
<body>
    <div class="kop">
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    @if (is_file($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo">
                    @endif
                </td>
                <td class="kop-text">
                    <div class="pemkot">PEMERINTAH KOTA YOGYAKARTA</div>
                    <div class="dinas">DINAS PERTANAHAN DAN TATA RUANG</div>
                    <div class="alamat">Jl. Kenari No. 56, Muja Muju, Umbulharjo, Kota Yogyakarta, Daerah Istimewa Yogyakarta 55165</div>
                    <div class="alamat">Telp. 0274 515865, 0274 515866</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="judul">
        <div class="main">BERITA ACARA PEMERIKSAAN LAPANGAN</div>
        <div>IZIN PERUBAHAN PENGGUNAAN TANAH (IPPT)</div>
    </div>

    <div class="meta">Nomor: <strong>{{ $pemeriksaan->nomor_bap }}</strong> &nbsp; | &nbsp; Versi: <strong>{{ $pemeriksaan->versi ?: 1 }}</strong></div>

    <p class="text-justify">
        Pada hari {{ $pemeriksaan->tanggal_pemeriksaan?->translatedFormat('l') }}, tanggal {{ $pemeriksaan->tanggal_pemeriksaan?->translatedFormat('d F Y') }}, telah dilaksanakan pemeriksaan lapangan terhadap permohonan Izin Perubahan Penggunaan Tanah (IPPT) dengan nomor permohonan <strong>{{ $permohonan->nomor_permohonan }}</strong>.
    </p>

    @if ($pemeriksaan->alasan_pembaruan)
        <p class="text-justify"><strong>Dasar Pembaruan BAP:</strong> {{ $pemeriksaan->alasan_pembaruan }}</p>
    @endif

    <div class="section-title">I. IDENTITAS PEMOHON</div>
    <table class="clean">
        <tr><td class="label">Nama</td><td>: {{ $pemohon->nama }}</td></tr>
        <tr><td class="label">Jenis Pemohon</td><td>: {{ ucfirst($pemohon->jenis_pemohon) }}</td></tr>
        @if ($pemohon->nik)<tr><td class="label">NIK</td><td>: {{ $pemohon->nik }}</td></tr>@endif
        @if ($pemohon->nib)<tr><td class="label">NIB</td><td>: {{ $pemohon->nib }}</td></tr>@endif
        <tr><td class="label">Alamat</td><td>: {{ $pemohon->alamat }}, {{ $pemohon->kelurahan }}, {{ $pemohon->kecamatan }}, {{ $pemohon->kota }}</td></tr>
    </table>

    <div class="section-title">II. IDENTITAS LOKASI TANAH</div>
    <table class="clean">
        <tr><td class="label">Lokasi Permohonan</td><td>: {{ $permohonan->lokasi_tanah }}</td></tr>
        <tr><td class="label">Luas Tanah</td><td>: {{ number_format((float) $permohonan->luas_tanah, 2, ',', '.') }} m²</td></tr>
        <tr><td class="label">Nomor Hak</td><td>: {{ $permohonan->nomor_hak ?: '-' }}</td></tr>
        <tr><td class="label">Penggunaan Saat Ini</td><td>: {{ $permohonan->penggunaan_sekarang }}</td></tr>
        <tr><td class="label">Penggunaan Dimohonkan</td><td>: {{ $permohonan->penggunaan_dimohonkan }}</td></tr>
        <tr><td class="label">Titik Koordinat</td><td>: {{ $pemeriksaan->latitude ?? '-' }}, {{ $pemeriksaan->longitude ?? '-' }}</td></tr>
        <tr><td class="label">Alamat Hasil Cek</td><td>: {{ $pemeriksaan->alamat_lokasi ?: '-' }}</td></tr>
    </table>

    <div class="section-title">III. PELAKSANAAN PEMERIKSAAN</div>
    <table class="clean">
        <tr><td class="label">Tanggal</td><td>: {{ $pemeriksaan->tanggal_pemeriksaan?->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Waktu</td><td>: {{ $pemeriksaan->waktu_mulai ?: '-' }} s.d. {{ $pemeriksaan->waktu_selesai ?: '-' }}</td></tr>
        <tr><td class="label">Petugas/Tim</td><td>: {{ $pemeriksaan->nama_tim ?: $pemeriksaan->dibuatOleh?->name }}</td></tr>
        <tr><td class="label">Cuaca</td><td>: {{ $pemeriksaan->cuaca ?: '-' }}</td></tr>
    </table>

    <div class="section-title">IV. HASIL DAFTAR PEMERIKSAAN</div>
    <table class="data">
        <thead><tr><th style="width: 35px">No.</th><th>Unsur Pemeriksaan</th><th class="check">Terpenuhi</th></tr></thead>
        <tbody>
        @php
            $labels = [
                'identitas_lokasi' => 'Identitas dan lokasi tanah sesuai dengan dokumen permohonan',
                'batas_bidang' => 'Batas-batas bidang tanah dapat diidentifikasi di lapangan',
                'akses_jalan' => 'Akses/jaringan jalan menuju lokasi dapat diidentifikasi',
                'penggunaan_eksisting' => 'Penggunaan tanah eksisting telah dicatat',
                'kondisi_fisik' => 'Kondisi fisik lokasi telah didokumentasikan',
                'denah_lokasi' => 'Denah/site plan sesuai dengan kondisi lapangan',
                'koordinat' => 'Titik koordinat lokasi dapat diverifikasi',
                'lingkungan' => 'Kondisi lingkungan sekitar telah diperiksa',
            ];
            $checklist = $pemeriksaan->checklist ?? [];
        @endphp
        @foreach ($labels as $key => $label)
            <tr><td style="text-align:center">{{ $loop->iteration }}</td><td>{{ $label }}</td><td class="check">{{ in_array($key, $checklist, true) ? '✓' : '—' }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <div class="section-title">V. KONDISI EKSISTING</div>
    <div class="text-justify">{{ $pemeriksaan->kondisi_eksisting }}</div>

    <div class="section-title">VI. TEMUAN PEMERIKSAAN</div>
    <div class="text-justify">{{ $pemeriksaan->temuan }}</div>

    <div class="section-title">VII. KESIMPULAN DAN HASIL</div>
    <div class="text-justify"><strong>{{ $pemeriksaan->hasil?->getLabel() }}</strong> — {{ $pemeriksaan->kesimpulan ?: $pemeriksaan->hasil_pemeriksaan }}</div>

    <div class="section-title">VIII. REKOMENDASI / TINDAK LANJUT</div>
    <div class="text-justify">{{ $pemeriksaan->rekomendasi ?: '-' }}</div>

    @if (count($photos))
        <div class="section-title">IX. DOKUMENTASI LAPANGAN</div>
        <table class="photo-grid">
            @foreach ($photos as $photo)
                @if ($loop->iteration % 2 === 1)<tr>@endif
                <td>
                    <img src="{{ $photo['data_uri'] }}">
                    @if ($photo['jenis'])<div class="photo-type">{{ str($photo['jenis'])->replace('_', ' ')->title() }}</div>@endif
                    <div class="photo-caption">{{ $photo['caption'] }}</div>
                </td>
                @if ($loop->iteration % 2 === 0 || $loop->last)</tr>@endif
            @endforeach
        </table>
    @endif

    <p class="text-justify" style="margin-top: 18px;">
        Berita Acara Pemeriksaan Lapangan ini dibuat berdasarkan hasil pemeriksaan pada lokasi dimaksud dan digunakan sebagai salah satu bahan dalam proses pelayanan Izin Perubahan Penggunaan Tanah (IPPT). Dokumen ini dinyatakan final setelah disahkan oleh petugas yang berwenang dalam sistem.
    </p>

    <table class="signature">
        <tr>
            <td>Petugas Pemeriksa,<div class="space-sign"></div><strong><u>{{ $pemeriksaan->dibuatOleh?->name }}</u></strong></td>
            <td>Mengetahui,<div class="space-sign"></div><strong><u>{{ $pemeriksaan->difinalisasiOleh?->name ?: 'Pejabat/Petugas Berwenang' }}</u></strong></td>
        </tr>
    </table>

    <div class="footer">Dokumen elektronik hasil sistem pelayanan IPPT — {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
