<?php
// Temporary DB connection test - remove after use
try {
    $mysql_url = getenv('MYSQL_URL') ?: null;
    if ($mysql_url) {
        $parts = parse_url($mysql_url);
        $host = $parts['host'] ?? null;
        $port = $parts['port'] ?? 3306;
        $user = isset($parts['user']) ? rawurldecode($parts['user']) : null;
        $pass = isset($parts['pass']) ? rawurldecode($parts['pass']) : null;
        $db = ltrim($parts['path'] ?? '', '/');
    } else {
        $host = getenv('MYSQLHOST') ?: getenv('RAILWAY_PRIVATE_DOMAIN') ?: 'localhost';
        $port = getenv('MYSQLPORT') ?: 3306;
        $user = getenv('MYSQLUSER') ?: 'root';
        $pass = getenv('MYSQLPASSWORD') ?: '';
        $db = getenv('MYSQLDATABASE') ?: 'railway';
    }

    echo "Trying connection to MySQL:\n";
    echo "host=$host\nport=$port\ndb=$db\nuser=$user\n";

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "OK: connected to MySQL\n";

    // show list of tables (safe readonly)
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables (" . count($tables) . "): " . implode(', ', $tables) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("[db_test] " . $e->getMessage());
}
?>
