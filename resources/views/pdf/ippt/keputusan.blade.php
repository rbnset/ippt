<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>
        Keputusan IPPT - {{ $keputusan->nomor_keputusan }}
    </title>

    <style>
        @page {
            margin: 2cm 2.5cm 2cm 2.5cm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .kop {
            margin-bottom: 20px;
        }

        .kop-instansi {
            text-align: center;
        }

        .nama-pemerintah {
            font-size: 14pt;
            font-weight: bold;
        }

        .nama-dinas {
            font-size: 15pt;
            font-weight: bold;
        }

        .alamat {
            font-size: 10pt;
        }

        .garis-kop {
            border-bottom: 3px solid #000;
            margin-top: 8px;
        }

        .judul {
            text-align: center;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 20px;
        }

        .judul div {
            margin-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            vertical-align: top;
            padding: 3px 0;
        }

        .label {
            width: 180px;
        }

        .isi {
            width: auto;
        }

        .nomor {
            margin-bottom: 20px;
        }

        .paragraf {
            text-align: justify;
            margin-bottom: 12px;
        }

        .menimbang td,
        .mengingat td {
            padding-bottom: 7px;
        }

        .signature {
            margin-top: 40px;
            width: 100%;
        }

        .signature td {
            text-align: center;
            vertical-align: top;
        }

        .nama-pejabat {
            margin-top: 70px;
            font-weight: bold;
            text-decoration: underline;
        }

        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
        }
    </style>
</head>

<body>

    {{-- KOP SURAT --}}
    @include('pdf.ippt.kop')

    {{-- JUDUL --}}
    <div class="judul">
        <div>KEPUTUSAN</div>
        <div>
            NOMOR:
            {{ $keputusan->nomor_keputusan }}
        </div>

        <div>
            TENTANG
        </div>

        <div>
            IZIN PERUBAHAN PENGGUNAAN TANAH
        </div>
    </div>

    {{-- PEMBUKA --}}
    <div class="paragraf">
        Dengan rahmat Tuhan Yang Maha Esa, berdasarkan hasil pemeriksaan
        dan pertimbangan terhadap permohonan Izin Perubahan Penggunaan Tanah,
        ditetapkan keputusan sebagai berikut:
    </div>

    {{-- DATA PEMOHON --}}
    <table>
        <tr>
            <td class="label">Nama Pemohon</td>
            <td class="isi">
                : {{ $pemohon->nama }}
            </td>
        </tr>

        <tr>
            <td class="label">Jenis Pemohon</td>
            <td class="isi">
                : {{ ucfirst($pemohon->jenis_pemohon) }}
            </td>
        </tr>

        @if ($pemohon->jenis_pemohon === 'perorangan')
            <tr>
                <td class="label">NIK</td>
                <td class="isi">
                    : {{ $pemohon->nik }}
                </td>
            </tr>
        @endif

        @if ($pemohon->jenis_pemohon === 'badan')
            <tr>
                <td class="label">NIB</td>
                <td class="isi">
                    : {{ $pemohon->nib }}
                </td>
            </tr>
        @endif

        <tr>
            <td class="label">Alamat</td>
            <td class="isi">
                : {{ $pemohon->alamat }}
            </td>
        </tr>
    </table>

    <br>

    {{-- DATA TANAH --}}
    <table>
        <tr>
            <td class="label">Lokasi Tanah</td>
            <td class="isi">
                : {{ $permohonan->lokasi_tanah }}
            </td>
        </tr>

        <tr>
            <td class="label">Luas Tanah</td>
            <td class="isi">
                : {{ number_format($permohonan->luas_tanah, 2, ',', '.') }}
                m²
            </td>
        </tr>

        <tr>
            <td class="label">Nomor Hak</td>
            <td class="isi">
                : {{ $permohonan->nomor_hak ?? '-' }}
            </td>
        </tr>

        <tr>
            <td class="label">Penggunaan Sekarang</td>
            <td class="isi">
                : {{ $permohonan->penggunaan_sekarang }}
            </td>
        </tr>

        <tr>
            <td class="label">Penggunaan Dimohonkan</td>
            <td class="isi">
                : {{ $permohonan->penggunaan_dimohonkan }}
            </td>
        </tr>
    </table>

    <br>

    {{-- MENETAPKAN --}}
    <div class="paragraf">
        <strong>MENETAPKAN:</strong>
    </div>

    <div class="paragraf">
        <strong>PERTAMA:</strong>
        Memberikan {{ $keputusan->jenis_keputusan === 'terbit'
            ? 'Izin Perubahan Penggunaan Tanah'
            : 'penolakan terhadap permohonan Izin Perubahan Penggunaan Tanah'
        }}
        kepada:
    </div>

    <table>
        <tr>
            <td class="label">Nama</td>
            <td>: {{ $pemohon->nama }}</td>
        </tr>

        <tr>
            <td class="label">Lokasi</td>
            <td>: {{ $permohonan->lokasi_tanah }}</td>
        </tr>

        <tr>
            <td class="label">Luas</td>
            <td>
                :
                {{ number_format($permohonan->luas_tanah, 2, ',', '.') }}
                m²
            </td>
        </tr>
    </table>

    @if ($keputusan->jenis_keputusan === 'terbit')

        <div class="paragraf">
            <strong>KEDUA:</strong>
            Perubahan penggunaan tanah dilaksanakan sesuai dengan
            ketentuan peraturan perundang-undangan yang berlaku.
        </div>

        <div class="paragraf">
            <strong>KETIGA:</strong>
            Izin Perubahan Penggunaan Tanah berlaku sesuai dengan
            ketentuan yang berlaku.
        </div>

    @else

        <div class="paragraf">
            <strong>KEDUA:</strong>
            Permohonan Izin Perubahan Penggunaan Tanah ditolak
            dengan pertimbangan:
        </div>

        <div class="paragraf">
            {{ $keputusan->alasan ?? '-' }}
        </div>

    @endif

    {{-- TANGGAL --}}
    <table class="signature">
        <tr>
            <td></td>
            <td>
                Yogyakarta,
                {{ $keputusan->tanggal_keputusan?->translatedFormat('d F Y') }}
            </td>
        </tr>

        <tr>
            <td></td>
            <td>
                Pejabat yang Berwenang
            </td>
        </tr>

        <tr>
            <td></td>
            <td class="nama-pejabat">
                {{ $keputusan->disetujuiOleh?->name ?? '-' }}
            </td>
        </tr>
    </table>

    <div class="footer">
        Dokumen diterbitkan secara elektronik melalui Sistem Informasi IPPT.
    </div>

</body>
</html>
