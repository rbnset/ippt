<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekomendasi Teknis - {{ $rekomendasi->nomor_rekomendasi }}</title>
    <style>
        @page { margin: 1.8cm 2cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.45; color: #111; }
        .kop { border-bottom: 3px solid #111; padding-bottom: 7px; margin-bottom: 18px; }
        .kop-table, .clean, .data, .signature { width: 100%; border-collapse: collapse; }
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
        .clean td { padding: 2px 0; vertical-align: top; }
        .label { width: 190px; }
        .text-justify { text-align: justify; white-space: pre-line; }
        .data th, .data td { border: 1px solid #444; padding: 5px; vertical-align: top; }
        .data th { text-align: center; background: #f2f2f2; }
        .signature { margin-top: 35px; }
        .signature td { width: 50%; text-align: center; vertical-align: top; }
        .space-sign { height: 70px; }
        .footer { position: fixed; bottom: -8px; left: 0; right: 0; text-align: center; font-size: 7.5pt; color: #555; }
    </style>
</head>
<body>
    <div class="kop">
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    @if (is_file($logoPath)) <img src="{{ $logoPath }}" alt="Logo"> @endif
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
        <div class="main">REKOMENDASI TEKNIS</div>
        <div>IZIN PERUBAHAN PENGGUNAAN TANAH (IPPT)</div>
    </div>
    <div class="meta">Nomor: <strong>{{ $rekomendasi->nomor_rekomendasi ?: '-' }}</strong></div>

    <div class="section-title">I. IDENTITAS PEMOHON</div>
    <table class="clean">
        <tr><td class="label">Nama</td><td>: {{ $pemohon->nama }}</td></tr>
        <tr><td class="label">Jenis Pemohon</td><td>: {{ ucfirst($pemohon->jenis_pemohon) }}</td></tr>
        @if ($pemohon->nik)<tr><td class="label">NIK</td><td>: {{ $pemohon->nik }}</td></tr>@endif
        @if ($pemohon->nib)<tr><td class="label">NIB</td><td>: {{ $pemohon->nib }}</td></tr>@endif
        <tr><td class="label">Alamat</td><td>: {{ $pemohon->alamat }}, {{ $pemohon->kelurahan }}, {{ $pemohon->kecamatan }}, {{ $pemohon->kota }}</td></tr>
    </table>

    <div class="section-title">II. IDENTITAS PERMOHONAN</div>
    <table class="clean">
        <tr><td class="label">Nomor Permohonan</td><td>: {{ $permohonan->nomor_permohonan }}</td></tr>
        <tr><td class="label">Lokasi Tanah</td><td>: {{ $permohonan->lokasi_tanah }}</td></tr>
        <tr><td class="label">Luas Tanah</td><td>: {{ number_format((float) $permohonan->luas_tanah, 2, ',', '.') }} m²</td></tr>
        <tr><td class="label">Penggunaan Saat Ini</td><td>: {{ $permohonan->penggunaan_sekarang }}</td></tr>
        <tr><td class="label">Penggunaan Dimohonkan</td><td>: {{ $permohonan->penggunaan_dimohonkan }}</td></tr>
        <tr><td class="label">Referensi BAP Lapangan</td><td>: {{ $rekomendasi->nomor_bap_referensi ?: '-' }}</td></tr>
        <tr><td class="label">Tanggal Rekomendasi</td><td>: {{ $rekomendasi->tanggal_rekomendasi?->format('d/m/Y') ?: '-' }}</td></tr>
    </table>

    <div class="section-title">III. DASAR / RUJUKAN</div>
    <div class="text-justify">{{ $rekomendasi->dasar_hukum ?: '-' }}</div>

    <div class="section-title">IV. PERTIMBANGAN TEKNIS</div>
    <div class="text-justify">{{ $rekomendasi->pertimbangan ?: '-' }}</div>

    <div class="section-title">V. KESESUAIAN TATA RUANG</div>
    <div class="text-justify">{{ $rekomendasi->kesesuaian_tata_ruang ?: '-' }}</div>

    <div class="section-title">VI. ARAHAN TEKNIS</div>
    <div class="text-justify">{{ $rekomendasi->arahan_teknis ?: '-' }}</div>

    <div class="section-title">VII. HASIL REKOMENDASI</div>
    <table class="data">
        <tr><th style="width: 180px">Hasil</th><td><strong>{{ $rekomendasi->hasil?->getLabel() ?: '-' }}</strong></td></tr>
        <tr><th>Ketentuan / Catatan Teknis</th><td class="text-justify">{{ $rekomendasi->ketentuan ?: '-' }}</td></tr>
    </table>

    @if ($rekomendasi->status?->value === 'ditolak' && $rekomendasi->catatan_review)
        <div class="section-title">VIII. CATATAN REVIEW</div>
        <div class="text-justify">{{ $rekomendasi->catatan_review }}</div>
    @endif

    <table class="signature">
        <tr>
            <td>Disusun oleh,<div class="space-sign"></div><strong><u>{{ $rekomendasi->disusunOleh?->name ?: '-' }}</u></strong></td>
            <td>Disetujui oleh,<div class="space-sign"></div><strong><u>{{ $rekomendasi->disetujuiOleh?->name ?: 'Pejabat Berwenang' }}</u></strong></td>
        </tr>
    </table>

    <div class="footer">Dokumen elektronik hasil sistem pelayanan IPPT — {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
