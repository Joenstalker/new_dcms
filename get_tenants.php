<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=new_dcms_db', 'root', '');
    $stmt = $pdo->query("SELECT id FROM tenants");
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "TENANT_IDS: " . implode(',', $ids) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
