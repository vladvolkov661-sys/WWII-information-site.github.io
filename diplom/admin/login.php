<?php
require_once '../config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}

// Если уже авторизован
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <main>
        <div class="login-container">
            <div class="login-box animate-on-scroll">
                <h2>🔐 Вход</h2>
                <?php if ($error): ?>
                    <p class="error-msg"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Логин</label>
                        <input type="text" name="username" required placeholder="Введите логин">
                    </div>
                    <div class="form-group">
                        <label>Пароль</label>
                        <input type="password" name="password" required placeholder="Введите пароль">
                    </div>
                    <button type="submit" class="submit-btn" style="width: 100%;">Войти</button>
                </form>
                <p style="text-align: center; margin-top: 20px;">
                    <a href="../index.php" style="font-size: 0.85rem;">← Вернуться на сайт</a>
                </p>
            </div>
        </div>
    </main>
    <script src="../script.js"></script>
</body>
</html>