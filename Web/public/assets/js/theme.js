(function () {
    var themeKey = 'rescueAppTheme';
    var toggle = document.querySelector('[data-theme-toggle]');
    var label = document.querySelector('[data-theme-label]');
    var saveUrl = 'ajax/save_dark_mode.php';

    function applyTheme(theme) {
        var isDark = theme === 'dark';
        if (isDark) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
        if (toggle) {
            toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        }
        if (label) {
            label.textContent = isDark ? 'Light mode' : 'Dark mode';
        }
    }

    function saveTheme(theme) {
        var body = new URLSearchParams();
        body.set('dark_mode', theme === 'dark' ? '1' : '0');

        return fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString(),
            credentials: 'same-origin'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Theme preference could not be saved.');
            }
            return response.json();
        }).then(function (payload) {
            if (!payload || payload.status !== 'ok') {
                throw new Error('Theme preference could not be saved.');
            }
        });
    }

    var initialTheme = document.documentElement.getAttribute('data-theme') === 'dark' || window.rescueAppTheme === 'dark' ? 'dark' : 'light';
    applyTheme(initialTheme);

    if (initialTheme === 'dark') {
        localStorage.setItem(themeKey, 'dark');
    } else {
        localStorage.removeItem(themeKey);
    }

    if (!toggle || !label) {
        return;
    }

    toggle.addEventListener('click', function () {
        var nextTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        var previousTheme = nextTheme === 'dark' ? 'light' : 'dark';

        if (nextTheme === 'dark') {
            localStorage.setItem(themeKey, 'dark');
        } else {
            localStorage.removeItem(themeKey);
        }

        applyTheme(nextTheme);

        saveTheme(nextTheme).catch(function () {
            if (previousTheme === 'dark') {
                localStorage.setItem(themeKey, 'dark');
            } else {
                localStorage.removeItem(themeKey);
            }
            applyTheme(previousTheme);
        });
    });
}());
