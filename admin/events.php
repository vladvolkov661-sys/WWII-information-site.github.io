<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
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

// Обработка формы (добавление/редактирование)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = trim($_POST['title']);
    $year = (int)$_POST['year'];
    $date_start = $_POST['date_start'] ?: null;
    $date_end = $_POST['date_end'] ?: null;
    $description = trim($_POST['description']);
    $full_description = trim($_POST['full_description']);
    $latitude = $_POST['latitude'] ?: null;
    $longitude = $_POST['longitude'] ?: null;
    $category = $_POST['category'];

    // Загрузка изображения
    $image_path = '';
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
        $uploadDir = '../assets/events/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('event_') . '.' . $ext;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
            $image_path = 'assets/events/' . $filename;
        }
    }

    if ($id) {
        // ✅ РЕДАКТИРОВАНИЕ
        if ($image_path) {
            // Удаляем старое изображение
            $old = db_query("SELECT image_path FROM events WHERE id = ?", [$id]);
            if ($old && $old->num_rows > 0) {
                $old_row = $old->fetch_assoc();
                if ($old_row['image_path'] && file_exists('../' . $old_row['image_path'])) {
                    unlink('../' . $old_row['image_path']);
                }
            }
            $sql = "UPDATE events SET title=?, year=?, date_start=?, date_end=?, description=?, 
                    full_description=?, latitude=?, longitude=?, category=?, image_path=? WHERE id=?";
            $params = [$title, $year, $date_start, $date_end, $description, $full_description, $latitude, $longitude, $category, $image_path, $id];
        } else {
            $sql = "UPDATE events SET title=?, year=?, date_start=?, date_end=?, description=?, 
                    full_description=?, latitude=?, longitude=?, category=? WHERE id=?";
            $params = [$title, $year, $date_start, $date_end, $description, $full_description, $latitude, $longitude, $category, $id];
        }
        
        $success = db_query($sql, $params);
        $message = $success ? '✅ Событие обновлено!' : '❌ Ошибка обновления!';
    } else {
        // ✅ ДОБАВЛЕНИЕ + перенумерация ID
        $sql = "INSERT INTO events (title, year, date_start, date_end, description, full_description, latitude, longitude, image_path, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$title, $year, $date_start, $date_end, $description, $full_description, $latitude, $longitude, $image_path, $category];
        $success = db_query($sql, $params);
        
        if ($success) {
            renumberTable('events'); // 🔁 Перенумерация после добавления
            $message = '✅ Событие добавлено! ID перенумерованы.';
        } else {
            $message = '❌ Ошибка добавления!';
        }
    }
}

// ✅ УДАЛЕНИЕ + перенумерация ID
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Сначала отвязываем героев от этого события
    db_query("UPDATE heroes SET event_id = NULL WHERE event_id = ?", [$delete_id]);
    
    // Затем удаляем событие
    $success = db_query("DELETE FROM events WHERE id = ?", [$delete_id]);
    
    if ($success) {
        renumberTable('events'); // 🔁 Перенумерация после удаления
        $message = '✅ Событие удалено! Герои отвязаны. ID перенумерованы.';
    } else {
        $message = '❌ Ошибка удаления!';
    }
}

// ✅ Редактирование — получение события
$editEvent = null;
if (isset($_GET['edit'])) {
    $result = db_query("SELECT * FROM events WHERE id = ?", [(int)$_GET['edit']]);
    if ($result && $result->num_rows > 0) {
        $editEvent = $result->fetch_assoc();
    }
}

