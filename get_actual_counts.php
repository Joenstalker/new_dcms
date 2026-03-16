<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=new_dcms_db', 'root', '');
$countT = $pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
$countU = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
echo "ACTUAL ROWS IN new_dcms_db:\n";
echo "TENANTS: $countT\n";
echo "USERS: $countU\n";
