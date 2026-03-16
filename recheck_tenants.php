<?php
$pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
foreach (['new_dcms_db', 'dcms_saas_new'] as $db) {
    try {
        $stmt = $pdo->query("SELECT id FROM `$db`.`tenants` ");
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "DATABASE $db: IDs=" . implode(',', $ids) . "\n";
    } catch (Exception $e) {
        echo "DATABASE $db: Error or no table.\n";
    }
}
