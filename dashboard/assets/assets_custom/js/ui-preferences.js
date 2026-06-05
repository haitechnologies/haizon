// Apply persisted dashboard UI preferences before the rest of the app boots.
(function() {
    var root = document.documentElement;
    var storage = null;

    try {
        storage = window.localStorage;
    } catch (error) {
        storage = null;
    }

    if (!storage) {
        return;
    }

    var theme = storage.getItem('theme');
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (theme === 'dark' || (theme === 'auto' && prefersDark)) {
        root.setAttribute('data-color-theme', 'dark');
    } else {
        root.removeAttribute('data-color-theme');
    }

    if (storage.getItem('direction') === 'rtl') {
        root.setAttribute('dir', 'rtl');
    }
})();