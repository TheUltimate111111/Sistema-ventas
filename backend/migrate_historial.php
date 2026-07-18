<?php
require 'includes/conexion.php';

$queries = [
    "ALTER TABLE ventas ADD COLUMN IF NOT EXISTS estado VARCHAR(20) NOT NULL DEFAULT 'Pagada'"
];

foreach ($queries as $sql) {
    $pdo->exec($sql);
}

$pdo->exec("UPDATE ventas SET estado = 'Pagada' WHERE estado IS NULL OR estado = ''");

echo "Migración completada\n";
