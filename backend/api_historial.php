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

$hasEstado = false;
$colsStmt = $pdo->prepare("SHOW COLUMNS FROM ventas LIKE 'estado'");
$colsStmt->execute();
if ($colsStmt->fetch()) {
    $hasEstado = true;
}

$estadoCol = $hasEstado ? "v.estado" : "'Pagada' AS estado";

$search = trim((string)($_GET['q'] ?? ''));
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin = $_GET['fecha_fin'] ?? '';
$estadoFiltro = $_GET['estado'] ?? '';
$params = [];

$sql = "SELECT v.id, v.fecha_emision, c.nombre_completo AS cliente, u.usuario AS cajero, v.total_factura, $estadoCol
        FROM ventas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        INNER JOIN usuarios u ON v.usuario_id = u.id
        WHERE 1=1";

if ($search !== '') {
    $sql .= ' AND (v.id = ? OR c.nombre_completo LIKE ? OR c.cedula LIKE ? OR u.usuario LIKE ?)';
    $params = [$search, "%$search%", "%$search%", "%$search%"];
}
if ($fechaInicio !== '') {
    $sql .= ' AND DATE(v.fecha_emision) >= ?';
    $params[] = $fechaInicio;
}
if ($fechaFin !== '') {
    $sql .= ' AND DATE(v.fecha_emision) <= ?';
    $params[] = $fechaFin;
}
if ($hasEstado && $estadoFiltro !== '') {
    $sql .= ' AND v.estado = ?';
    $params[] = $estadoFiltro;
}
$sql .= ' ORDER BY v.fecha_emision DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'estado' => 'success',
    'ventas' => $ventas,
    'totales' => [
        'cantidad' => count($ventas),
        'total_vendido' => (float)array_sum(array_column($ventas, 'total_factura')),
        'promedio' => count($ventas) > 0 ? (float)array_sum(array_column($ventas, 'total_factura')) / count($ventas) : 0
    ]
]);
