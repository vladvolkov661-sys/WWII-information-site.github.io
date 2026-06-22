<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// ✅ Функция для выполнения запросов с подготовленными параметрами
function db_query($sql, $params = []) {
    global $mysqli;
    
    if (empty($params)) {
        return $mysqli->query($sql);
    }
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Ошибка prepare: " . $mysqli->error . " | SQL: $sql");
        return false;
    }
    
    if (!empty($params)) {
        $types = '';
        $values = [];
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_string($param)) $types .= 's';
            elseif (is_double($param)) $types .= 'd';
            elseif (is_null($param)) $types .= 's';
            else $types .= 'b';
            $values[] = $param;
        }
        $stmt->bind_param($types, ...$values);
    }
    
    if (!$stmt->execute()) {
        error_log("Ошибка execute: " . $stmt->error . " | SQL: $sql");
        return false;
    }
    
    if (stripos(trim($sql), 'SELECT') === 0) {
        return $stmt->get_result();
    }
    
    return true;
}

// ✅ Функция перенумерации ID в таблице
function renumberTable($table) {
    global $mysqli;
    $mysqli->query("SET @row_number = 0");
    $mysqli->query("UPDATE `$table` SET id = (@row_number:=@row_number+1) ORDER BY id");
    $mysqli->query("ALTER TABLE `$table` AUTO_INCREMENT = 1");
}

// ✅ Обработка POST-запросов (добавление/редактирование)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $full_name = trim($_POST['full_name']);
    $rank = trim($_POST['rank']);
    $birth_year = $_POST['birth_year'] ?: null;
    $death_year = $_POST['death_year'] ?: null;
    $biography = trim($_POST['biography']);
    $awards = trim($_POST['awards']);
    $event_id = $_POST['event_id'] ?: null;

    $image_path = '';
    
    // Загрузка изображения
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
        $uploadDir = '../assets/heroes/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('hero_') . '.' . $ext;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
            $image_path = 'assets/heroes/' . $filename;
        }
    }

    if ($id) {
        // ✅ РЕДАКТИРОВАНИЕ
        if ($image_path) {
            // Удаляем старое изображение
            $old = db_query("SELECT image_path FROM heroes WHERE id = ?", [$id]);
            if ($old && $old->num_rows > 0) {
                $old_row = $old->fetch_assoc();
                if ($old_row['image_path'] && file_exists('../' . $old_row['image_path'])) {
                    unlink('../' . $old_row['image_path']);
                }
            }
            $sql = "UPDATE heroes SET full_name=?, rank=?, birth_year=?, death_year=?, biography=?, awards=?, event_id=?, image_path=? WHERE id=?";
            $params = [$full_name, $rank, $birth_year, $death_year, $biography, $awards, $event_id, $image_path, $id];
        } else {
            $sql = "UPDATE heroes SET full_name=?, rank=?, birth_year=?, death_year=?, biography=?, awards=?, event_id=? WHERE id=?";
            $params = [$full_name, $rank, $birth_year, $death_year, $biography, $awards, $event_id, $id];
        }
        $success = db_query($sql, $params);
        $message = $success ? '✅ Герой обновлён!' : '❌ Ошибка обновления!';
    } else {
        // ✅ ДОБАВЛЕНИЕ + перенумерация ID
        $sql = "INSERT INTO heroes (full_name, rank, birth_year, death_year, biography, awards, image_path, event_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$full_name, $rank, $birth_year, $death_year, $biography, $awards, $image_path, $event_id];
        $success = db_query($sql, $params);
        
        if ($success) {
            renumberTable('heroes'); // 🔁 Перенумерация после добавления
            $message = '✅ Герой добавлен! ID перенумерованы.';
        } else {
            $message = '❌ Ошибка добавления!';
        }
    }
}

// ✅ УДАЛЕНИЕ + перенумерация ID
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Удаляем героя
    $success = $mysqli->query("DELETE FROM heroes WHERE id = $delete_id");
    
    if ($success) {
        renumberTable('heroes'); // 🔁 Перенумерация после удаления
        $message = '✅ Герой удалён! ID перенумерованы.';
    } else {
        $message = '❌ Ошибка удаления!';
    }
}

// ✅ Получение героя для редактирования
$editHero = null;
if (isset($_GET['edit'])) {
    $result = db_query("SELECT * FROM heroes WHERE id = ?", [(int)$_GET['edit']]);
    if ($result && $result->num_rows > 0) {
        $editHero = $result->fetch_assoc();
    }
}

