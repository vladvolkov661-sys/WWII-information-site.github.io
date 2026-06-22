<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM heroes WHERE id = ?");
$stmt->execute([$id]);
$hero = $stmt->fetch();

if (!$hero) {
    header('Location: index.php');
    exit;
}

// Связанное событие
$relatedEvent = null;
if ($hero['event_id']) {
    $stmtEvent = $pdo->prepare("SELECT title FROM events WHERE id = ?");
    $stmtEvent->execute([$hero['event_id']]);
    $relatedEvent = $stmtEvent->fetch();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($hero['full_name']) ?> — <?= SITE_NAME ?></title>
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
                <li><a href="index.php#events">События</a></li>
                <li><a href="index.php#heroes" class="active">О героях</a></li>
                <li><a href="about.php">О сайте</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="hero-page">
            <h2 class="page-title animate-on-scroll">Герой Советского Союза</h2>
            
            <div class="hero-content animate-on-scroll">
                <div class="hero-image">
                    <?php if ($hero['image_path']): ?>
                        <img src="<?= htmlspecialchars($hero['image_path']) ?>" alt="<?= htmlspecialchars($hero['full_name']) ?>">
                    <?php else: ?>
                        <span style="font-size:5rem;opacity:0.2;">🎖️</span>
                    <?php endif; ?>
                </div>
                <div class="hero-details">
                    <h1><?= htmlspecialchars($hero['full_name']) ?></h1>
                    <div class="hero-rank"><?= htmlspecialchars($hero['rank']) ?></div>
                    <p><strong>Годы жизни:</strong> <?= $hero['birth_year'] ?> — <?= $hero['death_year'] ?></p>
                    
                    <?php if ($hero['awards']): ?>
                    <p><strong>Награды:</strong> <?= nl2br(htmlspecialchars($hero['awards'])) ?></p>
                    <?php endif; ?>

                    <?php if ($relatedEvent): ?>
                    <p><strong>Участвовал в:</strong> <a href="event.php?id=<?= $hero['event_id'] ?>"><?= htmlspecialchars($relatedEvent['title']) ?></a></p>
                    <?php endif; ?>

                    <div style="margin-top: 20px;">
                        <?= nl2br(htmlspecialchars($hero['biography'])) ?>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php#heroes" class="filter-btn">← Вернуться к героям</a>
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