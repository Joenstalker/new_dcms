<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=dcms_new_db', 'root', '');
    $uCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $tCount = $pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
    echo "dcms_new_db: USERS=$uCount, TENANTS=$tCount\n";
    if ($tCount > 0) {
        $ids = $pdo->query("SELECT id FROM tenants")->fetchAll(PDO::FETCH_COLUMN);
        echo "  Tenant IDs: " . implode(',', $ids) . "\n";
    }
} catch (Exception $e) {
    echo "Error checking dcms_new_db: " . $e->getMessage() . "\n";
}
