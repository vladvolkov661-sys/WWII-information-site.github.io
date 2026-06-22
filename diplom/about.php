<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О сайте — <?= SITE_NAME ?></title>
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
                <li><a href="index.php#heroes">О героях</a></li>
                <li><a href="about.php" class="active">О сайте</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2 class="page-title animate-on-scroll">О проекте</h2>
        
        <div class="animate-on-scroll" style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 10px; padding: 40px; max-width: 900px; margin: 0 auto; line-height: 1.8;">
            <p style="color: var(--text-secondary); font-size: 1.05rem;">
                Данный сайт посвящён Великой Отечественной войне 1941–1945 годов — самому кровопролитному 
                конфликту в истории человечества. На интерактивной карте вы можете отследить линию фронта 
                по годам, изучить ключевые сражения и узнать о героях, отдавших свои жизни за свободу Родины.
            </p>
            
            <div class="star-decoration">★ ★ ★</div>
            
            <p style="color: var(--text-secondary); font-size: 1.05rem;">
                Мы стремимся сохранить память о подвиге советского народа и предоставить удобный инструмент 
                для изучения истории войны. Каждое событие на карте привязано к конкретным координатам и 
                содержит подробную информацию о ходе боевых действий.
            </p>
            
            <div style="margin-top: 30px; padding: 20px; background: var(--bg-card); border-radius: 8px; border-left: 3px solid var(--accent);">
                <p style="color: var(--accent); font-style: italic;">
                    «Никто не забыт, ничто не забыто»
                </p>
            </div>
        </div>
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