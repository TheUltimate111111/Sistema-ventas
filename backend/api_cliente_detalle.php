<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_activo'])) {
    http_response_code(401);
    echo json_encode(['estado' => 'error', 'mensaje' => 'No autenticado']);
    exit;
}

require_once 'includes/conexion.php';

$clienteId = (int)($_GET['cliente_id'] ?? 0);
if ($clienteId <= 0) {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'ID de cliente invalido']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            c.id, c.cedula, c.nombre_completo, c.correo, c.telefono, c.direccion, c.fecha_registro,
            COALESCE(SUM(CASE WHEN v.estado = 'Pagada' THEN v.total_factura ELSE 0 END), 0) AS total_gastado,
            COUNT(DISTINCT CASE WHEN v.estado = 'Pagada' THEN v.id END) AS num_compras,
            MAX(CASE WHEN v.estado = 'Pagada' THEN v.fecha_emision END) AS ultima_compra,
            MIN(CASE WHEN v.estado = 'Pagada' THEN v.fecha_emision END) AS primera_compra
        FROM clientes c
        LEFT JOIN ventas v ON c.id = v.cliente_id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$clienteId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        http_response_code(404);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Cliente no encontrado']);
        exit;
    }

    $ventasStmt = $pdo->prepare("
        SELECT v.id, v.fecha_emision, v.subtotal, v.iva, v.total_factura, v.monto_pagado, v.cambio, v.estado
        FROM ventas v
        WHERE v.cliente_id = ?
        ORDER BY v.fecha_emision DESC
    ");
    $ventasStmt->execute([$clienteId]);
    $ventas = $ventasStmt->fetchAll(PDO::FETCH_ASSOC);

    $ventaIds = array_column($ventas, 'id');
    $detallesPorVenta = [];

    if (!empty($ventaIds)) {
        $placeholders = implode(',', array_fill(0, count($ventaIds), '?'));
        $detallesStmt = $pdo->prepare("
            SELECT dv.venta_id, dv.cantidad, dv.precio_congelado, p.nombre_producto, p.codigo_barras
            FROM detalles_venta dv
            INNER JOIN productos p ON dv.producto_id = p.id
            WHERE dv.venta_id IN ($placeholders)
        ");
        $detallesStmt->execute($ventaIds);
        $todosDetalles = $detallesStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($todosDetalles as $d) {
            $detallesPorVenta[$d['venta_id']][] = [
                'nombre_producto' => $d['nombre_producto'],
                'codigo_barras' => $d['codigo_barras'],
                'cantidad' => (int)$d['cantidad'],
                'precio_congelado' => (float)$d['precio_congelado']
            ];
        }
    }

    foreach ($ventas as &$v) {
        $v['detalles'] = $detallesPorVenta[$v['id']] ?? [];
    }
    unset($v);

    $topProdStmt = $pdo->prepare("
        SELECT p.nombre_producto, SUM(dv.cantidad) AS cantidad_total, SUM(dv.cantidad * dv.precio_congelado) AS total_gastado
        FROM detalles_venta dv
        INNER JOIN productos p ON dv.producto_id = p.id
        INNER JOIN ventas v ON dv.venta_id = v.id
        WHERE v.cliente_id = ? AND v.estado = 'Pagada'
        GROUP BY p.id
        ORDER BY total_gastado DESC
        LIMIT 10
    ");
    $topProdStmt->execute([$clienteId]);
    $productosTop = $topProdStmt->fetchAll(PDO::FETCH_ASSOC);

    $mesStmt = $pdo->prepare("
        SELECT DATE_FORMAT(v.fecha_emision, '%Y-%m') AS mes, SUM(v.total_factura) AS total
        FROM ventas v
        WHERE v.cliente_id = ? AND v.estado = 'Pagada'
        GROUP BY mes
        ORDER BY mes ASC
    ");
    $mesStmt->execute([$clienteId]);
    $tendenciaMensual = $mesStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'estado' => 'success',
        'cliente' => $cliente,
        'ventas' => $ventas,
        'productos_top' => $productosTop,
        'tendencia_mensual' => $tendenciaMensual
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno']);
}
?>
