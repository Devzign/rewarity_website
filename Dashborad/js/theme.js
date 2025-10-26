// Simple theme manager for Rewarity Admin
(function() {
  const STORAGE_KEY = 'rewarity_theme';
  const ACCENT_KEY = 'rewarity_accent';

  function apply(theme, accent) {
    const html = document.documentElement;
    if (theme) html.setAttribute('data-theme', theme);
    if (accent) html.setAttribute('data-accent', accent);
  }

  function init() {
    const saved = localStorage.getItem(STORAGE_KEY) || 'light';
    const accent = localStorage.getItem(ACCENT_KEY) || 'green';
    apply(saved, accent);

    // Wire up UI controls if present
    document.querySelectorAll('[data-set-theme]').forEach(btn => {
      btn.addEventListener('click', () => {
        const v = btn.getAttribute('data-set-theme');
        localStorage.setItem(STORAGE_KEY, v);
        apply(v);
        // Visual active state for chips
        document.querySelectorAll('.theme-chip').forEach(el => el.classList.remove('active'));
        if (btn.classList.contains('theme-chip')) btn.classList.add('active');
      });
    });
    document.querySelectorAll('[data-set-accent]').forEach(btn => {
      btn.addEventListener('click', () => {
        const v = btn.getAttribute('data-set-accent');
        localStorage.setItem(ACCENT_KEY, v);
        apply(null, v);
      });
    });

    // Activate current chip if present
    const activeChip = document.querySelector(`.theme-chip[data-set-theme="${saved}"]`);
    if (activeChip) activeChip.classList.add('active');
  }

  // Apply as early as possible
  try { init(); } catch(e) {}
})();
