<x-filament-panels::page>
    @php
        $user = auth()->user();
        $isPemohon = $user?->hasRole(\App\Enums\UserRole::PEMOHON);
        $pemohon = $isPemohon ? $this->getPemohonRecord() : null;
        $stats = $isPemohon ? $this->getPermohonanStats() : [];
        $status = $pemohon?->status_verifikasi;
        $canEdit = $pemohon ? $pemohon->isEditableByPemohon() : true;
        $avatar = $user?->avatar_url ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_url) : null;
    @endphp

    @php
        $isBackoffice = $user?->hasAnyRole([\App\Enums\UserRole::ADMIN, \App\Enums\UserRole::STAFF, \App\Enums\UserRole::TIM_TEKNIS, \App\Enums\UserRole::KABID, \App\Enums\UserRole::KADIS]);
        $backoffice = $isBackoffice ? $this->getBackofficeDashboard() : [];
    @endphp

    @if($isBackoffice)
        <div class="space-y-6">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid gap-6 px-6 py-7 sm:px-8 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ $backoffice['role_label'] }}</p>
                        <h2 class="mt-2 max-w-3xl text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">{{ $backoffice['headline'] }}</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $backoffice['greeting'] }}, {{ $user->name }}. {{ $backoffice['description'] }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ $backoffice['links']['permohonan'] }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                            <x-heroicon-o-document-text class="size-4" /> Permohonan IPPT
                        </a>
                        @if($user?->hasAnyRole([\App\Enums\UserRole::ADMIN, \App\Enums\UserRole::STAFF]))
                            <a href="{{ $backoffice['links']['pemohon'] }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-800 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:hover:bg-slate-900">
                                <x-heroicon-o-users class="size-4" /> Pemohon
                            </a>
                        @endif
                    </div>
                </div>
            </section>

            @if(($backoffice['notifications'] ?? 0) > 0)
                <section class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 dark:border-amber-400/20 dark:bg-amber-400/5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm dark:bg-slate-950 dark:text-amber-300"><x-heroicon-o-bell-alert class="size-5" /></span>
                        <div><p class="text-sm font-black text-amber-900 dark:text-amber-200">{{ $backoffice['notifications'] }} notifikasi belum dibaca</p><p class="mt-0.5 text-xs leading-5 text-amber-800 dark:text-amber-300">Gunakan lonceng Filament pada topbar untuk melihat pemberitahuan dan membuka tindakan terkait.</p></div>
                    </div>
                </section>
            @endif

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($backoffice['stats'] as $stat)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-4">
                            <div><p class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $stat['label'] }}</p><p class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ number_format($stat['value'], 0, ',', '.') }}</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $stat['description'] }}</p></div>
                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"><x-dynamic-component :component="$stat['icon']" class="size-5" /></span>
                        </div>
                    </div>
                @endforeach
            </section>

            <section class="rounded-2xl border border-amber-200 bg-amber-50/70 p-6 dark:border-amber-400/20 dark:bg-amber-400/5 sm:p-7">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-amber-700 dark:text-amber-300">Prioritas kerja</p><h3 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Yang perlu dikerjakan sekarang</h3></div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Jumlah menunjukkan antrean yang perlu perhatian.</p>
                </div>
                <div class="mt-5 grid gap-3 lg:grid-cols-2">
                    @forelse($backoffice['tasks'] as $task)
                        <a href="{{ $task['url'] }}" class="group flex min-h-24 items-start gap-4 rounded-2xl border border-slate-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-amber-400 hover:shadow-sm dark:border-slate-700 dark:bg-slate-950">
                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200"><x-dynamic-component :component="$task['icon']" class="size-5" /></span>
                            <span class="min-w-0 flex-1"><span class="flex items-center justify-between gap-3"><strong class="text-sm font-black text-slate-950 dark:text-white">{{ $task['title'] }}</strong><span class="inline-flex min-w-8 justify-center rounded-full bg-amber-100 px-2 py-1 text-xs font-black text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">{{ number_format($task['count'], 0, ',', '.') }}</span></span><span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $task['description'] }}</span></span>
                            <span class="pt-1 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-amber-600">→</span>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-6 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">Tidak ada antrean tindakan khusus saat ini.</div>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Aktivitas permohonan</p><h3 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Permohonan terbaru dalam jangkauan Anda</h3></div>
                    <a href="{{ $backoffice['links']['permohonan'] }}" class="text-sm font-bold text-amber-700 hover:text-amber-800 dark:text-amber-300 dark:hover:text-amber-200">Lihat semua →</a>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($backoffice['recent'] as $item)
                        <a href="{{ \App\Filament\Resources\Permohonans\PermohonanResource::getUrl('view', ['record' => $item['id']]) }}" class="flex flex-col gap-3 px-6 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/60 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                            <div class="min-w-0"><p class="truncate text-sm font-black text-slate-950 dark:text-white">{{ $item['nomor'] }}</p><p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $item['pemohon'] }} · {{ $item['tanggal'] }}</p></div>
                            <span class="inline-flex w-fit items-center rounded-full border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200">{{ $item['status'] }}</span>
                        </a>
                    @empty
                        <div class="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada permohonan pada jangkauan akun ini.</div>
                    @endforelse
                </div>
            </section>
        </div>
    @elseif($isPemohon)
        <div class="space-y-6">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-5 px-6 py-6 sm:px-8 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        @if($avatar)
                            <img src="{{ $avatar }}" alt="Foto profil {{ $user->name }}" class="size-14 rounded-2xl object-cover ring-1 ring-slate-200 dark:ring-slate-700">
                        @else
                            <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-lg font-black text-white dark:bg-white dark:text-slate-950">{{ strtoupper(substr($user->name ?? 'P', 0, 1)) }}</div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-600 dark:text-amber-400">Portal Pemohon IPPT</p>
                            <h2 class="mt-1 truncate text-2xl font-black tracking-tight text-slate-950 dark:text-white">Selamat datang, {{ $user->name }}.</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Pantau data pemohon dan seluruh proses pengajuan IPPT dari satu tempat.</p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        @if($status === 'terverifikasi')
                            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20"><span class="size-1.5 rounded-full bg-emerald-500"></span>Data terverifikasi</span>
                        @elseif($status === 'perlu_perbaikan')
                            <span class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3.5 py-2 text-xs font-bold text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/20"><span class="size-1.5 rounded-full bg-rose-500"></span>Perlu perbaikan</span>
                        @elseif($status === 'menunggu_perubahan')
                            <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20"><span class="size-1.5 rounded-full bg-amber-500"></span>Menunggu izin perubahan</span>
                        @elseif($pemohon && blank($status))
                            <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20"><span class="size-1.5 rounded-full bg-amber-500"></span>Belum diajukan</span>
                        @else
                            <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20"><span class="size-1.5 rounded-full bg-amber-500"></span>Data belum lengkap</span>
                        @endif
                    </div>
                </div>
            </section>

            @if($pemohon)
                @php($progressItems = $this->getPemohonProgress())
                @if(count($progressItems))
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800 sm:px-8"><div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.14em] text-amber-600 dark:text-amber-400">Progress pengajuan</p><h3 class="mt-1 text-xl font-black text-slate-950 dark:text-white">3 permohonan terbaru</h3><p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Pantau posisi proses dan langkah berikutnya tanpa harus membuka setiap dokumen.</p></div><span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Dari {{ $stats['total'] }} pengajuan</span></div></div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($progressItems as $item)
                                <a href="{{ \App\Filament\Resources\Permohonans\PermohonanResource::getUrl('view', ['record' => $item['id']]) }}" class="block px-6 py-5 transition hover:bg-slate-50 dark:hover:bg-slate-950/60 sm:px-8">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2"><p class="truncate text-sm font-black text-slate-950 dark:text-white">{{ $item['nomor'] }}</p><span class="text-xs text-slate-400">{{ $item['tanggal'] }}</span></div>
                                            <p class="mt-2 text-base font-black text-amber-700 dark:text-amber-300">{{ $item['label'] }}</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $item['detail'] }}</p>
                                            <p class="mt-2 text-xs font-bold text-slate-500 dark:text-slate-400">Langkah berikutnya: <span class="text-slate-700 dark:text-slate-200">{{ $item['action'] }}</span></p>
                                        </div>
                                        <span class="inline-flex w-fit shrink-0 items-center rounded-full border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200">{{ $item['status'] }}</span>
                                    </div>
                                    <div class="mt-5 flex gap-1.5" aria-label="Tahap {{ $item['step'] }} dari 8">@for($step = 1; $step <= 8; $step++)<span class="h-1.5 flex-1 rounded-full {{ $step <= max(1, $item['step']) ? 'bg-amber-500' : 'bg-slate-200 dark:bg-slate-800' }}"></span>@endfor</div>
                                    <div class="mt-2 flex justify-between text-[10px] font-semibold uppercase tracking-wide text-slate-400"><span>Pengajuan</span><span>Verifikasi</span><span>Teknis</span><span>Rekomendasi</span><span>Risalah</span><span>Keputusan</span><span>Selesai</span></div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach([
                        ['label'=>'Total pengajuan','value'=>$stats['total'],'icon'=>'document-text'],
                        ['label'=>'Sedang diproses','value'=>$stats['diproses'],'icon'=>'clock'],
                        ['label'=>'IPPT diterbitkan','value'=>$stats['selesai'],'icon'=>'check-badge'],
                        ['label'=>'Ditolak','value'=>$stats['ditolak'],'icon'=>'x-circle'],
                    ] as $stat)
                        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-3"><p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $stat['label'] }}</p><span class="text-slate-400"><x-dynamic-component :component="'heroicon-o-'.$stat['icon']" class="size-5" /></span></div>
                            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $stat['value'] }}</p>
                        </div>
                    @endforeach
                </section>
            @endif

            @if($pemohon && blank($status))
                <section class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/70 shadow-sm dark:border-amber-400/20 dark:bg-amber-400/5">
                    <div class="px-6 py-7 sm:px-8">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                            <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300"><x-heroicon-o-pencil-square class="size-6" /></div>
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-amber-700 dark:text-amber-300">Langkah pertama</p>
                                <h3 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Lengkapi data pemohon terlebih dahulu</h3>
                                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-700 dark:text-slate-300">Akun Anda sudah aktif, tetapi data pemohon belum diajukan. Lengkapi formulir di bawah lalu pilih <strong>Simpan &amp; Kirim untuk Verifikasi</strong>. Status baru akan menjadi <strong>Menunggu Verifikasi</strong> setelah Anda benar-benar mengirim data.</p>
                            </div>
                        </div>
                    </div>
                </section>
            @elseif($pemohon && $status === 'menunggu_verifikasi')
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="px-6 py-8 text-center sm:px-8">
                        <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"><x-heroicon-o-clipboard-document-check class="size-6" /></div>
                        <h3 class="mt-4 text-xl font-black text-slate-950 dark:text-white">Data sedang ditinjau petugas</h3>
                        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600 dark:text-slate-300">Form data pemohon sementara ditutup agar data yang sedang diverifikasi tidak berubah. Anda akan menerima notifikasi setelah pemeriksaan selesai.</p>
                        <div class="mx-auto mt-6 grid max-w-lg gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-left dark:border-slate-800 dark:bg-slate-950"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor antrian</p><p class="mt-1 text-lg font-black text-slate-950 dark:text-white">{{ $pemohon->nomor_antrian }}</p></div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-left dark:border-slate-800 dark:bg-slate-950"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Diajukan</p><p class="mt-1 text-sm font-bold text-slate-950 dark:text-white">{{ $pemohon->diajukan_pada?->format('d M Y, H:i') }}</p></div>
                        </div>
                    </div>
                </section>
            @elseif($pemohon && $status === 'menunggu_perubahan')
                <section class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-6 dark:border-amber-400/20 dark:bg-amber-400/5 sm:px-8">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-amber-700 shadow-sm dark:bg-slate-950 dark:text-amber-300"><x-heroicon-o-clock class="size-5" /></span>
                        <div><p class="text-sm font-black text-amber-900 dark:text-amber-200">Menunggu izin perubahan data</p><p class="mt-1 text-sm leading-6 text-amber-800 dark:text-amber-300">Permintaan perubahan sudah diterima petugas, tetapi formulir belum dibuka. Anda akan menerima notifikasi jika Admin/Staff mengizinkan perubahan.</p><p class="mt-3 text-xs leading-5 text-amber-800/80 dark:text-amber-300/80"><strong>Alasan:</strong> {{ $pemohon->alasan_perubahan ?: '—' }}</p></div>
                    </div>
                </section>
            @elseif($pemohon && $status === 'terverifikasi')
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 dark:border-slate-800 sm:px-8 lg:flex-row lg:items-center lg:justify-between">
                        <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-600 dark:text-emerald-400">Identitas resmi</p><h3 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Data pemohon terverifikasi</h3><p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Versi data {{ $pemohon->versi_data }} · diverifikasi {{ $pemohon->diverifikasi_pada?->format('d M Y, H:i') ?? '—' }}</p></div>
                        <button type="button" x-data x-on:click="$dispatch('open-change-request')" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-800 transition hover:border-amber-500 hover:text-amber-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"><x-heroicon-o-pencil-square class="size-4" /> Ajukan Perubahan Data</button>
                    </div>
                    <dl class="grid gap-x-8 gap-y-6 px-6 py-6 sm:grid-cols-2 lg:grid-cols-3 sm:px-8">
                        @foreach([
                            ['Nama',$pemohon->nama],['Jenis',$pemohon->jenis_pemohon === 'badan' ? 'Badan / badan hukum' : 'Perorangan'],['NIK',$pemohon->nik],['NIB',$pemohon->nib],['NPWP',$pemohon->npwp],['Telepon',$pemohon->nomor_telepon],['Email',$pemohon->email ?: $user->email],
                        ] as $item)
                            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $item[0] }}</dt><dd class="mt-1.5 break-words text-sm font-semibold text-slate-900 dark:text-white">{{ $item[1] ?: '—' }}</dd></div>
                        @endforeach
                        <div class="sm:col-span-2 lg:col-span-3"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Alamat</dt><dd class="mt-1.5 text-sm font-semibold leading-6 text-slate-900 dark:text-white">{{ $pemohon->alamat ?: '—' }}{{ $pemohon->kelurahan ? ', '.$pemohon->kelurahan : '' }}{{ $pemohon->kecamatan ? ', '.$pemohon->kecamatan : '' }}{{ $pemohon->kota ? ', '.$pemohon->kota : '' }}</dd></div>
                    </dl>
                </section>

                <div x-data="{ open: false }" x-on:open-change-request.window="open = true" x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
                    <div x-on:click.outside="open = false" class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-4"><div><h3 class="text-lg font-black text-slate-950 dark:text-white">Ajukan perubahan data</h3><p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">Jelaskan alasan perubahan. Setelah dikirim, permintaan akan ditinjau Admin/Staff. Formulir baru dibuka jika permintaan perubahan diizinkan.</p></div><button type="button" x-on:click="open = false" class="text-slate-400 hover:text-slate-700"><x-heroicon-o-x-mark class="size-5" /></button></div>
                        <textarea wire:model="alasanPerubahan" rows="5" maxlength="2000" placeholder="Contoh: nomor telepon berubah karena nomor lama sudah tidak aktif." class="mt-5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-950 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white"></textarea>
                        @error('alasanPerubahan') <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        <div class="mt-5 flex justify-end gap-2"><button type="button" x-on:click="open = false" class="min-h-10 rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Batal</button><button type="button" wire:click="requestChange" wire:loading.attr="disabled" class="min-h-10 rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white disabled:opacity-60 dark:bg-white dark:text-slate-950">Ajukan Perubahan</button></div>
                    </div>
                </div>
            @elseif($pemohon && $status === 'perlu_perbaikan')
                <section class="rounded-2xl border border-rose-200 bg-rose-50 px-6 py-5 dark:border-rose-400/20 dark:bg-rose-400/5 sm:px-8">
                    <p class="text-sm font-black text-rose-800 dark:text-rose-200">Data belum dapat diverifikasi</p>
                    <p class="mt-1 text-sm leading-6 text-rose-700 dark:text-rose-300">{{ $pemohon->alasan_perubahan ?: 'Silakan periksa kembali data sebelum mengirim ulang.' }}</p>
                </section>
            @elseif($pemohon && $status === 'perlu_perubahan')
                <section class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5 dark:border-amber-400/20 dark:bg-amber-400/5 sm:px-8">
                    <p class="text-sm font-black text-amber-900 dark:text-amber-200">Form perubahan data terbuka</p>
                    <p class="mt-1 text-sm leading-6 text-amber-800 dark:text-amber-300">Alasan perubahan: {{ $pemohon->alasan_perubahan ?: '—' }}. Setelah disimpan, data akan kembali ke antrean verifikasi.</p>
                </section>
            @endif

            @if(!$pemohon || $canEdit)
                @if($errors->any())
                    <section class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 dark:border-rose-400/20 dark:bg-rose-400/5" role="alert">
                        <p class="text-sm font-black text-rose-800 dark:text-rose-200">Data belum dapat dikirim</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-xs leading-5 text-rose-700 dark:text-rose-300">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif
                <form wire:submit.prevent="savePemohon" wire:key="pemohon-verification-form" class="space-y-6">
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800 sm:px-8"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Data Pemohon</p><h3 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Lengkapi identitas Anda</h3><p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">Data akan menjadi identitas resmi yang digunakan dalam proses IPPT.</p></div>
                        <div class="grid gap-6 px-6 py-6 sm:px-8 lg:grid-cols-2">
                            <fieldset class="lg:col-span-2"><legend class="mb-2 text-sm font-semibold text-slate-900 dark:text-white">Jenis pemohon <span class="text-rose-600">*</span></legend><div class="grid gap-3 sm:grid-cols-2">
                                <label class="cursor-pointer"><input type="radio" value="perorangan" wire:model.live="data.jenis_pemohon" class="peer sr-only"><span class="block rounded-xl border border-slate-200 bg-white p-4 transition peer-checked:border-amber-500 peer-checked:ring-2 peer-checked:ring-amber-500/15 dark:border-slate-700 dark:bg-slate-950"><strong class="block text-sm text-slate-950 dark:text-white">Perorangan</strong><small class="mt-1 block text-xs text-slate-500">Mengajukan atas nama pribadi.</small></span></label>
                                <label class="cursor-pointer"><input type="radio" value="badan" wire:model.live="data.jenis_pemohon" class="peer sr-only"><span class="block rounded-xl border border-slate-200 bg-white p-4 transition peer-checked:border-amber-500 peer-checked:ring-2 peer-checked:ring-amber-500/15 dark:border-slate-700 dark:bg-slate-950"><strong class="block text-sm text-slate-950 dark:text-white">Badan / badan hukum</strong><small class="mt-1 block text-xs text-slate-500">Mengajukan atas nama badan/usaha.</small></span></label>
                            </div></fieldset>
                            <div><label class="mb-2 block text-sm font-semibold text-slate-900 dark:text-white">{{ $this->data['jenis_pemohon'] === 'badan' ? 'Nama Badan Usaha' : 'Nama Pemohon' }} <span class="text-rose-600">*</span></label><input wire:model="data.nama" maxlength="150" placeholder="Nama lengkap sesuai identitas" class="block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">@error('data.nama')<p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror</div>
                            @if($this->data['jenis_pemohon'] === 'badan')<div><label class="mb-2 block text-sm font-semibold text-slate-900 dark:text-white">NIB <span class="text-rose-600">*</span></label><input wire:model="data.nib" inputmode="numeric" maxlength="13" placeholder="Masukkan 13 digit NIB" class="block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">@error('data.nib')<p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror</div>@else<div><label class="mb-2 block text-sm font-semibold text-slate-900 dark:text-white">NIK <span class="text-rose-600">*</span></label><input wire:model="data.nik" inputmode="numeric" maxlength="16" placeholder="Masukkan 16 digit NIK" class="block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">@error('data.nik')<p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror</div>@endif
                            <div><label class="mb-2 block text-sm font-semibold text-slate-900 dark:text-white">NPWP <span class="font-normal text-slate-400">(opsional)</span></label><input wire:model="data.npwp" maxlength="16" placeholder="Nomor NPWP (opsional)" class="block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"></div>
                            <div><label class="mb-2 block text-sm font-semibold text-slate-900 dark:text-white">Nomor telepon / WhatsApp <span class="text-rose-600">*</span></label><input wire:model="data.nomor_telepon" maxlength="16" placeholder="Contoh: 081234567890" class="block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">@error('data.nomor_telepon')<p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror</div>
                            <div class="lg:col-span-2"><label class="mb-2 block text-sm font-semibold text-slate-900 dark:text-white">Email akun</label><input value="{{ $user->email }}" disabled class="block min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400"></div>
                            <div class="lg:col-span-2"><label class="mb-2 block text-sm font-semibold text-slate-900 dark:text-white">Alamat lengkap <span class="text-rose-600">*</span></label><textarea wire:model="data.alamat" rows="3" maxlength="255" placeholder="Masukkan alamat lengkap tempat tinggal / domisili" class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"></textarea>@error('data.alamat')<p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror</div>
                            <div><label class="mb-2 block text-sm font-semibold text-slate-900 dark:text-white">Kelurahan / Kalurahan</label><input wire:model="data.kelurahan" maxlength="100" placeholder="Nama kelurahan / kalurahan" class="block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"></div>
                            <div><label class="mb-2 block text-sm font-semibold text-slate-900 dark:text-white">Kecamatan / Kapanewon</label><input wire:model="data.kecamatan" maxlength="100" placeholder="Nama kecamatan / kapanewon" class="block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"></div>
                            <div><label class="mb-2 block text-sm font-semibold text-slate-900 dark:text-white">Kota / Kabupaten <span class="text-rose-600">*</span></label><input wire:model="data.kota" maxlength="100" placeholder="Kota / kabupaten" class="block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">@error('data.kota')<p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror</div>
                        </div>
                    </section>
                    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-900/60 sm:flex-row sm:items-center sm:justify-between sm:px-6"><p class="max-w-2xl text-xs leading-5 text-slate-600 dark:text-slate-300">{{ $status === 'perlu_perubahan' ? 'Perubahan yang dikirim akan masuk kembali ke antrean verifikasi.' : 'Setelah dikirim, formulir akan ditutup sampai petugas menyelesaikan verifikasi.' }}</p><button type="submit" wire:loading.attr="disabled" wire:target="savePemohon" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-slate-950"><span wire:loading.remove wire:target="savePemohon">Simpan & Kirim untuk Verifikasi</span><span wire:loading wire:target="savePemohon" class="inline-flex items-center gap-2"><x-heroicon-o-arrow-path class="size-4 animate-spin" /> Mengirim data...</span></button></div>
                </form>
            @endif

            @if($pemohon && $status === 'terverifikasi')
                <div class="flex flex-col gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 dark:border-emerald-400/20 dark:bg-emerald-400/5 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">Identitas Anda sudah diverifikasi. Anda dapat melanjutkan pengajuan IPPT.</p><a href="{{ \App\Filament\Resources\Permohonans\PermohonanResource::getUrl() }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-slate-950">Lihat Permohonan IPPT</a></div>
            @endif
        </div>
    @else
        {{ $this->content }}
    @endif
</x-filament-panels::page>
