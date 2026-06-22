<?php
require_once 'config.php';

// ✅ Год по умолчанию 1941 (там у нас тестовое событие)
$year = isset($_GET['year']) ? (int)$_GET['year'] : 1941;

// ✅ ЗАПРОС СОБЫТИЙ ДЛЯ КАРТЫ (с проверкой ошибок)
$events = [];
$stmt = $mysqli->prepare("SELECT * FROM events WHERE year = ? ORDER BY date_start");
if ($stmt) {
    $stmt->bind_param("i", $year);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    } else {
        error_log("Ошибка execute: " . $stmt->error);
    }
} else {
    error_log("Ошибка prepare: " . $mysqli->error);
}

// ✅ ПОСЛЕДНИЕ СОБЫТИЯ ДЛЯ КАРТОЧЕК
$latestEvents = [];
$resultAll = $mysqli->query("SELECT * FROM events ORDER BY date_start DESC LIMIT 999");
if ($resultAll) {
    while ($row = $resultAll->fetch_assoc()) {
        $latestEvents[] = $row;
    }
}

// ✅ ГЕРОИ
$heroes = [];
$resultHeroes = $mysqli->query("SELECT * FROM heroes ORDER BY full_name LIMIT 999");
if ($resultHeroes) {
    while ($row = $resultHeroes->fetch_assoc()) {
        $heroes[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://*.basemaps.cartocdn.com;">
    <title><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
    <!-- Загрузка -->
    <div class="loading-overlay" id="loader">
        <div class="spinner"></div>
    </div>

    <!-- HEADER -->
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
                <li><a href="index.php" class="active">Главная</a></li>
                <li><a href="index.php#events">События</a></li>
                <li><a href="index.php#heroes">О героях</a></li>
                <li><a href="about.php">О сайте</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- КАРТА -->
        <section class="map-container">
            <div class="map-header">
                <h2>🗺️ Карта боевых действий</h2>
                <div class="year-selector">
                    <?php for ($y = 1941; $y <= 1945; $y++): ?>
                        <button class="year-btn <?= $y == $year ? 'active' : '' ?>" 
                                onclick="changeYear(<?= $y ?>)"><?= $y ?></button>
                    <?php endfor; ?>
                </div>
            </div>
            <div id="map"></div>
        </section>

        <!-- СОБЫТИЯ -->
        <section id="events" style="margin-top: 60px;">
            <h2 class="page-title animate-on-scroll">Хроника событий</h2>
            
            <div class="filters animate-on-scroll">
                <label>Фильтр:</label>
                <select id="filterCategory">
                    <option value="">Все категории</option>
                    <option value="битва">Битвы</option>
                    <option value="операция">Операции</option>
                    <option value="оборона">Оборона</option>
                    <option value="освобождение">Освобождение</option>
                </select>
                <select id="filterYear">
                    <option value="">Все годы</option>
                    <option value="1941">1941</option>
                    <option value="1942">1942</option>
                    <option value="1943">1943</option>
                    <option value="1944">1944</option>
                    <option value="1945">1945</option>
                </select>
                <input type="text" id="filterSearch" placeholder="Поиск событий...">
                <button class="filter-btn" onclick="applyFilters()">Применить</button>
            </div>

            <div class="cards-grid" id="eventsGrid">
                <?php foreach ($latestEvents as $i => $event): ?>
                <a href="event.php?id=<?= $event['id'] ?>" class="card animate-on-scroll" style="animation-delay: <?= $i * 0.1 ?>s" 
                   data-category="<?= $event['category'] ?>" data-year="<?= $event['year'] ?>">
                    <div class="card-image">
                        <?php if ($event['image_path']): ?>
                            <img src="<?= htmlspecialchars($event['image_path']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                        <?php else: ?>
                            <span style="font-size: 3rem; opacity: 0.3;">⭐</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h3><?= htmlspecialchars($event['title']) ?></h3>
                        <div class="card-date"><?= $event['year'] ?>г. <?= $event['category'] ?></div>
                        <p><?= htmlspecialchars(mb_substr($event['description'], 0, 150)) ?>...</p>
                        <span class="card-link">Подробнее →</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="star-decoration animate-on-scroll">★ ★ ★</div>

        <!-- ГЕРОИ -->
        <section id="heroes" style="margin-top: 40px;">
            <h2 class="page-title animate-on-scroll">Герои Советского Союза</h2>
            
            <div class="filters animate-on-scroll">
                <label>Поиск героя:</label>
                <input type="text" id="heroSearch" placeholder="Введите имя..." onkeyup="filterHeroes()">
            </div>

            <div class="cards-grid" id="heroesGrid">
                <?php foreach ($heroes as $i => $hero): ?>
                <a href="hero.php?id=<?= $hero['id'] ?>" class="card animate-on-scroll" style="animation-delay: <?= $i * 0.15 + 0.2 ?>s">
                    <div class="card-image">
                        <?php if ($hero['image_path']): ?>
                            <img src="<?= htmlspecialchars($hero['image_path']) ?>" alt="<?= htmlspecialchars($hero['full_name']) ?>">
                        <?php else: ?>
                            <span style="font-size: 3rem; opacity: 0.3;">🎖️</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h3><?= htmlspecialchars($hero['full_name']) ?></h3>
                        <div class="card-date"><?= htmlspecialchars($hero['rank']) ?> | <?= $hero['birth_year'] ?>–<?= $hero['death_year'] ?></div>
                        <p><?= htmlspecialchars(mb_substr($hero['biography'], 0, 120)) ?>...</p>
                        <span class="card-link">Подробнее →</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer>
        <div class="footer-content">
            <p>© 2026 <?= SITE_NAME ?>. Вечная память героям.</p>
            <button class="admin-login-btn" onclick="location.href='admin/login.php'">⚙️ Вход для администратора</button>
        </div>
    </footer>

    <!-- ✅ ОТЛАДОЧНЫЙ БЛОК (удалите после настройки) -->
    <div id="debug" style="display:none;">
        Year: <?= $year ?> | Events: <?= count($events) ?>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- ✅ ПЕРЕДАЧА ДАННЫХ В JAVASCRIPT -->
    <script>
        const eventsData = <?= json_encode($events, JSON_UNESCAPED_UNICODE) ?>;
        const currentYear = <?= $year ?>;
        
        // ✅ ОТЛАДКА: вывод в консоль
        console.log('✅ eventsData:', eventsData);
        console.log('✅ currentYear:', currentYear);
        console.log('✅ Количество событий:', eventsData.length);
    </script>
    
    <script src="script.js"></script>
</body>
</html>