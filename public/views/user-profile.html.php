<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
</head>
<body>
    <header>
        <h1>Aplikacja Webowa - Profil</h1>
        <nav>
            <a href="/">Strona główna</a>
        </nav>
    </header>
    
    <main>
        <section>
            <h2>Dane użytkownika: <?= htmlspecialchars($userName) ?></h2>
            <ul>
                <li><strong>ID:</strong> <?= $userId ?></li>
                <li><strong>Rola:</strong> <?= htmlspecialchars($userRole) ?></li>
            </ul>
        </section>
    </main>
</body>
</html>