// ✅ Получаем всех героев с названиями событий
$result = $mysqli->query("SELECT h.*, e.title as event_title FROM heroes h LEFT JOIN events e ON h.event_id = e.id ORDER BY h.id");
$heroes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $heroes[] = $row;
    }
}

// ✅ Получаем все события для выпадающего списка
$result = $mysqli->query("SELECT id, title FROM events ORDER BY title");
$allEvents = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allEvents[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление героями — Админ-панель</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header>
        <div class="header-top">
            <a href="../index.php" class="logo">⭐ Админ-панель</a>
        </div>
    </header>

    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <h3>Навигация</h3>
            <ul>
                <li><a href="index.php">📊 Панель</a></li>
                <li><a href="events.php">📋 События</a></li>
                <li><a href="heroes.php" class="active">🎖️ Герои</a></li>
                <li><a href="../index.php">🌐 На сайт</a></li>
                <li><a href="logout.php">🚪 Выход</a></li>
            </ul>
        </aside>

        <div class="admin-main">
            <?php if ($message): ?>
                <div style="background: var(--accent-dark); color: var(--text-primary); padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--accent);">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <h2><?= $editHero ? 'Редактирование героя' : 'Добавить героя' ?></h2>
            <form class="admin-form" method="POST" enctype="multipart/form-data">
                <?php if ($editHero): ?>
                    <input type="hidden" name="id" value="<?= $editHero['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>ФИО *</label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($editHero['full_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Звание</label>
                    <input type="text" name="rank" value="<?= htmlspecialchars($editHero['rank'] ?? '') ?>" placeholder="например: Генерал-майор">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Год рождения</label>
                        <input type="number" name="birth_year" min="1850" max="1945" value="<?= $editHero['birth_year'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Год смерти</label>
                        <input type="number" name="death_year" min="1941" max="2000" value="<?= $editHero['death_year'] ?? '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Привязка к событию</label>
                    <select name="event_id">
                        <option value="">— Не выбрано —</option>
                        <?php foreach ($allEvents as $ev): ?>
                            <option value="<?= $ev['id'] ?>" <?= ($editHero['event_id'] ?? '') == $ev['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ev['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Награды</label>
                    <textarea name="awards" rows="3"><?= htmlspecialchars($editHero['awards'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Биография</label>
                    <textarea name="biography" rows="8"><?= htmlspecialchars($editHero['biography'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Фото</label>
                    <?php if ($editHero && !empty($editHero['image_path'])): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="../<?= htmlspecialchars($editHero['image_path']) ?>" alt="Текущее фото" style="max-width: 150px; border-radius: 5px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*">
                </div>

                <button type="submit" class="submit-btn"><?= $editHero ? 'Сохранить изменения' : 'Добавить героя' ?></button>
                <?php if ($editHero): ?>
                    <a href="heroes.php" style="margin-left: 10px; color: var(--text-secondary);">Отмена</a>
                <?php endif; ?>
            </form>

            <h2 style="margin-top: 40px;">Все герои (<?= count($heroes) ?>)</h2>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ФИО</th>
                            <th>Звание</th>
                            <th>Годы</th>
                            <th>Событие</th>
                            <th style="width: 120px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($heroes)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 20px;">Герои не добавлены</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($heroes as $hero): ?>
                            <tr>
                                <td><strong><?= $hero['id'] ?></strong></td>
                                <td><?= htmlspecialchars($hero['full_name']) ?></td>
                                <td><?= htmlspecialchars($hero['rank']) ?: '—' ?></td>
                                <td><?= $hero['birth_year'] ? $hero['birth_year'] : '?' ?>–<?= $hero['death_year'] ? $hero['death_year'] : '?' ?></td>
                                <td><?= $hero['event_title'] ? htmlspecialchars($hero['event_title']) : '—' ?></td>
                                <td>
                                    <a href="?edit=<?= $hero['id'] ?>" class="action-btn edit" title="Редактировать">✏️</a>
                                    <a href="?delete=<?= $hero['id'] ?>" class="action-btn delete" 
                                       onclick="return confirm('Удалить героя «<?= addslashes($hero['full_name']) ?>»?\n\n⚠️ Все ID будут перенумерованы!')" 
                                       title="Удалить">🗑️</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../script.js"></script>
</body>
</html>