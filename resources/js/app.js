const THEME_KEY = 'theme';
const THEME_MODES = ['light', 'system', 'dark'];

function getStoredTheme() {
    try {
        const value = localStorage.getItem(THEME_KEY);
        return THEME_MODES.includes(value) ? value : 'system';
    } catch (_) {
        return 'system';
    }
}

function resolveDark(mode) {
    return mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
}

function renderTheme(mode) {
    const dark = resolveDark(mode);
    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.dataset.themeMode = mode;

    document.querySelectorAll('[data-theme-toggle-icon]').forEach((icon) => {
        icon.innerHTML = dark
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>';
    });

    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', dark ? '#020617' : '#f8fafc');
}

function setTheme(mode) {
    if (!THEME_MODES.includes(mode)) return;
    try { localStorage.setItem(THEME_KEY, mode); } catch (_) {}
    renderTheme(mode);
}

function cycleTheme() {
    const current = getStoredTheme();
    setTheme(resolveDark(current) ? 'light' : 'dark');
}

document.addEventListener('DOMContentLoaded', () => {
    renderTheme(getStoredTheme());

    document.querySelectorAll('[data-theme-cycle]').forEach((button) => {
        button.addEventListener('click', cycleTheme);
    });

    const media = window.matchMedia('(prefers-color-scheme: dark)');
    media.addEventListener?.('change', () => {
        if (getStoredTheme() === 'system') renderTheme('system');
    });

    window.addEventListener('storage', (event) => {
        if (event.key === THEME_KEY) renderTheme(getStoredTheme());
    });
});
