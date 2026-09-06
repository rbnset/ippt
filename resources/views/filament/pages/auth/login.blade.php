<x-filament-panels::page.simple heading="">

<style>
/* IPPT Auth v27: landing header, active-state navigation, working theme control, and content-height auth layout. */
.fi-simple-page:has(.ippt-auth-page) .fi-simple-header { display:none!important; }
.fi-simple-page:has(.ippt-auth-page),
.fi-simple-page:has(.ippt-auth-page)>div,
.fi-simple-main:has(.ippt-auth-page),
.fi-simple-main:has(.ippt-auth-page)>div,
.fi-simple-main-ctn:has(.ippt-auth-page) {
    width:100%!important; max-width:none!important; min-width:0!important;
    padding:0!important; margin:0!important; min-height:0!important;
}
.fi-simple-layout:has(.ippt-auth-page) { min-height:0!important; }
html, body { background:#f8fafc; }
html.dark, html.dark body { background:#020617; }
.ippt-auth-page{width:100vw;min-height:0;margin-left:calc(50% - 50vw);box-sizing:border-box;background:#f8fafc;color:#172033;}
.dark .ippt-auth-page{background:#020617;color:#edf2f7}
.ippt-govbar{min-height:36px;border-bottom:1px solid #e2e8f0;background:#fff;color:#64748b;}
.dark .ippt-govbar{border-color:rgba(255,255,255,.08);background:#0f172a;color:#94a3b8}
.ippt-govbar-inner,.ippt-nav-inner{width:min(1280px,calc(100% - 40px));margin:auto;display:flex;align-items:center;justify-content:space-between;gap:20px;}
.ippt-govbar-inner{min-height:36px;font-size:11px;}
.ippt-govbar-inner p{margin:0}
.ippt-site-nav{position:sticky;top:0;z-index:50;border-bottom:1px solid rgba(226,232,240,.92);background:rgba(255,255,255,.96);backdrop-filter:blur(12px);}
.dark .ippt-site-nav{border-color:rgba(255,255,255,.08);background:rgba(2,6,23,.94)}
.ippt-nav-inner{min-height:76px;}
.ippt-nav-brand{display:flex;align-items:center;gap:12px;min-width:0;text-decoration:none;}
.ippt-nav-logo-wrap{width:48px;height:48px;flex:0 0 48px;display:grid;place-items:center;overflow:hidden;border:1px solid #e2e8f0;border-radius:10px;background:#fff;}
.dark .ippt-nav-logo-wrap{border-color:rgba(255,255,255,.10);background:#0f172a}
.ippt-nav-logo{max-width:40px;max-height:40px;object-fit:contain;}
.ippt-nav-brand-text{min-width:0;}
.ippt-nav-brand-text strong{display:block;color:#0f172a;font-size:14px;font-weight:800;letter-spacing:-.02em;line-height:1.2;}
.ippt-nav-brand-text span{display:block;margin-top:2px;color:#64748b;font-size:11px;line-height:1.25;}
.dark .ippt-nav-brand-text strong{color:#f8fafc} .dark .ippt-nav-brand-text span{color:#94a3b8}
.ippt-nav-links{display:none;align-items:center;gap:25px;font-size:13px;font-weight:650;}
.ippt-nav-links a{color:#475569;text-decoration:none;transition:color .16s ease} .ippt-nav-links a:hover{color:#0f172a} .dark .ippt-nav-links a{color:#cbd5e1} .dark .ippt-nav-links a:hover{color:#fff}
.ippt-nav-actions{display:flex;align-items:center;gap:8px;}
.ippt-theme-btn{width:40px;height:40px;display:grid;place-items:center;border:1px solid #e2e8f0;border-radius:9px;background:#fff;color:#475569;cursor:pointer;transition:border-color .16s ease,background .16s ease,color .16s ease;}
.ippt-theme-btn:hover{border-color:#fbbf24;background:#f8fafc} .dark .ippt-theme-btn{border-color:rgba(255,255,255,.10);background:#0f172a;color:#e2e8f0} .dark .ippt-theme-btn:hover{border-color:#fbbf24;background:#1e293b}
.ippt-theme-btn svg{width:18px;height:18px}
.ippt-nav-login,.ippt-nav-register{display:inline-flex;align-items:center;justify-content:center;border-radius:8px;padding:9px 13px;font-size:12px;font-weight:750;text-decoration:none;}
.ippt-nav-login{color:#334155} .ippt-nav-login:hover{background:#f1f5f9} .dark .ippt-nav-login{color:#e2e8f0} .dark .ippt-nav-login:hover{background:rgba(255,255,255,.06)}
.ippt-nav-register{background:#0f172a;color:#fff} .ippt-nav-register:hover{background:#334155} .dark .ippt-nav-register{background:#fbbf24;color:#0f172a} .dark .ippt-nav-register:hover{background:#fcd34d}
.ippt-nav-current{display:none!important;}
.ippt-auth-main{width:min(1280px,calc(100% - 40px));margin:auto;padding:48px 0 52px;}
.ippt-auth-shell{width:min(1120px,100%);margin:auto;display:grid;grid-template-columns:minmax(0,1fr) 450px;gap:clamp(48px,7vw,90px);align-items:start;}
.ippt-auth-copy{min-width:0;padding-top:8px;}
.ippt-auth-kicker{display:inline-flex;align-items:center;gap:8px;min-height:29px;padding:0 11px;border:1px solid rgba(180,83,9,.20);border-radius:999px;background:#fffbeb;color:#a16207;font-size:10px;font-weight:850;letter-spacing:.10em;text-transform:uppercase;}
.ippt-auth-kicker i{width:6px;height:6px;border-radius:50%;background:#d97706;display:block;}
.dark .ippt-auth-kicker{background:rgba(245,158,11,.10);border-color:rgba(245,158,11,.22);color:#fbbf24} .dark .ippt-auth-kicker i{background:#fbbf24}
.ippt-auth-copy h1{max-width:700px;margin:19px 0 17px;color:#0f172a;font-size:clamp(46px,5.4vw,68px);line-height:1.01;letter-spacing:-.055em;font-weight:820;}
.dark .ippt-auth-copy h1{color:#f8fafc}
.ippt-auth-copy h1 em{font-style:normal;color:#b45309} .dark .ippt-auth-copy h1 em{color:#fbbf24}
.ippt-auth-copy>p{max-width:625px;margin:0;color:#64748b;font-size:15px;line-height:1.8;} .dark .ippt-auth-copy>p{color:#a8b3c2}
.ippt-auth-points{display:grid;gap:12px;margin-top:30px;max-width:600px;}
.ippt-auth-point{display:flex;gap:11px;align-items:flex-start;color:#526176;font-size:12px;line-height:1.5;}
.dark .ippt-auth-point{color:#aab5c4}
.ippt-auth-point b{width:24px;height:24px;flex:0 0 24px;display:grid;place-items:center;border-radius:7px;background:#fef3c7;color:#92400e;font-size:11px;font-weight:900;}
.dark .ippt-auth-point b{background:#422006;color:#fbbf24}
.ippt-auth-card{width:100%;min-width:0;box-sizing:border-box;padding:30px;border:1px solid #e2e8f0;border-radius:22px;background:#fff;box-shadow:0 22px 60px rgba(15,23,42,.09);}
.dark .ippt-auth-card{background:#0f172a;border-color:rgba(255,255,255,.10);box-shadow:0 28px 80px rgba(0,0,0,.30)}
.ippt-auth-card::before{content:"";display:block;height:3px;margin:-30px -30px 27px;background:#d97706;border-radius:22px 22px 0 0;} .dark .ippt-auth-card::before{background:#fbbf24}
.ippt-auth-card-head{margin-bottom:22px;} .ippt-auth-card-head h2{margin:0 0 6px;color:#0f172a;font-size:23px;line-height:1.2;font-weight:800;letter-spacing:-.035em;} .dark .ippt-auth-card-head h2{color:#f8fafc}
.ippt-auth-card-head p{margin:0;color:#64748b;font-size:12px;line-height:1.6} .dark .ippt-auth-card-head p{color:#94a3b8}
.ippt-auth-card>.fi-sc,.ippt-auth-card .fi-sc,.ippt-auth-card .fi-sc-form,.ippt-auth-card .fi-sc-form>div,.ippt-auth-card form{width:100%!important;max-width:none!important;min-width:0!important;box-sizing:border-box;}
.ippt-auth-card .fi-sc-form{gap:15px} .ippt-auth-card .fi-fo-field-wrp-label{margin-bottom:6px} .ippt-auth-card .fi-input-wrp{border-radius:10px} .ippt-auth-card .fi-input{min-height:45px} .ippt-auth-card .fi-btn{min-height:45px;border-radius:10px} .ippt-auth-card .fi-sc-actions,.ippt-auth-card .fi-sc-actions .fi-btn{width:100%}
.ippt-auth-card .fi-fo-field-wrp-label-text{font-size:12px;font-weight:700}
.ippt-auth-links{margin-top:18px;padding-top:17px;border-top:1px solid #e2e8f0;text-align:center;color:#64748b;font-size:11px;line-height:1.6} .dark .ippt-auth-links{border-color:rgba(255,255,255,.10);color:#94a3b8}
.ippt-auth-links a{color:#92400e;font-weight:800;text-decoration:none} .ippt-auth-links a:hover{text-decoration:underline} .dark .ippt-auth-links a{color:#fbbf24}
.ippt-auth-back{display:inline-flex;align-items:center;gap:6px;margin-top:15px;color:#64748b;font-size:11px;font-weight:700;text-decoration:none} .ippt-auth-back:hover{color:#92400e} .dark .ippt-auth-back{color:#94a3b8} .dark .ippt-auth-back:hover{color:#fbbf24}
@media(min-width:1100px){.ippt-nav-links{display:flex}}
@media(max-width:900px){.ippt-auth-main{padding:40px 0 44px}.ippt-auth-shell{grid-template-columns:1fr;gap:36px;max-width:650px}.ippt-auth-copy{text-align:center;padding-top:0}.ippt-auth-copy>p{margin-inline:auto}.ippt-auth-points{text-align:left;margin-inline:auto}.ippt-nav-links{display:none}}
@media(max-width:640px){.ippt-govbar-inner,.ippt-nav-inner,.ippt-auth-main{width:min(100% - 28px,1280px)}.ippt-govbar-inner p:nth-child(2){display:none}.ippt-nav-inner{min-height:68px}.ippt-nav-logo-wrap{width:42px;height:42px;flex-basis:42px}.ippt-nav-logo{max-width:35px;max-height:35px}.ippt-nav-brand-text strong{font-size:13px}.ippt-nav-brand-text span{font-size:10px}.ippt-nav-login,.ippt-nav-register{padding:8px 11px}.ippt-theme-btn{width:38px;height:38px}.ippt-auth-main{padding-top:30px}.ippt-auth-copy h1{font-size:40px}.ippt-auth-copy>p{font-size:14px}.ippt-auth-card{padding:24px 20px;border-radius:18px}.ippt-auth-card::before{margin:-24px -20px 22px;border-radius:18px 18px 0 0}}
@media(prefers-reduced-motion:reduce){.ippt-nav-links a,.ippt-theme-btn{transition:none!important}}
</style>

<div class="ippt-auth-page">
  <div class="ippt-govbar"><div class="ippt-govbar-inner"><p>Pemerintah Kota Yogyakarta</p><p>Dinas Pertanahan dan Tata Ruang</p></div></div>
  <header class="ippt-site-nav">
    <div class="ippt-nav-inner">
      <a href="{{ route('landing') }}" class="ippt-nav-brand" aria-label="Portal Layanan IPPT Kota Yogyakarta">
        <span class="ippt-nav-logo-wrap"><img class="ippt-nav-logo" src="{{ file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : asset('image/logo.png') }}" alt="Logo Pemerintah Kota Yogyakarta"></span>
        <span class="ippt-nav-brand-text"><strong>Portal Layanan IPPT</strong><span>Kota Yogyakarta</span></span>
      </a>
      <nav class="ippt-nav-links" aria-label="Navigasi utama">
        <a href="{{ route('landing') }}#tentang">Tentang IPPT</a><a href="{{ route('landing') }}#panduan">Panduan</a><a href="{{ route('landing') }}#alur">Alur</a><a href="{{ route('landing') }}#persyaratan">Persyaratan</a><a href="{{ route('landing') }}#kontak">Kontak</a>
      </nav>
      <div class="ippt-nav-actions">
        <button type="button" data-theme-cycle class="ippt-theme-btn" aria-label="Ganti tema terang dan gelap" title="Ganti tema"><span data-theme-toggle-icon aria-hidden="true"></span></button>
        <a href="{{ route('filament.admin.auth.login') }}" class="ippt-nav-login ippt-nav-current">Masuk</a>
        <a href="{{ route('filament.admin.auth.register') }}" class="ippt-nav-register ">Daftar</a>
      </div>
    </div>
  </header>

  <main class="ippt-auth-main">
    <div class="ippt-auth-shell">
      <section class="ippt-auth-copy" aria-label="Informasi masuk">
        <div class="ippt-auth-kicker"><i></i> Portal layanan IPPT</div>
        <h1>Selamat datang <em>kembali.</em></h1>
        <p>Masuk ke Portal Layanan IPPT Kota Yogyakarta untuk mengakses layanan sesuai akun dan kewenangan Anda.</p>
        <div class="ippt-auth-points"><div class="ippt-auth-point"><b>01</b><span><strong>Pemohon</strong><br>Kelola profil, permohonan, dokumen, dan pantau proses layanan.</span></div>
          <div class="ippt-auth-point"><b>02</b><span><strong>Pengelola</strong><br>Kelola verifikasi dan tahapan pelayanan sesuai peran yang diberikan.</span></div>
          <div class="ippt-auth-point"><b>03</b><span><strong>Aman & terarah</strong><br>Menu dan akses sistem disesuaikan dengan kewenangan akun.</span></div></div>
      </section>
      <section class="ippt-auth-card" aria-label="Form masuk">
        <div class="ippt-auth-card-head"><h2>Masuk ke sistem</h2><p>Gunakan email dan kata sandi akun Anda.</p></div>
        {{ $this->content }}
        <div class="ippt-auth-links">Belum memiliki akun? <a href="{{ filament()->getRegistrationUrl() }}">Daftar sebagai pemohon</a></div>
        <a class="ippt-auth-back" href="{{ route('landing') }}">← Kembali ke halaman utama</a>
      </section>
    </div>
  </main>
</div>

<script>
(() => {
  const key='theme'; const modes=['light','dark','system'];
  const get=()=>{ try { const v=localStorage.getItem(key); return modes.includes(v)?v:'system'; } catch(_) { return 'system'; } };
  const resolve=(mode)=>mode==='dark'||(mode==='system'&&window.matchMedia('(prefers-color-scheme: dark)').matches);
  const render=(mode)=>{
    const dark=resolve(mode); document.documentElement.classList.toggle('dark',dark); document.documentElement.dataset.themeMode=mode;
    document.querySelectorAll('[data-theme-toggle-icon]').forEach((icon)=>{ icon.innerHTML=dark
      ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>'
      : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>'; });
  };
  const cycle=()=>{ const next=resolve(get())?'light':'dark'; try{localStorage.setItem(key,next)}catch(_){} render(next); };
  render(get()); document.querySelectorAll('[data-theme-cycle]').forEach((b)=>b.addEventListener('click',cycle));
  const media=window.matchMedia('(prefers-color-scheme: dark)'); media.addEventListener?.('change',()=>{if(get()==='system')render('system')});
})();
</script>
</x-filament-panels::page.simple>
