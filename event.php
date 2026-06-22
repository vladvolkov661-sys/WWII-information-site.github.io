<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: index.php');
    exit;
}

// Связанные герои
$stmtHeroes = $pdo->prepare("SELECT * FROM heroes WHERE event_id = ?");
$stmtHeroes->execute([$id]);
$relatedHeroes = $stmtHeroes->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title']) ?> — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="header-top">
            <a href="index.php" class="logo">⭐ <?= SITE_NAME ?></a>
            <form class="global-search" action="search.php" method="GET">
                <input type="text" name="q" placeholder="Поиск по сайту..." required>
                <button type="submit">🔍</button>
            </form>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Главная</a></li>
                <li><a href="index.php#events" class="active">События</a></li>
                <li><a href="index.php#heroes">О героях</a></li>
                <li><a href="about.php">О сайте</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="event-page">
            <div class="event-hero animate-on-scroll">
                <?php if ($event['image_path']): ?>
                    <img src="<?= htmlspecialchars($event['image_path']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                <?php else: ?>
                    <div style="width:100%;height:100%;background:linear-gradient(135deg, #1a1a2e, #16213e);display:flex;align-items:center;justify-content:center;">
                        <span style="font-size:5rem;opacity:0.2;">⭐</span>
                    </div>
                <?php endif; ?>
                <div class="event-title-overlay">
                    <h1><?= htmlspecialchars($event['title']) ?></h1>
                </div>
            </div>

            <div class="event-info">
                <div class="event-description animate-on-scroll">
                    <?php
                    $fullDesc = $event['full_description'] ?: $event['description'];
                    echo nl2br(htmlspecialchars($fullDesc));
                    ?>
                </div>

                <div class="event-sidebar">
                    <div class="info-box animate-right">
                        <h4>📅 Даты</h4>
                        <p>
                            <?php if ($event['date_start']): ?>
                                <?= date('d.m.Y', strtotime($event['date_start'])) ?>
                                <?php if ($event['date_end']): ?>
                                    — <?= date('d.m.Y', strtotime($event['date_end'])) ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <?= $event['year'] ?>г.
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="info-box animate-right" style="animation-delay: 0.2s">
                        <h4>🏷️ Категория</h4>
                        <p><?= htmlspecialchars(ucfirst($event['category'])) ?></p>
                    </div>

                    <div class="info-box animate-right" style="animation-delay: 0.4s">
                        <h4>🗓️ Год</h4>
                        <p><?= $event['year'] ?></p>
                    </div>

                    <?php if (!empty($relatedHeroes)): ?>
                    <div class="info-box animate-right" style="animation-delay: 0.6s">
                        <h4>🎖️ Герои события</h4>
                        <?php foreach ($relatedHeroes as $hero): ?>
                            <p><a href="hero.php?id=<?= $hero['id'] ?>"><?= htmlspecialchars($hero['full_name']) ?></a></p>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php#events" class="filter-btn">← Вернуться к событиям</a>
            </div>
        </article>
    </main>

    <footer>
        <div class="footer-content">
            <p>© 2026 <?= SITE_NAME ?>. Вечная память героям.</p>
            <button class="admin-login-btn" onclick="location.href='admin/login.php'">⚙️ Вход для администратора</button>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>