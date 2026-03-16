<?php
$pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
$stmt = $pdo->query('SHOW DATABASES');
$dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
file_put_contents('all_dbs_list.txt', implode("\n", $dbs));
echo "SUCCESS: Wrote " . count($dbs) . " databases to all_dbs_list.txt\n";
