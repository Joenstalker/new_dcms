<?php
$pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
$stmt = $pdo->query('SHOW DATABASES');
$databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($databases as $db) {
    if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) continue;
    
    try {
        $stmt2 = $pdo->query("SHOW TABLES FROM `$db` LIKE 'tenants' ");
        if ($stmt2->fetch()) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$db`.`tenants` ")->fetchColumn();
            echo "DATABASE: $db HAS tenants table with $count records.\n";
            if ($count > 0) {
                $ids = $pdo->query("SELECT id FROM `$db`.`tenants` ")->fetchAll(PDO::FETCH_COLUMN);
                echo "  IDs: " . implode(', ', $ids) . "\n";
            }
        }
    } catch (Exception $e) {}
}
