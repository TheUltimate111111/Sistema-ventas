<?php
require 'includes/conexion.php';
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo $table . PHP_EOL;
}
