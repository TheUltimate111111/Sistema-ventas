<?php
require 'includes/conexion.php';
$tables = ['ventas', 'detalles_venta', 'clientes', 'productos', 'usuarios'];
foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo ' - ' . $col['Field'] . ' ' . $col['Type'] . ' ' . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }
    echo "\n";
}