// ✅ Список событий
$result = $mysqli->query("SELECT * FROM events ORDER BY id");
$events = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление событиями — Админ-панель</title>
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
                <li><a href="events.php" class="active">📋 События</a></li>
                <li><a href="heroes.php">🎖️ Герои</a></li>
                <li><a href="../index.php">🌐 На сайт</a></li>
                <li><a href="logout.php">🚪 Выход</a></li>
            </ul>
        </aside>

        <div class="admin-main">
            <?php if ($message): ?>
                <div class="animate-on-scroll" style="background: var(--accent-dark); color: var(--text-primary); padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--accent);">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- ФОРМА -->
            <h2><?= $editEvent ? 'Редактирование события' : 'Добавить событие' ?></h2>
            <form class="admin-form animate-on-scroll" method="POST" enctype="multipart/form-data">
                <?php if ($editEvent): ?>
                    <input type="hidden" name="id" value="<?= $editEvent['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Название события *</label>
                    <input type="text" name="title" required value="<?= htmlspecialchars($editEvent['title'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Год *</label>
                    <select name="year" required>
                        <?php for ($y = 1941; $y <= 1945; $y++): ?>
                            <option value="<?= $y ?>" <?= ($editEvent['year'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Дата начала</label>
                        <input type="date" name="date_start" value="<?= $editEvent['date_start'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Дата окончания</label>
                        <input type="date" name="date_end" value="<?= $editEvent['date_end'] ?? '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Категория</label>
                    <select name="category">
                        <option value="битва" <?= ($editEvent['category'] ?? '') == 'битва' ? 'selected' : '' ?>>Битва</option>
                        <option value="операция" <?= ($editEvent['category'] ?? '') == 'операция' ? 'selected' : '' ?>>Операция</option>
                        <option value="оборона" <?= ($editEvent['category'] ?? '') == 'оборона' ? 'selected' : '' ?>>Оборона</option>
                        <option value="освобождение" <?= ($editEvent['category'] ?? '') == 'освобождение' ? 'selected' : '' ?>>Освобождение</option>
                        <option value="другое" <?= ($editEvent['category'] ?? '') == 'другое' ? 'selected' : '' ?>>Другое</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Широта (latitude)</label>
                        <input type="text" step="any" name="latitude" placeholder="55.7558" value="<?= $editEvent['latitude'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Долгота (longitude)</label>
                        <input type="text" step="any" name="longitude" placeholder="37.6173" value="<?= $editEvent['longitude'] ?? '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Краткое описание</label>
                    <textarea name="description" rows="3"><?= htmlspecialchars($editEvent['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Полное описание</label>
                    <textarea name="full_description" rows="8"><?= htmlspecialchars($editEvent['full_description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Изображение</label>
                    <input type="file" name="image" accept="image/*">
                    <?php if (!empty($editEvent['image_path'])): ?>
                        <div style="margin-top: 10px;">
                            <img src="../<?= htmlspecialchars($editEvent['image_path']) ?>" style="max-width: 200px; border-radius: 5px;">
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="submit-btn"><?= $editEvent ? 'Сохранить изменения' : 'Добавить событие' ?></button>
                <?php if ($editEvent): ?>
                    <a href="events.php" class="filter-btn" style="margin-left: 10px; color: var(--text-secondary);">Отмена</a>
                <?php endif; ?>
            </form>

            <!-- ТАБЛИЦА -->
            <h2 style="margin-top: 40px;">Все события (<?= count($events) ?>)</h2>
            <div class="animate-on-scroll" style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Год</th>
                            <th>Категория</th>
                            <th style="width: 120px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 20px;">События не добавлены</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                            <tr>
                                <td><strong><?= $event['id'] ?></strong></td>
                                <td><?= htmlspecialchars($event['title']) ?></td>
                                <td><?= $event['year'] ?></td>
                                <td><?= htmlspecialchars($event['category']) ?></td>
                                <td>
                                    <a href="?edit=<?= $event['id'] ?>" class="action-btn edit" title="Редактировать">✏️</a>
                                    <a href="?delete=<?= $event['id'] ?>" class="action-btn delete" 
                                       onclick="return confirm('Удалить событие «<?= addslashes($event['title']) ?>»?\n\n⚠️ Все привязанные герои будут отвязаны!\n⚠️ Все ID будут перенумерованы!')" 
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