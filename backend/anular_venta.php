<?php
session_start();
if (!isset($_SESSION['usuario_activo'])) {
    header('Location: ../index.php');
    exit;
}

require_once 'includes/conexion.php';

$ventaId = (int)($_GET['id'] ?? 0);
if ($ventaId <= 0) {
    header('Location: ../historial.php');
    exit;
}

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare('SELECT id, estado FROM ventas WHERE id = ? LIMIT 1');
    $stmt->execute([$ventaId]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }

    if (($venta['estado'] ?? 'Pagada') === 'Anulada') {
        throw new Exception('La venta ya fue anulada');
    }

    $detalleStmt = $pdo->prepare('SELECT producto_id, cantidad FROM detalles_venta WHERE venta_id = ?');
    $detalleStmt->execute([$ventaId]);
    $detalles = $detalleStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($detalles as $detalle) {
        $productoStmt = $pdo->prepare('SELECT stock_disponible FROM productos WHERE id = ? LIMIT 1');
        $productoStmt->execute([(int)$detalle['producto_id']]);
        $producto = $productoStmt->fetch(PDO::FETCH_ASSOC);

        if ($producto) {
            $updateStock = $pdo->prepare('UPDATE productos SET stock_disponible = stock_disponible + ? WHERE id = ?');
            $updateStock->execute([(int)$detalle['cantidad'], (int)$detalle['producto_id']]);
        }
    }

    $updateVenta = $pdo->prepare('UPDATE ventas SET estado = ? WHERE id = ?');
    $updateVenta->execute(['Anulada', $ventaId]);

    $pdo->commit();
    header('Location: ../historial.php');
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ../historial.php');
    exit;
}