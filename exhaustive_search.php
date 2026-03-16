<?php
$pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
$stmt = $pdo->query('SHOW DATABASES');
$databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($databases as $db) {
    if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) continue;
    
    echo "DB: $db\n";
    try {
        $uCount = 0;
        $tCount = 0;
        
        $s = $pdo->query("SHOW TABLES FROM `$db` LIKE 'users' ");
        if ($s->fetch()) {
            $uCount = $pdo->query("SELECT COUNT(*) FROM `$db`.`users` WHERE email = 'admin@dcms.com' ")->fetchColumn();
        }
        
        $s = $pdo->query("SHOW TABLES FROM `$db` LIKE 'tenants' ");
        if ($s->fetch()) {
            $tCount = $pdo->query("SELECT COUNT(*) FROM `$db`.`tenants` ")->fetchColumn();
        }
        
        echo "  Admin User? " . ($uCount > 0 ? "YES" : "NO") . "\n";
        echo "  Tenants? " . $tCount . "\n";
        
        if ($uCount > 0 && $tCount > 0) {
            echo "  *** THIS IS THE ONE! ***\n";
        }
    } catch (Exception $e) {}
}
