<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit;
}

require_once 'backend/includes/conexion.php';

$search = trim((string)($_GET['q'] ?? ''));
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin = $_GET['fecha_fin'] ?? '';
$estadoFiltro = $_GET['estado'] ?? '';
$params = [];

$sql = 'SELECT v.id, v.fecha_emision, c.nombre_completo AS cliente, u.usuario AS cajero, v.subtotal, v.iva, v.total_factura, v.monto_pagado, v.cambio, v.estado FROM ventas v INNER JOIN clientes c ON v.cliente_id = c.id INNER JOIN usuarios u ON v.usuario_id = u.id WHERE 1=1';

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
if ($estadoFiltro !== '') {
    $sql .= ' AND v.estado = ?';
    $params[] = $estadoFiltro;
}
$sql .= ' ORDER BY v.fecha_emision DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totales = [
    'ventas' => count($ventas),
    'total_vendido' => array_sum(array_column($ventas, 'total_factura')),
    'promedio' => count($ventas) > 0 ? array_sum(array_column($ventas, 'total_factura')) / count($ventas) : 0
];

$usuario = $_SESSION['usuario_activo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Ventas - Sistema de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
    <style>
        .btn-verde {background-color: var(--verde-oscuro); color: white;}
        .btn-verde:hover {background-color: var(--verde-medio); color: white;}
        .badge-status {padding: 0.4rem 0.7rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600;}
        .badge-pagada {background: #dcfce7; color: #166534;}
        .badge-anulada {background: #fee2e2; color: #b91c1c;}
        .card-stat {border-left: 4px solid var(--verde-medio);}
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'backend/includes/sidebar.php'; ?>
        <div id="content" class="w-100">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 p-3">
                <div class="container-fluid d-flex justify-content-between">
                    <span class="navbar-brand mb-0 h4 text-secondary">Historial de Ventas</span>
                    <div>
                        <span class="me-4 fw-bold" style="color:var(--verde-oscuro);">
                            <?php echo strtoupper($usuario['nombre']) . ' | Rol: ' . ucfirst($usuario['rol']); ?>
                        </span>
                        <a href="backend/includes/logout.php" class="btn btn-sm btn-outline-danger fw-bold">Cerrar Sesión</a>
                    </div>
                </div>
            </nav>
            <div class="container-fluid px-4">
                <div class="mb-4">
                    <h1 class="h3 mb-1">Historial de Ventas</h1>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card card-stat shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted mb-1">Total Vendido</h6>
                                <h4 class="mb-0">$<?php echo number_format($totales['total_vendido'], 2); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stat shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted mb-1">Cantidad de Facturas</h6>
                                <h4 class="mb-0"><?php echo $totales['ventas']; ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stat shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted mb-1">Ticket Promedio</h6>
                                <h4 class="mb-0">$<?php echo number_format($totales['promedio'], 2); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form class="row g-2" method="GET" action="historial.php">
                            <div class="col-md-3">
                                <input name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>" class="form-control" type="search" placeholder="Buscar por ID, cliente, cédula o cajero" aria-label="Buscar">
                            </div>
                            <div class="col-md-2">
                                <input name="fecha_inicio" value="<?php echo htmlspecialchars($fechaInicio, ENT_QUOTES); ?>" class="form-control" type="date">
                            </div>
                            <div class="col-md-2">
                                <input name="fecha_fin" value="<?php echo htmlspecialchars($fechaFin, ENT_QUOTES); ?>" class="form-control" type="date">
                            </div>
                            <div class="col-md-2">
                                <select name="estado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="Pagada" <?php echo $estadoFiltro === 'Pagada' ? 'selected' : ''; ?>>Pagada</option>
                                    <option value="Anulada" <?php echo $estadoFiltro === 'Anulada' ? 'selected' : ''; ?>>Anulada</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button class="btn btn-verde" type="submit">Buscar</button>
                                <a href="historial.php" class="btn btn-outline-secondary">Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Cajero</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($ventas) === 0): ?>
                                        <tr><td colspan="7" class="text-center text-muted py-4">No hay ventas registradas.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($ventas as $venta): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars((string)$venta['id'], ENT_QUOTES); ?></td>
                                                <td><?php echo htmlspecialchars($venta['fecha_emision'], ENT_QUOTES); ?></td>
                                                <td><?php echo htmlspecialchars($venta['cliente'], ENT_QUOTES); ?></td>
                                                <td><?php echo htmlspecialchars($venta['cajero'], ENT_QUOTES); ?></td>
                                                <td><?php echo '$' . number_format((float)$venta['total_factura'], 2); ?></td>
                                                <td>
                                                    <span class="badge-status <?php echo ($venta['estado'] ?? 'Pagada') === 'Anulada' ? 'badge-anulada' : 'badge-pagada'; ?>">
                                                        <?php echo htmlspecialchars((string)($venta['estado'] ?? 'Pagada'), ENT_QUOTES); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" type="button">Ver</button>
                                                        <button class="btn btn-outline-secondary" type="button">Reimprimir</button>
                                                        <a href="backend/anular_venta.php?id=<?php echo (int)$venta['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('¿Seguro que deseas anular esta factura?');">Anular</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
