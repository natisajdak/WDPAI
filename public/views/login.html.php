<!doctype html>
<html lang="pl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zaloguj się - System</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <main class="page-container">
        <section class="card">
            <h1>Logowanie</h1>
            <form action="/login" method="post" class="form-layout">
                <div class="form-group">
                    <label for="email">Adres e-mail</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Hasło</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn-primary">Zaloguj się</button>
            </form>
            <a href="/forgot-password" class="text-center">Zapomniałeś hasła?</a>
        </section>
    </main>
</body>
</html>