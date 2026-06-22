<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Статистика
$result = $mysqli->query("SELECT COUNT(*) FROM events");
$eventsCount = $result->fetch_row()[0];

$result = $mysqli->query("SELECT COUNT(*) FROM heroes");
$heroesCount = $result->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header>
        <div class="header-top">
            <a href="../index.php" class="logo">⭐ Админ-панель</a>
            <span style="color: var(--text-secondary);">Привет, <?= htmlspecialchars($_SESSION['admin_username']) ?>!</span>
        </div>
    </header>

    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <h3>Навигация</h3>
            <ul>
                <li><a href="index.php" class="active">📊 Панель</a></li>
                <li><a href="events.php">📋 События</a></li>
                <li><a href="heroes.php">🎖️ Герои</a></li>
                <li><a href="../index.php">🌐 На сайт</a></li>
                <li><a href="logout.php">🚪 Выход</a></li>
            </ul>
        </aside>

        <div class="admin-main">
            <h2>Панель управления</h2>

            <div class="cards-grid" style="grid-template-columns: repeat(2, 1fr);">
                <div class="card animate-on-scroll">
                    <div class="card-content" style="text-align: center; padding: 40px;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">📋</div>
                        <h3 style="font-size: 2rem;"><?= $eventsCount ?></h3>
                        <p style="color: var(--text-secondary);">Событий в базе</p>
                        <a href="events.php" class="card-link" style="margin-top: 15px;">Управление →</a>
                    </div>
                </div>
                <div class="card animate-on-scroll" style="animation-delay: 0.2s">
                    <div class="card-content" style="text-align: center; padding: 40px;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">🎖️</div>
                        <h3 style="font-size: 2rem;"><?= $heroesCount ?></h3>
                        <p style="color: var(--text-secondary);">Героев в базе</p>
                        <a href="heroes.php" class="card-link" style="margin-top: 15px;">Управление →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../script.js"></script>
</body>
</html>