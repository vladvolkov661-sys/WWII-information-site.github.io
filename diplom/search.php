<?php
require_once 'config.php';

// Получаем поисковый запрос
$q = $_GET['q'] ?? '';
$events = [];
$heroes = [];

// Если есть запрос — ищем в базе
if (!empty($q)) {
    // 1. Ищем события (по названию и описанию)
    $searchTerm = "%$q%";
    $stmt = $mysqli->prepare("SELECT * FROM events WHERE title LIKE ? OR description LIKE ? OR full_description LIKE ?");
    if ($stmt) {
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }

    // 2. Ищем героев (по имени и биографии)
    $stmt = $mysqli->prepare("SELECT * FROM heroes WHERE full_name LIKE ? OR rank LIKE ? OR biography LIKE ?");
    if ($stmt) {
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $heroes[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск: <?= htmlspecialchars($q) ?> — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- HEADER (Копия из index.php, чтобы исправить ошибку) -->
    <header>
        <div class="header-top">
            <a href="index.php" class="logo">⭐ <?= SITE_NAME ?></a>
            <form class="global-search" action="search.php" method="GET">
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Поиск по сайту..." required>
                <button type="submit">🔍</button>
            </form>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Главная</a></li>
                <li><a href="index.php#events">События</a></li>
                <li><a href="index.php#heroes">О героях</a></li>
                <li><a href="about.php">О сайте</a></li>
            </ul>
        </nav>
    </header>

    <main style="padding: 40px 0;">
        <h2 class="page-title" style="text-align: center; margin-bottom: 40px;">
            Результаты поиска: "<?= htmlspecialchars($q) ?>"
        </h2>

        <?php if (empty($events) && empty($heroes)): ?>
            <p style="text-align: center; color: #a0a0a0;">Ничего не найдено по запросу "<?= htmlspecialchars($q) ?>".</p>
        <?php else: ?>
            
            <!-- РЕЗУЛЬТАТЫ: СОБЫТИЯ -->
            <?php if (!empty($events)): ?>
            <section style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
                <h3 style="margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px;">События (<?= count($events) ?>)</h3>
                <div class="cards-grid">
                    <?php foreach ($events as $event): ?>
                    <a href="event.php?id=<?= $event['id'] ?>" class="card animate-on-scroll">
                        <div class="card-content">
                            <h3><?= htmlspecialchars($event['title']) ?></h3>
                            <div class="card-date"><?= $event['year'] ?>г.</div>
                            <p><?= htmlspecialchars(mb_substr($event['description'], 0, 120)) ?>...</p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- РЕЗУЛЬТАТЫ: ГЕРОИ -->
            <?php if (!empty($heroes)): ?>
            <section style="max-width: 1200px; margin: 40px auto 0; padding: 0 20px;">
                <h3 style="margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px;">Герои (<?= count($heroes) ?>)</h3>
                <div class="cards-grid">
                    <?php foreach ($heroes as $hero): ?>
                    <a href="hero.php?id=<?= $hero['id'] ?>" class="card animate-on-scroll">
                        <div class="card-content">
                            <h3><?= htmlspecialchars($hero['full_name']) ?></h3>
                            <div class="card-date"><?= htmlspecialchars($hero['rank']) ?></div>
                            <p><?= htmlspecialchars(mb_substr($hero['biography'], 0, 100)) ?>...</p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

        <?php endif; ?>
    </main>

    <!-- FOOTER -->
    <footer>
        <div class="footer-content">
            <p>© <?= date('Y') ?> <?= SITE_NAME ?>. Вечная память героям.</p>
            <button class="admin-login-btn" onclick="location.href='admin/login.php'">⚙️ Вход для администратора</button>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>