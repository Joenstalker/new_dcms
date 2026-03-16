<?php
$pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
foreach (['sql_join_acero', 'acero_laravel_lab', 'dcms_new_db', 'dcms_saas_new', 'new_dcms_db'] as $db) {
    echo "--- DB: $db ---\n";
    try {
        $stmt = $pdo->query("SHOW TABLES FROM `$db` LIKE 'tenants' ");
        if ($stmt->fetch()) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$db`.`tenants` ")->fetchColumn();
            echo "  TENANTS: $count\n";
            if ($count > 0) {
                $ids = $pdo->query("SELECT id FROM `$db`.`tenants` ")->fetchAll(PDO::FETCH_COLUMN);
                echo "    IDs: " . implode(',', $ids) . "\n";
            }
        }
        
        $stmt = $pdo->query("SHOW TABLES FROM `$db` LIKE 'users' ");
        if ($stmt->fetch()) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$db`.`users` ")->fetchColumn();
            echo "  USERS: $count\n";
            if ($count > 0) {
                $emails = $pdo->query("SELECT email FROM `$db`.`users` LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
                echo "    Top Emails: " . implode(',', $emails) . "\n";
            }
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}
