<?php
require 'includes/conexion.php';
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo ' - ' . $col['Field'] . ' ' . $col['Type'] . ' ' . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }
    echo "\n";
}
