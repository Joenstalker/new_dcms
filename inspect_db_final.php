<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=new_dcms_db', 'root', '');
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "TABLES FOUND: " . count($tables) . "\n";
foreach ($tables as $t) {
    echo " - $t\n";
}

if (in_array('migrations', $tables)) {
    $stmt = $pdo->query("SELECT migration FROM migrations");
    echo "MIGRATIONS RUN: " . $stmt->rowCount() . "\n";
}
