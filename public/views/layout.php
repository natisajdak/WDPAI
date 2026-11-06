<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargo</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        (function () {
            const storageKey = 'theme';
            let savedTheme = null;
            try { savedTheme = localStorage.getItem(storageKey); } catch (e) {}
            
            const hasSavedTheme = savedTheme === 'light' || savedTheme === 'dark';
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            document.documentElement.dataset.theme = hasSavedTheme ? savedTheme : (systemPrefersDark ? 'dark' : 'light');
        })();
    </script>
    
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header style="padding: 1rem; border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between;">
        <strong>Cargo</strong>
        <button class="theme-toggle" type="button" aria-label="Włącz ciemny motyw" aria-pressed="false" title="Zmień motyw">
            <i class="fa-solid fa-moon" aria-hidden="true"></i>
        </button>
    </header>

    <?= $content ?>

    <script src="/js/theme.js" defer></script>
</body>
</html>