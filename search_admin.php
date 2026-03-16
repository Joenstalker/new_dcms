<?php
$pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
$stmt = $pdo->query('SHOW DATABASES');
$databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($databases as $db) {
    if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) continue;
    
    try {
        $stmt2 = $pdo->query("SELECT COUNT(*) FROM `$db`.`users` WHERE email = 'admin@dcms.com' ");
        if ($stmt2) {
            $count = $stmt2->fetchColumn();
            if ($count > 0) {
                echo "FOUND admin@dcms.com in database: $db\n";
            }
        }
    } catch (Exception $e) {
        // Table might not exist
    }
}
