<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal layanan digital Izin Perubahan Penggunaan Tanah (IPPT) Kota Yogyakarta.">
    <meta name="theme-color" content="#f8fafc">
    <title>IPPT Kota Yogyakarta — Portal Layanan</title>

    <script>
        (() => {
            try {
                const mode = localStorage.getItem('theme') || 'system';
                const dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.dataset.themeMode = mode;
            } catch (_) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-slate-950 focus:px-4 focus:py-3 focus:text-white">
        Lewati ke konten utama
    </a>

    <div class="border-b border-slate-200 bg-white text-xs text-slate-600 dark:border-white/10 dark:bg-slate-900 dark:text-slate-300">
        <div class="mx-auto flex min-h-9 max-w-7xl items-center justify-between gap-4 px-5 sm:px-8 lg:px-10">
            <p>Pemerintah Kota Yogyakarta</p>
            <p class="hidden sm:block">Dinas Pertanahan dan Tata Ruang</p>
        </div>
    </div>

    <header class="sticky top-0 z-50 border-b border-slate-200/90 bg-white/95 backdrop-blur dark:border-white/10 dark:bg-slate-950/95">
        <div class="mx-auto flex min-h-[76px] max-w-7xl items-center justify-between gap-5 px-5 sm:px-8 lg:px-10">
            <a href="#beranda" class="flex min-w-0 items-center gap-3" aria-label="Beranda IPPT Kota Yogyakarta">
                <span class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-white/10 dark:bg-slate-900">
                    <img src="{{ file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : asset('image/logo.png') }}" alt="Logo Pemerintah Kota Yogyakarta" class="max-h-10 max-w-10 object-contain">
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold tracking-tight">Portal Layanan IPPT</span>
                    <span class="block truncate text-xs text-slate-500 dark:text-slate-400">Kota Yogyakarta</span>
                </span>
            </a>

            <nav class="hidden items-center gap-7 text-sm font-semibold lg:flex" aria-label="Navigasi utama">
                <a href="#tentang" class="text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white">Tentang IPPT</a>
                <a href="#panduan" class="text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white">Panduan</a>
                <a href="#alur" class="text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white">Alur</a>
                <a href="#persyaratan" class="text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white">Persyaratan</a>
                <a href="#kontak" class="text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white">Kontak</a>
            </nav>

            <div class="flex items-center gap-2">
                <button type="button" data-theme-cycle class="grid size-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-amber-400 hover:bg-slate-50 dark:border-white/10 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-amber-400 dark:hover:bg-slate-800" aria-label="Ganti tema terang dan gelap" title="Ganti tema">
                    <span data-theme-toggle-icon class="block size-5" aria-hidden="true"></span>
                </button>
                <a href="{{ route('filament.admin.auth.login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 sm:inline-flex dark:hover:bg-white/5">Masuk</a>
                <a href="{{ route('filament.admin.auth.register') }}" class="inline-flex rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700 dark:bg-amber-400 dark:text-slate-950 dark:hover:bg-amber-300">Daftar</a>
            </div>
        </div>
    </header>

    <main id="konten">
        <section id="beranda" class="border-b border-slate-200 bg-white dark:border-white/10 dark:bg-slate-950">
            <div class="mx-auto grid max-w-7xl gap-12 px-5 py-14 sm:px-8 sm:py-20 lg:grid-cols-[1.2fr_.8fr] lg:px-10 lg:py-24">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold text-amber-700 dark:text-amber-300">LAYANAN PERTANAHAN • KOTA YOGYAKARTA</p>
                    <h1 class="mt-4 text-4xl font-extrabold tracking-[-0.03em] sm:text-5xl lg:text-[58px] lg:leading-[1.08]">Ajukan IPPT dengan mengetahui apa yang harus disiapkan dan apa yang terjadi berikutnya.</h1>
                    <p class="mt-6 max-w-2xl text-base leading-8 text-slate-600 dark:text-slate-300 sm:text-lg">Portal ini membantu pemohon mengajukan Izin Perubahan Penggunaan Tanah, melengkapi dokumen, menerima arahan perbaikan, dan memantau tahapan pemeriksaan sampai keputusan.</p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('filament.admin.auth.register') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-3.5 text-sm font-bold text-white hover:bg-slate-700 dark:bg-amber-400 dark:text-slate-950 dark:hover:bg-amber-300">Buat Akun Pemohon</a>
                        <a href="#panduan" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3.5 text-sm font-bold hover:bg-slate-50 dark:border-white/15 dark:bg-slate-900 dark:hover:bg-white/5">Baca Panduan Pengajuan</a>
                    </div>
                    <div class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-xs text-slate-500 dark:text-slate-400">
                        <span>✓ Status dapat dipantau</span>
                        <span>✓ Dokumen tersimpan dalam riwayat</span>
                        <span>✓ Pemberitahuan proses</span>
                    </div>
                </div>

                <aside class="self-start border border-slate-200 bg-slate-50 p-6 dark:border-white/10 dark:bg-slate-900 lg:mt-4">
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-slate-500 dark:text-slate-400">Sebelum mengajukan</p>
                    <h2 class="mt-3 text-xl font-extrabold">Siapkan tiga hal ini</h2>
                    <ol class="mt-5 divide-y divide-slate-200 dark:divide-white/10">
                        <li class="flex gap-4 py-4"><span class="font-mono text-sm font-bold text-amber-700 dark:text-amber-300">01</span><div><p class="font-bold">Identitas pemohon</p><p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">Nama, kontak, dan identitas sesuai jenis pemohon.</p></div></li>
                        <li class="flex gap-4 py-4"><span class="font-mono text-sm font-bold text-amber-700 dark:text-amber-300">02</span><div><p class="font-bold">Data bidang tanah</p><p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">Lokasi, alas hak, dan data pendukung bidang.</p></div></li>
                        <li class="flex gap-4 py-4"><span class="font-mono text-sm font-bold text-amber-700 dark:text-amber-300">03</span><div><p class="font-bold">Dokumen persyaratan</p><p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">Siapkan file yang jelas agar proses verifikasi tidak tertunda.</p></div></li>
                    </ol>
                    <a href="#persyaratan" class="mt-3 inline-flex text-sm font-bold text-amber-700 hover:text-amber-800 dark:text-amber-300">Lihat panduan persyaratan →</a>
                </aside>
            </div>
        </section>

        <section id="tentang" class="bg-slate-50 py-16 dark:bg-slate-900/60 sm:py-20">
            <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                <div class="grid gap-10 lg:grid-cols-[.7fr_1.3fr]">
                    <div><p class="text-xs font-bold uppercase tracking-[.18em] text-amber-700 dark:text-amber-300">Tentang layanan</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">IPPT itu apa?</h2></div>
                    <div class="max-w-3xl text-base leading-8 text-slate-600 dark:text-slate-300">
                        <p>Izin Perubahan Penggunaan Tanah (IPPT) merupakan layanan yang berkaitan dengan perubahan penggunaan tanah pertanian menjadi penggunaan non-pertanian. Portal ini bukan sekadar formulir online: pemohon mendapatkan satu tempat untuk mengikuti tahapan administrasi, dokumen, pemeriksaan lapangan, rekomendasi teknis, risalah pertimbangan teknis, dan keputusan.</p>
                        <p class="mt-5">Dinas Pertanahan dan Tata Ruang Kota Yogyakarta mempunyai tugas di bidang pertanahan dan penataan ruang. Informasi kelembagaan dan layanan resmi dapat dilihat pada situs Dinas Pertanahan dan Tata Ruang Kota Yogyakarta.</p>
                        <a href="https://dinpertaru.jogjakota.go.id/" target="_blank" rel="noopener" class="mt-5 inline-flex font-bold text-amber-700 hover:underline dark:text-amber-300">Kunjungi situs Dinas Pertanahan dan Tata Ruang →</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="panduan" class="border-y border-slate-200 bg-white py-16 dark:border-white/10 dark:bg-slate-950 sm:py-20">
            <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                <div class="max-w-3xl"><p class="text-xs font-bold uppercase tracking-[.18em] text-amber-700 dark:text-amber-300">Panduan pengisian</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Mulai dari mana? Ikuti urutan ini.</h2><p class="mt-4 leading-7 text-slate-600 dark:text-slate-300">Jika baru pertama kali menggunakan sistem, tidak perlu menghafal seluruh proses. Kerjakan satu tahap sampai selesai, lalu ikuti arahan yang muncul pada permohonan.</p></div>
                <div class="mt-10 grid gap-0 border-y border-slate-200 dark:border-white/10 lg:grid-cols-6 lg:border-x">
                    @foreach ([['1','Buat akun','Daftar sebagai pemohon. Pastikan email dan nomor telepon aktif.'],['2','Isi identitas','Lengkapi jenis pemohon dan data identitas.'],['3','Buat permohonan','Isi data tanah dan rencana penggunaan tanah sesuai kondisi sebenarnya.'],['4','Unggah dokumen','Masukkan dokumen pada jenis persyaratan yang sesuai.'],['5','Kirim','Periksa kembali data sebelum permohonan dikirim untuk diverifikasi.'],['6','Pantau','Ikuti status, catatan perbaikan, pemeriksaan, dan keputusan dari dashboard.']] as $item)
                        <article class="border-b border-slate-200 p-5 last:border-b-0 dark:border-white/10 lg:border-b-0 lg:border-r lg:last:border-r-0">
                            <span class="font-mono text-xs font-bold text-amber-700 dark:text-amber-300">LANGKAH {{ $item[0] }}</span>
                            <h3 class="mt-4 text-base font-extrabold">{{ $item[1] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $item[2] }}</p>
                        </article>
                    @endforeach
                </div>
                <div class="mt-6 border-l-4 border-amber-500 bg-amber-50 px-5 py-4 text-sm leading-7 text-amber-950 dark:bg-amber-400/10 dark:text-amber-100"><strong>Tips:</strong> jangan mengunggah dokumen yang buram, terpotong, atau salah jenis. Bila petugas mengembalikan dokumen, baca catatan/arahan terlebih dahulu lalu gunakan fitur unggah ulang pada dokumen yang ditolak.</div>
            </div>
        </section>

        <section id="alur" class="bg-slate-50 py-16 dark:bg-slate-900/60 sm:py-20">
            <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                <div class="max-w-3xl"><p class="text-xs font-bold uppercase tracking-[.18em] text-amber-700 dark:text-amber-300">Alur proses</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Setelah permohonan dikirim, apa yang terjadi?</h2></div>
                <div class="mt-10 grid gap-px overflow-hidden border border-slate-200 bg-slate-200 dark:border-white/10 dark:bg-white/10 md:grid-cols-2 lg:grid-cols-5">
                    @foreach ([['01','Verifikasi dokumen','Petugas memeriksa kelengkapan dan kesesuaian dokumen. Jika perlu perbaikan, Anda mendapat catatan.'],['02','Pemeriksaan lapangan','Tim teknis melakukan pemeriksaan lapangan dan menyusun BAP.'],['03','Rekomendasi teknis','Hasil pemeriksaan menjadi dasar penyusunan rekomendasi teknis.'],['04','Risalah pertimbangan','Risalah pertimbangan teknis diterima sebagai bagian dari dasar proses keputusan.'],['05','Keputusan IPPT','Keputusan ditetapkan setelah dasar dan tahapan yang diperlukan terpenuhi.']] as $item)
                        <article class="bg-white p-5 dark:bg-slate-950"><span class="font-mono text-xs font-bold text-amber-700 dark:text-amber-300">{{ $item[0] }}</span><h3 class="mt-5 font-extrabold">{{ $item[1] }}</h3><p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $item[2] }}</p></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="persyaratan" class="bg-white py-16 dark:bg-slate-950 sm:py-20">
            <div class="mx-auto grid max-w-7xl gap-10 px-5 sm:px-8 lg:grid-cols-[.75fr_1.25fr] lg:px-10">
                <div><p class="text-xs font-bold uppercase tracking-[.18em] text-amber-700 dark:text-amber-300">Persiapan dokumen</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Apa yang sebaiknya disiapkan?</h2><p class="mt-5 leading-7 text-slate-600 dark:text-slate-300">Daftar di bawah adalah panduan awal. Dokumen yang benar-benar diperlukan dapat bergantung pada kondisi permohonan dan hasil pemeriksaan.</p></div>
                <div class="grid gap-x-8 gap-y-0 sm:grid-cols-2">
                    @foreach (['Identitas pemohon dan kontak aktif','Bukti hak atau dokumen penguasaan tanah','Data lokasi dan bidang tanah','Dokumen/denah/peta lokasi sesuai kebutuhan','Dokumen pendukung yang diminta sesuai kondisi tanah','Dokumen teknis atau dokumen instansi terkait bila diperlukan'] as $i => $item)
                        <div class="flex gap-4 border-b border-slate-200 py-4 dark:border-white/10"><span class="font-mono text-xs font-bold text-slate-400">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span><p class="text-sm font-semibold leading-6">{{ $item }}</p></div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="kontak" class="border-t border-slate-200 bg-slate-50 py-16 dark:border-white/10 dark:bg-slate-900/60 sm:py-20">
            <div class="mx-auto grid max-w-7xl gap-10 px-5 sm:px-8 lg:grid-cols-[1fr_.9fr] lg:px-10">
                <div><p class="text-xs font-bold uppercase tracking-[.18em] text-amber-700 dark:text-amber-300">Lokasi & informasi</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Dinas Pertanahan dan Tata Ruang Kota Yogyakarta</h2><p class="mt-5 max-w-2xl leading-7 text-slate-600 dark:text-slate-300">Gunakan informasi berikut sebagai rujukan lokasi. Untuk informasi kelembagaan dan publikasi terbaru, kunjungi situs resmi Dinas Pertanahan dan Tata Ruang.</p>
                    <div class="mt-7 border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-slate-950"><p class="font-bold">Jl. Kenari No. 56, Muja Muju, Umbulharjo</p><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kota Yogyakarta, Daerah Istimewa Yogyakarta 55165</p><p class="mt-4 text-sm text-slate-600 dark:text-slate-300">Telp. 0274 515865 / 0274 515866</p><div class="mt-5 flex flex-wrap gap-3"><a target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=Jl.%20Kenari%20No.%2056%20Yogyakarta" class="inline-flex rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700 dark:bg-amber-400 dark:text-slate-950">Buka Google Maps</a><a target="_blank" rel="noopener" href="https://dinpertaru.jogjakota.go.id/" class="inline-flex rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-bold hover:bg-white dark:border-white/15 dark:hover:bg-white/5">Situs Dinas</a></div></div>
                </div>
                <div class="border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-slate-950"><p class="text-xs font-bold uppercase tracking-[.18em] text-slate-500 dark:text-slate-400">Ringkasannya</p><dl class="mt-5 divide-y divide-slate-200 dark:divide-white/10"><div class="py-4"><dt class="text-xs text-slate-500 dark:text-slate-400">Layanan</dt><dd class="mt-1 font-bold">Izin Perubahan Penggunaan Tanah</dd></div><div class="py-4"><dt class="text-xs text-slate-500 dark:text-slate-400">Wilayah</dt><dd class="mt-1 font-bold">Kota Yogyakarta</dd></div><div class="py-4"><dt class="text-xs text-slate-500 dark:text-slate-400">Cara memulai</dt><dd class="mt-1 font-bold">Daftar akun → isi permohonan → unggah dokumen</dd></div><div class="py-4"><dt class="text-xs text-slate-500 dark:text-slate-400">Pemantauan</dt><dd class="mt-1 font-bold">Melalui dashboard akun pemohon</dd></div></dl></div>
            </div>
        </section>

        <section class="border-t border-slate-200 bg-slate-950 py-12 text-white dark:border-white/10 dark:bg-black">
            <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 sm:px-8 lg:flex-row lg:items-center lg:justify-between lg:px-10">
                <div><p class="text-xs font-bold uppercase tracking-[.18em] text-amber-300">Mulai pengajuan</p><h2 class="mt-2 text-2xl font-extrabold">Sudah siap? Buat akun pemohon terlebih dahulu.</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">Setelah akun dibuat, Anda dapat masuk ke panel pemohon dan mengikuti panduan pengisian permohonan.</p></div>
                <a href="{{ route('filament.admin.auth.register') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-amber-400 px-5 py-3.5 text-sm font-extrabold text-slate-950 hover:bg-amber-300">Daftar Akun Pemohon</a>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white py-7 dark:border-white/10 dark:bg-slate-950">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 text-xs text-slate-500 dark:text-slate-400 sm:px-8 md:flex-row md:items-center md:justify-between lg:px-10">
            <p>© {{ date('Y') }} Portal Layanan IPPT Kota Yogyakarta.</p>
            <div class="flex flex-wrap gap-x-5 gap-y-2"><a href="https://dinpertaru.jogjakota.go.id/" target="_blank" rel="noopener" class="hover:text-slate-900 dark:hover:text-white">Dinas Pertanahan dan Tata Ruang</a><a href="https://jdih.jogjakota.go.id/" target="_blank" rel="noopener" class="hover:text-slate-900 dark:hover:text-white">JDIH Kota Yogyakarta</a></div>
        </div>
    </footer>
</body>
</html>
