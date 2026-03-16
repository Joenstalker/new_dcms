<?php
$pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
$stmt = $pdo->query('SHOW DATABASES');
$databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($databases as $db) {
    if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) continue;
    
    echo "\n--- DATABASE: $db ---\n";
    try {
        $stmt2 = $pdo->query("SHOW TABLES FROM `$db` ");
        $tables = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('users', $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$db`.`users` ")->fetchColumn();
            echo "  USERS: $count\n";
            if ($count > 0) {
                $emails = $pdo->query("SELECT email FROM `$db`.`users` LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
                echo "    Emails: " . implode(', ', $emails) . "\n";
            }
        }
        
        if (in_array('tenants', $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$db`.`tenants` ")->fetchColumn();
            echo "  TENANTS: $count\n";
            if ($count > 0) {
                $ids = $pdo->query("SELECT id FROM `$db`.`tenants` LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
                echo "    IDs: " . implode(', ', $ids) . "\n";
            }
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}
