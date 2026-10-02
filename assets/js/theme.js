(function () {
    var KEY = 'sarpras-theme';

    function current() {
        return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    }

    function apply(theme) {
        var isDark = theme === 'dark';
        document.documentElement.classList.toggle('dark', isDark);
        try { localStorage.setItem(KEY, isDark ? 'dark' : 'light'); } catch (e) {}
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.setAttribute('aria-label', isDark ? 'Ubah ke mode terang' : 'Ubah ke mode malam');
            btn.setAttribute('title', isDark ? 'Mode terang' : 'Mode malam');
            var sun = btn.querySelector('[data-icon="sun"]');
            var moon = btn.querySelector('[data-icon="moon"]');
            if (sun) sun.classList.toggle('hidden', !isDark);
            if (moon) moon.classList.toggle('hidden', isDark);
        });
    }

    window.sarprasTheme = {
        apply: apply,
        toggle: function () {
            apply(current() === 'dark' ? 'light' : 'dark');
        }
    };

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-theme-toggle]');
        if (!btn) return;
        e.preventDefault();
        window.sarprasTheme.toggle();
    });

    apply(current());
})();
