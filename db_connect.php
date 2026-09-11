<?php
/**
 * FarmFlow Database Connection Handler
 * Supports multi-port (3306, 3307), multi-dbname fallback (farmflow_db, farmflow),
 * and automatic schema initialization if tables are missing.
 */

$config = require __DIR__ . '/config.php';
$db = $config['db'];

$host = $db['host'];
$ports = $db['ports'];
$db_names = $db['names'];
$db_user = $db['user'];
$db_pass = $db['pass'];
$charset = $db['charset'];
$timeout = $db['timeout'];

$pdo = null;
$connected_db = null;

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$schemaFile = $db['schema'];

foreach ($ports as $port) {
    // Connect to the MySQL server (no database) with a short timeout so
    // unavailable servers fail fast instead of hanging for many seconds.
    $rootDsn = "mysql:host={$host};port={$port};charset={$charset};connect_timeout={$timeout}";
    try {
        $rootPdo = new PDO($rootDsn, $db_user, $db_pass, $pdoOptions);
    } catch (PDOException $e) {
        continue; // MySQL is not running on this port
    }

    foreach ($db_names as $db_name) {
        try {
            // Create the database if it is missing
            $dbExists = $rootPdo->query("SHOW DATABASES LIKE " . $rootPdo->quote($db_name))->fetch();
            if (!$dbExists) {
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            }

            $rootPdo->exec("USE `{$db_name}`;");

            // Verify / initialize the 'users' table
            $tableCheck = $rootPdo->query("SHOW TABLES LIKE 'users'")->fetch();
            if (!$tableCheck && file_exists($schemaFile)) {
                $rootPdo->exec(file_get_contents($schemaFile));
            }

            $pdo = $rootPdo;
            $connected_db = $db_name;
            break 2;
        } catch (PDOException $e) {
            continue; // Try the next database name on this port
        }
    }
}

if (!$pdo) {
    die("Database Connection Error: Could not connect or initialize MySQL database on port 3306 or 3307. Please ensure Apache & MySQL are started in XAMPP Control Panel.");
}
?>
