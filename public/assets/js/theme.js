/**
 * Theme controller — light / dark switching.
 *
 * The theme is applied by a tiny inline script in <head> before first paint
 * (see template/master.blade.php) so there is no flash. This file only wires
 * up the toggle button and reacts to OS-level changes.
 *
 * Storage key holds 'light' | 'dark'. Absent means "follow the OS".
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'alpha-theme';
    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function stored() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    function persist(value) {
        try {
            localStorage.setItem(STORAGE_KEY, value);
        } catch (e) {
            /* private mode — theme stays for this page only */
        }
    }

    function current() {
        return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function apply(theme) {
        root.setAttribute('data-theme', theme);
        root.setAttribute('data-bs-theme', theme);
        // Sneat keys a few rules off these classes
        root.classList.toggle('dark-style', theme === 'dark');
        root.classList.toggle('light-style', theme !== 'dark');

        document.querySelectorAll('.theme-toggle').forEach(function (btn) {
            var label = theme === 'dark' ? 'Yorug‘ rejimga o‘tish' : 'Qorong‘i rejimga o‘tish';
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
            btn.setAttribute('aria-pressed', String(theme === 'dark'));
        });

        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme: theme } }));
    }

    function toggle() {
        var next = current() === 'dark' ? 'light' : 'dark';
        persist(next);
        apply(next);
    }

    document.addEventListener('DOMContentLoaded', function () {
        apply(current());

        document.querySelectorAll('.theme-toggle').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                toggle();
            });
        });
    });

    // Follow the OS only while the user has not made an explicit choice.
    if (media) {
        var onChange = function (event) {
            if (!stored()) {
                apply(event.matches ? 'dark' : 'light');
            }
        };

        if (typeof media.addEventListener === 'function') {
            media.addEventListener('change', onChange);
        } else if (typeof media.addListener === 'function') {
            media.addListener(onChange);
        }
    }

    window.AlphaTheme = {
        get: current,
        set: function (theme) {
            var value = theme === 'dark' ? 'dark' : 'light';
            persist(value);
            apply(value);
        },
        toggle: toggle,
        reset: function () {
            try {
                localStorage.removeItem(STORAGE_KEY);
            } catch (e) { /* noop */ }
            apply(media && media.matches ? 'dark' : 'light');
        }
    };
})();
