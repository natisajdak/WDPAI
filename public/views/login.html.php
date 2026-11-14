<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $pdo = new PDO(
                "pgsql:host=db;port=5432;dbname=cargo", 
                "cargo_app", 
                "change_me_app", 
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

            $stmt = $pdo->prepare("SELECT id, password_hash, is_active FROM users WHERE lower(email) = lower(:email)");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['is_active']) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = "Twoje konto zostało dezaktywowane.";
                }
            } else {
                $error = "Nieprawidłowy e-mail lub hasło.";
            }
        } catch (PDOException $e) {
            $error = "Błąd połączenia z bazą: " . $e->getMessage();
        }
    } else {
        $error = "Wypełnij wszystkie pola.";
    }
}

// Zbieramy HTML do wstrzyknięcia w layout.php
ob_start();
?>

<main class="page-container">
    <div class="card">
        <h1>Logowanie</h1>
        
        <?php if ($error): ?>
            <div style="color: #be123c; background: #fff1f2; padding: 0.75rem; border-radius: 0.5rem; text-align: center; margin-bottom: 1rem; border: 1px solid #fda4af;">
                <strong><?= htmlspecialchars($error) ?></strong>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="form-layout">
            <div class="form-group">
                <label for="email">Adres e-mail</label>
                <input type="email" id="email" name="email" class="form-control" required autofocus placeholder="operator@cargo.local">
            </div>
            
            <div class="form-group">
                <label for="password">Hasło</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn-primary">Zaloguj się</button>
        </form>
    </div>
</main>

<?php
// Wrzucamy kod HTML do zmiennej i ładujemy główny układ
$content = ob_get_clean();
require 'layout.php';
?>