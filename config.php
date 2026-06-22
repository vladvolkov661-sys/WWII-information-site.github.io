<?php
session_start();

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'voyna_db');

// Простое подключение через mysqli
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_error) {
    die("Ошибка подключения к БД: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");

// Класс-обёртка для совместимости с PDO синтаксисом
class Database {
    private $mysqli;
    public static $instance = null;
    
    private function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            global $mysqli;
            self::$instance = new self($mysqli);
        }
        return self::$instance;
    }
    
    public function prepare($sql) {
        return new Statement($this->mysqli, $sql);
    }
    
    public function query($sql) {
        return $this->mysqli->query($sql);
    }
}

// Класс для подготовленных выражений
class Statement {
    private $mysqli;
    private $sql;
    private $stmt;
    
    public function __construct($mysqli, $sql) {
        $this->mysqli = $mysqli;
        $this->sql = $sql;
        $this->stmt = $mysqli->prepare($sql);
    }
    
    public function execute($params = []) {
        if (empty($params)) {
            return $this->stmt->execute();
        }
        
        $types = '';
        $values = [];
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_string($param)) {
                $types .= 's';
            } elseif (is_double($param)) {
                $types .= 'd';
            } else {
                $types .= 'b';
            }
            $values[] = $param;
        }
        
        $this->stmt->bind_param($types, ...$values);
        return $this->stmt->execute();
    }
    
    public function fetchAll() {
        $result = $this->stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    public function fetch() {
        $result = $this->stmt->get_result();
        return $result->fetch_assoc();
    }
}

// Создаём экземпляр
$pdo = Database::getInstance();

define('SITE_NAME', 'Великая Отечественная Война');
define('BASE_URL', 'http://localhost/voyna');
?>