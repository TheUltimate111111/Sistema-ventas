<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit;
}

require_once 'backend/includes/conexion.php';

$hasEstado = false;
$colsStmt = $pdo->prepare("SHOW COLUMNS FROM ventas LIKE 'estado'");
$colsStmt->execute();
if ($colsStmt->fetch()) {
    $hasEstado = true;
}

$estadoCol = $hasEstado ? "v.estado" : "'Pagada' AS estado";

$sql = "SELECT v.id, v.fecha_emision, c.nombre_completo AS cliente, c.cedula, u.usuario AS cajero, v.subtotal, v.iva, v.total_factura, v.monto_pagado, v.cambio, $estadoCol FROM ventas v INNER JOIN clientes c ON v.cliente_id = c.id INNER JOIN usuarios u ON v.usuario_id = u.id ORDER BY v.fecha_emision DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
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
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes countUp {
            from { opacity: 0; transform: scale(0.85); }
            to { opacity: 1; transform: scale(1); }
        }

        .stat-card {
            border-left: 4px solid var(--verde-medio);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            animation: fadeSlideUp 0.5s ease both;
        }
        .stat-card:nth-child(1) { animation-delay: 0s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.2s; }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.1) !important;
        }
        .stat-card .stat-value {
            animation: countUp 0.6s ease both;
            animation-delay: 0.3s;
        }
        .stat-card .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; color: #fff; flex-shrink: 0;
        }

        .filter-card {
            animation: fadeSlideUp 0.5s ease 0.15s both;
            border: none; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .filter-card .form-control,
        .filter-card .form-select {
            border-radius: 10px; border: 1.5px solid #e2e8f0;
            padding: 10px 14px; font-size: 0.92rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .filter-card .form-control:focus,
        .filter-card .form-select:focus {
            border-color: var(--verde-medio);
            box-shadow: 0 0 0 3px rgba(45,106,79,0.12);
        }

        .table-card {
            animation: fadeSlideUp 0.5s ease 0.3s both;
            border: none; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .table thead th {
            background: var(--verde-oscuro); color: #fff;
            font-weight: 600; font-size: 0.85rem; letter-spacing: 0.5px;
            text-transform: uppercase; border: none; padding: 14px 16px;
        }
        .table tbody tr {
            transition: background 0.15s, transform 0.15s;
        }
        .table tbody tr:hover {
            background: #f0faf4 !important;
            transform: scale(1.005);
        }
        .table tbody td {
            padding: 14px 16px; vertical-align: middle;
            border-color: #eef2f6; font-size: 0.93rem;
        }

        .badge-status {
            padding: 5px 14px; border-radius: 999px;
            font-size: 0.78rem; font-weight: 700; letter-spacing: 0.3px;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .badge-status::before {
            content: ''; width: 7px; height: 7px; border-radius: 50%;
        }
        .badge-pagada { background: #dcfce7; color: #166534; }
        .badge-pagada::before { background: #22c55e; }
        .badge-anulada { background: #fee2e2; color: #b91c1c; }
        .badge-anulada::before { background: #ef4444; }

        .btn-action {
            border-radius: 8px; font-size: 0.8rem; font-weight: 600;
            padding: 6px 12px; border: 1.5px solid; transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 4px;
            cursor: pointer; background: transparent;
        }
        .btn-action:hover { transform: translateY(-1px); }
        .btn-view { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
        .btn-view:hover { background: #3b82f6; color: #fff; }
        .btn-print { border-color: #8b5cf6; color: #8b5cf6; background: #f5f3ff; }
        .btn-print:hover { background: #8b5cf6; color: #fff; }
        .btn-void { border-color: #ef4444; color: #ef4444; background: #fef2f2; text-decoration: none; }
        .btn-void:hover { background: #ef4444; color: #fff; }

        .btn-verde {
            background: var(--verde-medio); color: #fff; border: none;
            border-radius: 10px; font-weight: 600; padding: 10px 24px;
            transition: all 0.2s;
        }
        .btn-verde:hover { background: var(--verde-oscuro); color: #fff; transform: translateY(-1px); }
        .btn-outline-secondary { border-radius: 10px; }

        .detail-overlay {
            display: none; position: fixed; inset: 0; z-index: 9999;
            background: rgba(6,20,12,0.55); backdrop-filter: blur(4px);
            align-items: center; justify-content: center; padding: 20px;
        }
        .detail-overlay.show { display: flex; }
        .detail-panel {
            background: #fff; border-radius: 20px; width: 100%; max-width: 600px;
            max-height: 85vh; overflow-y: auto;
            box-shadow: 0 30px 80px rgba(0,0,0,0.25);
            animation: fadeSlideUp 0.3s ease;
        }
        .detail-header {
            background: var(--verde-oscuro); color: #fff; padding: 20px 24px;
            border-radius: 20px 20px 0 0; display: flex; justify-content: space-between;
            align-items: center; position: sticky; top: 0; z-index: 1;
        }
        .detail-header h3 { margin: 0; font-size: 1.1rem; font-weight: 700; }
        .detail-close {
            width: 34px; height: 34px; border-radius: 10px; border: none;
            background: rgba(255,255,255,0.15); color: #fff; font-size: 1.1rem;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background 0.15s;
        }
        .detail-close:hover { background: rgba(255,255,255,0.3); }
        .detail-body { padding: 24px; }
        .detail-meta {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
            margin-bottom: 20px;
        }
        .detail-meta-item {
            background: #f8fafb; border-radius: 10px; padding: 12px 14px;
        }
        .detail-meta-item small { display: block; color: #6b7280; font-size: 0.78rem; margin-bottom: 4px; }
        .detail-meta-item strong { color: var(--verde-oscuro); font-size: 0.95rem; }
        .detail-products table { width: 100%; border-collapse: collapse; }
        .detail-products thead th {
            background: #f1f5f9; color: #475569; font-size: 0.78rem;
            text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 12px; border: none;
        }
        .detail-products tbody td {
            padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem;
        }
        .detail-totals {
            background: #f8fafb; border-radius: 12px; padding: 16px; margin-top: 16px;
        }
        .detail-totals .row-total {
            display: flex; justify-content: space-between; padding: 6px 0;
            font-size: 0.9rem; color: #475569;
        }
        .detail-totals .row-total.grand {
            border-top: 2px solid var(--verde-oscuro); margin-top: 8px;
            padding-top: 10px; font-weight: 700; font-size: 1.05rem; color: var(--verde-oscuro);
        }
        .detail-actions { display: flex; gap: 10px; margin-top: 20px; }
        .detail-actions .btn { flex: 1; border-radius: 10px; font-weight: 600; padding: 12px; }

        .receipt-overlay {
            display: none; position: fixed; inset: 0; z-index: 10000;
            background: rgba(6,20,12,0.55); backdrop-filter: blur(4px);
            align-items: center; justify-content: center; padding: 20px;
        }
        .receipt-overlay.show { display: flex; }
        .receipt-ticket {
            position: relative; width: 100%; max-width: 360px;
            background: #f7f5ec; color: #1d2b1f; border-radius: 6px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.45);
        }
        .receipt-dentado {
            height: 14px; background-color: #0f2e1f;
            background-image: linear-gradient(-45deg, #f7f5ec 8px, transparent 0), linear-gradient(45deg, #f7f5ec 8px, transparent 0);
            background-size: 16px 16px;
        }
        .receipt-dentado.top { transform: rotate(180deg); }
        .receipt-body { padding: 24px; }
        .receipt-close {
            position: absolute; top: 20px; right: 12px;
            width: 28px; height: 28px; border-radius: 50%; border: none;
            background: #e6efe1; color: #1c4d33; font-size: 14px;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
        }
        .receipt-close:hover { background: #d3e4ca; }
        .receipt-header { text-align: center; margin-bottom: 16px; }
        .receipt-sello {
            width: 42px; height: 42px; margin: 0 auto 8px; border-radius: 50%;
            background: #2f6b46; display: flex; align-items: center; justify-content: center;
            color: #fff; font-family: Georgia, serif; font-size: 18px; font-weight: 700;
            box-shadow: 0 0 0 3px #e6efe1, 0 0 0 4px #2f6b46;
        }
        .receipt-title {
            font-family: Georgia, 'Times New Roman', serif; letter-spacing: 3px;
            text-transform: uppercase; font-size: 18px; color: #1c4d33; margin: 0;
        }
        .receipt-subtitle { font-size: 10px; letter-spacing: 1.5px; color: #4f8f5f; text-transform: uppercase; margin-top: 3px; }
        .receipt-line { border: none; border-top: 2px dashed #d8dcc9; margin: 14px 0; }
        .receipt-meta { font-size: 12px; line-height: 1.8; color: #3a4a3c; }
        .receipt-meta .fila { display: flex; justify-content: space-between; }
        .receipt-meta b { color: #1d2b1f; }
        .receipt-table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 11px; }
        .receipt-table thead th {
            text-align: left; font-size: 9px; letter-spacing: 1px;
            text-transform: uppercase; color: #6d7a6a; padding-bottom: 5px;
            border-bottom: 1px solid #d8dcc9;
        }
        .receipt-table thead th:not(:first-child) { text-align: right; }
        .receipt-table tbody td { padding: 6px 0; vertical-align: top; color: #26332a; }
        .receipt-table tbody td:not(:first-child) { text-align: right; white-space: nowrap; }
        .receipt-table tbody tr { border-bottom: 1px dotted #d8dcc9; }
        .receipt-summary { margin-top: 12px; font-size: 12px; }
        .receipt-summary .fila { display: flex; justify-content: space-between; padding: 3px 0; color: #3a4a3c; }
        .receipt-summary .fila.total {
            margin-top: 6px; padding-top: 8px; border-top: 2px solid #1c4d33;
            font-size: 15px; font-weight: 700; color: #1c4d33;
        }
        .receipt-footer { text-align: center; margin-top: 16px; font-size: 10px; letter-spacing: 1px; color: #7b8a78; text-transform: uppercase; }
        .receipt-barcode {
            margin-top: 12px; height: 30px;
            background: repeating-linear-gradient(90deg, #0f2e1f 0 2px, transparent 2px 4px, #0f2e1f 4px 5px, transparent 5px 9px, #0f2e1f 9px 12px, transparent 12px 14px);
            opacity: 0.85; border-radius: 2px;
        }
        .receipt-actions { display: flex; gap: 10px; margin-top: 18px; }
        .receipt-actions button {
            flex: 1; padding: 10px; border-radius: 8px; border: none;
            font-family: inherit; font-size: 12px; letter-spacing: 0.5px;
            text-transform: uppercase; cursor: pointer; font-weight: 600;
        }
        .receipt-btn-print { background: #1c4d33; color: #f5f7f2; }
        .receipt-btn-close { background: transparent; color: #1c4d33; border: 1.5px solid #d8dcc9; }

        .empty-state { text-align: center; padding: 48px 20px; color: #94a3b8; }
        .empty-state svg { margin-bottom: 12px; opacity: 0.4; }

        @media print {
            .app-layout,
            .detail-overlay {
                display: none !important;
            }

            html, body {
                width: 80mm !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .receipt-overlay {
                display: block !important;
                position: static !important;
                background: transparent !important;
                backdrop-filter: none !important;
                padding: 0 !important;
            }

            .receipt-ticket {
                box-shadow: none !important;
                max-width: 80mm !important;
                width: 80mm !important;
                border-radius: 0 !important;
                margin: 0 auto !important;
            }

            .receipt-close,
            .receipt-actions {
                display: none !important;
            }

            .receipt-body {
                padding: 10px 6px 6px !important;
            }

            .receipt-sello {
                display: flex !important;
            }

            .receipt-meta .fila,
            .receipt-summary .fila {
                display: flex !important;
            }

            .receipt-table {
                display: table !important;
                width: 100% !important;
            }

            .receipt-table thead {
                display: table-header-group !important;
            }

            .receipt-table tbody {
                display: table-row-group !important;
            }

            .receipt-table tr {
                display: table-row !important;
            }

            .receipt-table th,
            .receipt-table td {
                display: table-cell !important;
            }

            .receipt-dentado {
                display: block !important;
            }

            @page {
                size: 80mm auto;
                margin: 2mm;
            }
        }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php include 'backend/includes/sidebar.php'; ?>
        <div id="content" class="w-100">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 p-3">
                <div class="container-fluid d-flex justify-content-between">
                    <span class="navbar-brand mb-0 h4 text-secondary">Historial de Ventas</span>
                    <div>
                        <span class="me-4 fw-bold" style="color:var(--verde-oscuro);">
                            <?php echo strtoupper($usuario['nombre']) . ' | Rol: ' . ucfirst($usuario['rol']); ?>
                        </span>
                        <a href="backend/includes/logout.php" class="btn btn-sm btn-outline-danger fw-bold">Cerrar Sesion</a>
                    </div>
                </div>
            </nav>
            <div class="container-fluid px-4">
                <div class="mb-4">
                    <h1 class="h3 mb-1">Historial de Ventas</h1>
                    <p class="text-muted mb-0" style="font-size:0.92rem;">Consulta, reimprime y administra facturas</p>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card shadow-sm">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                                </div>
                                <div>
                                    <small class="text-muted" style="font-size:0.78rem;">Total Vendido</small>
                                    <h4 class="mb-0 stat-value" style="color:var(--verde-oscuro);" id="statTotal">$<?php echo number_format($totales['total_vendido'], 2); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card shadow-sm">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 5h6"/></svg>
                                </div>
                                <div>
                                    <small class="text-muted" style="font-size:0.78rem;">Facturas</small>
                                    <h4 class="mb-0 stat-value" style="color:var(--verde-oscuro);" id="statCount"><?php echo $totales['ventas']; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card shadow-sm">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                                </div>
                                <div>
                                    <small class="text-muted" style="font-size:0.78rem;">Ticket Promedio</small>
                                    <h4 class="mb-0 stat-value" style="color:var(--verde-oscuro);" id="statAvg">$<?php echo number_format($totales['promedio'], 2); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card filter-card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label" style="font-size:0.8rem; font-weight:600; color:#64748b;">Buscar factura</label>
                                <input id="searchInput" class="form-control" type="search"
                                    placeholder="ID, cliente, cedula o cajero..." autocomplete="off">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" style="font-size:0.8rem; font-weight:600; color:#64748b;">Fecha Inicio</label>
                                <input id="filterFechaInicio" class="form-control" type="date">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" style="font-size:0.8rem; font-weight:600; color:#64748b;">Fecha Fin</label>
                                <input id="filterFechaFin" class="form-control" type="date">
                            </div>
                            <?php if ($hasEstado): ?>
                            <div class="col-md-2">
                                <label class="form-label" style="font-size:0.8rem; font-weight:600; color:#64748b;">Estado</label>
                                <select id="filterEstado" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="Pagada">Pagada</option>
                                    <option value="Anulada">Anulada</option>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-3 d-flex gap-2">
                                <button class="btn btn-verde" type="button" id="btnBuscar">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:4px;"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                                    Buscar
                                </button>
                                <button class="btn btn-outline-secondary" type="button" id="btnLimpiar">Limpiar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card table-card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>N Factura</th>
                                        <th>Fecha y Hora</th>
                                        <th>Cliente</th>
                                        <th>Cajero</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <?php if (count($ventas) === 0): ?>
                                        <tr><td colspan="7">
                                            <div class="empty-state">
                                                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 5h6"/></svg>
                                                <p class="mb-0">No hay ventas registradas con los filtros seleccionados.</p>
                                            </div>
                                        </td></tr>
                                    <?php else: ?>
                                        <?php foreach ($ventas as $venta): ?>
                                            <tr data-id="<?php echo (int)$venta['id']; ?>"
                                                data-cliente="<?php echo htmlspecialchars($venta['cliente'], ENT_QUOTES); ?>"
                                                data-cedula="<?php echo htmlspecialchars($venta['cedula'] ?? '', ENT_QUOTES); ?>"
                                                data-cajero="<?php echo htmlspecialchars($venta['cajero'], ENT_QUOTES); ?>"
                                                data-fecha="<?php echo htmlspecialchars(substr($venta['fecha_emision'], 0, 10), ENT_QUOTES); ?>"
                                                data-estado="<?php echo htmlspecialchars(($venta['estado'] ?? 'Pagada'), ENT_QUOTES); ?>"
                                                data-total="<?php echo (float)$venta['total_factura']; ?>">
                                                <td><strong style="color:var(--verde-oscuro);">#<?php echo htmlspecialchars((string)$venta['id'], ENT_QUOTES); ?></strong></td>
                                                <td><?php echo htmlspecialchars($venta['fecha_emision'], ENT_QUOTES); ?></td>
                                                <td><?php echo htmlspecialchars($venta['cliente'], ENT_QUOTES); ?></td>
                                                <td><?php echo htmlspecialchars($venta['cajero'], ENT_QUOTES); ?></td>
                                                <td><strong>$<?php echo number_format((float)$venta['total_factura'], 2); ?></strong></td>
                                                <td>
                                                    <span class="badge-status <?php echo ($venta['estado'] ?? 'Pagada') === 'Anulada' ? 'badge-anulada' : 'badge-pagada'; ?>">
                                                        <?php echo htmlspecialchars((string)($venta['estado'] ?? 'Pagada'), ENT_QUOTES); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <button class="btn-action btn-view" type="button" onclick="verDetalle(<?php echo (int)$venta['id']; ?>)" title="Ver detalles">
                                                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                            Ver
                                                        </button>
                                                        <button class="btn-action btn-print" type="button" onclick="reimprimir(<?php echo (int)$venta['id']; ?>)" title="Reimprimir">
                                                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                                            Reimprimir
                                                        </button>
                                                        <?php if (($venta['estado'] ?? 'Pagada') !== 'Anulada'): ?>
                                                            <a href="backend/anular_venta.php?id=<?php echo (int)$venta['id']; ?>" class="btn-action btn-void" onclick="return confirm('Seguro que deseas anular esta factura? El stock se restaurara automaticamente.')" title="Anular factura">
                                                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                                                                Anular
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr id="noResults" class="d-none"><td colspan="7" class="text-center text-muted py-4">No se encontraron ventas con los filtros seleccionados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="detailOverlay" class="detail-overlay" role="dialog" aria-modal="true">
        <div class="detail-panel">
            <div class="detail-header">
                <h3>Detalle de Factura <span id="detailId"></span></h3>
                <button class="detail-close" onclick="closeDetail()" aria-label="Cerrar">&times;</button>
            </div>
            <div class="detail-body">
                <div class="detail-meta" id="detailMeta"></div>
                <div class="detail-products">
                    <table>
                        <thead><tr><th>Producto</th><th>Codigo</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead>
                        <tbody id="detailProducts"></tbody>
                    </table>
                </div>
                <div class="detail-totals" id="detailTotals"></div>
                <div class="detail-actions">
                    <button class="btn btn-outline-secondary" onclick="closeDetail()">Cerrar</button>
                    <button class="btn btn-verde" id="detailPrintBtn" onclick="reimprimirFromDetail()">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:4px;"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        Reimprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="receiptOverlay" class="receipt-overlay" role="dialog" aria-modal="true">
        <div class="receipt-ticket">
            <div class="receipt-dentado top"></div>
            <div class="receipt-body" id="receiptBody"></div>
            <div class="receipt-dentado"></div>
        </div>
    </div>

    <script>
    let currentDetailVentaId = null;

    function formatMoney(v) { return '$' + Number(v).toFixed(2); }

    /* ---- FILTRADO AUTOMÁTICO CLIENT-SIDE ---- */
    var searchInput = document.getElementById('searchInput');
    var filterFechaInicio = document.getElementById('filterFechaInicio');
    var filterFechaFin = document.getElementById('filterFechaFin');
    var filterEstado = document.getElementById('filterEstado');
    var tableBody = document.getElementById('tableBody');
    var noResults = document.getElementById('noResults');
    var statTotal = document.getElementById('statTotal');
    var statCount = document.getElementById('statCount');
    var statAvg = document.getElementById('statAvg');
    var rows = Array.from(tableBody.querySelectorAll('tr[data-id]'));

    function filterTable() {
        var q = searchInput.value.trim().toLowerCase();
        var fi = filterFechaInicio ? filterFechaInicio.value : '';
        var ff = filterFechaFin ? filterFechaFin.value : '';
        var est = filterEstado ? filterEstado.value : '';
        var visibleCount = 0;
        var totalVendido = 0;

        rows.forEach(function(row) {
            var id = (row.dataset.id || '').toLowerCase();
            var cliente = (row.dataset.cliente || '').toLowerCase();
            var cedula = (row.dataset.cedula || '').toLowerCase();
            var cajero = (row.dataset.cajero || '').toLowerCase();
            var fecha = row.dataset.fecha || '';
            var estado = row.dataset.estado || '';
            var total = parseFloat(row.dataset.total) || 0;

            var matchSearch = q === '' || id.indexOf(q) !== -1 || cliente.indexOf(q) !== -1 || cedula.indexOf(q) !== -1 || cajero.indexOf(q) !== -1;
            var matchFecha = true;
            if (fi) matchFecha = matchFecha && fecha >= fi;
            if (ff) matchFecha = matchFecha && fecha <= ff;
            var matchEstado = est === '' || estado === est;

            var show = matchSearch && matchFecha && matchEstado;
            row.style.display = show ? '' : 'none';
            if (show) { visibleCount++; totalVendido += total; }
        });

        if (noResults) noResults.classList.toggle('d-none', visibleCount !== 0);
        var avg = visibleCount > 0 ? totalVendido / visibleCount : 0;
        if (statTotal) statTotal.textContent = formatMoney(totalVendido);
        if (statCount) statCount.textContent = visibleCount;
        if (statAvg) statAvg.textContent = formatMoney(avg);
    }

    searchInput.addEventListener('input', filterTable);
    if (filterFechaInicio) filterFechaInicio.addEventListener('change', filterTable);
    if (filterFechaFin) filterFechaFin.addEventListener('change', filterTable);
    if (filterEstado) filterEstado.addEventListener('change', filterTable);

    document.getElementById('btnBuscar').addEventListener('click', filterTable);
    document.getElementById('btnLimpiar').addEventListener('click', function() {
        searchInput.value = '';
        if (filterFechaInicio) filterFechaInicio.value = '';
        if (filterFechaFin) filterFechaFin.value = '';
        if (filterEstado) filterEstado.value = '';
        filterTable();
    });

    filterTable();

    /* ---- VER DETALLE ---- */
    async function verDetalle(ventaId) {
        currentDetailVentaId = ventaId;
        var overlay = document.getElementById('detailOverlay');
        overlay.classList.add('show');
        document.getElementById('detailId').textContent = '#' + ventaId;
        document.getElementById('detailMeta').innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#6b7280;padding:20px;">Cargando...</div>';
        document.getElementById('detailProducts').innerHTML = '';
        document.getElementById('detailTotals').innerHTML = '';

        try {
            var res = await fetch('backend/api_detalle_venta.php?venta_id=' + ventaId);
            var data = await res.json();
            if (data.estado !== 'success') throw new Error(data.mensaje);
            var v = data.venta;
            var estadoClass = v.estado === 'Anulada' ? 'badge-anulada' : 'badge-pagada';

            document.getElementById('detailMeta').innerHTML =
                '<div class="detail-meta-item"><small>Fecha y Hora</small><strong>' + v.fecha_emision + '</strong></div>' +
                '<div class="detail-meta-item"><small>Estado</small><span class="badge-status ' + estadoClass + '" style="font-size:0.85rem;">' + v.estado + '</span></div>' +
                '<div class="detail-meta-item"><small>Cliente</small><strong>' + v.cliente + (v.cedula ? ' (' + v.cedula + ')' : '') + '</strong></div>' +
                '<div class="detail-meta-item"><small>Cajero</small><strong>' + v.cajero + '</strong></div>';

            document.getElementById('detailProducts').innerHTML = data.detalles.map(function(d) {
                return '<tr><td><strong>' + d.nombre_producto + '</strong></td>' +
                    '<td style="color:#6b7280;font-size:0.82rem;">' + (d.codigo_barras || '-') + '</td>' +
                    '<td style="text-align:center;">' + d.cantidad + '</td>' +
                    '<td style="text-align:right;">' + formatMoney(d.precio_congelado) + '</td>' +
                    '<td style="text-align:right;"><strong>' + formatMoney(d.precio_congelado * d.cantidad) + '</strong></td></tr>';
            }).join('');

            document.getElementById('detailTotals').innerHTML =
                '<div class="row-total"><span>Subtotal</span><span>' + formatMoney(v.subtotal) + '</span></div>' +
                '<div class="row-total"><span>IVA 15%</span><span>' + formatMoney(v.iva) + '</span></div>' +
                '<div class="row-total grand"><span>Total</span><span>' + formatMoney(v.total_factura) + '</span></div>' +
                '<div class="row-total"><span>Pagado</span><span>' + formatMoney(v.monto_pagado) + '</span></div>' +
                '<div class="row-total"><span>Cambio</span><span>' + formatMoney(v.cambio) + '</span></div>';
        } catch (e) {
            document.getElementById('detailMeta').innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#ef4444;padding:20px;">Error al cargar los detalles.</div>';
        }
    }

    function closeDetail() { document.getElementById('detailOverlay').classList.remove('show'); currentDetailVentaId = null; }
    function reimprimirFromDetail() { if (currentDetailVentaId) reimprimir(currentDetailVentaId); }

    /* ---- REIMPRIMIR ---- */
    async function reimprimir(ventaId) {
        var overlay = document.getElementById('receiptOverlay');
        var body = document.getElementById('receiptBody');
        body.innerHTML = '<div style="text-align:center;padding:30px;color:#6b7280;">Cargando...</div>';
        overlay.classList.add('show');

        try {
            var res = await fetch('backend/api_detalle_venta.php?venta_id=' + ventaId);
            var data = await res.json();
            if (data.estado !== 'success') throw new Error(data.mensaje);
            var v = data.venta;
            var rows = data.detalles.map(function(d) {
                return '<tr><td style="font-weight:700;">' + d.nombre_producto + '</td><td>' + d.cantidad + '</td><td>' + formatMoney(d.precio_congelado) + '</td><td>' + formatMoney(d.precio_congelado * d.cantidad) + '</td></tr>';
            }).join('');

            body.innerHTML =
                '<div class="receipt-header"><div class="receipt-sello">F</div><h1 class="receipt-title">Factura</h1><div class="receipt-subtitle">Comprobante de venta</div></div>' +
                '<hr class="receipt-line">' +
                '<div class="receipt-meta"><div class="fila"><span>Factura ID</span><b>#' + v.id + '</b></div><div class="fila"><span>Fecha</span><b>' + v.fecha_emision + '</b></div><div class="fila"><span>Cliente</span><b>' + v.cliente + '</b></div><div class="fila"><span>Cajero</span><b>' + v.cajero + '</b></div></div>' +
                '<table class="receipt-table"><thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Total</th></tr></thead><tbody>' + rows + '</tbody></table>' +
                '<div class="receipt-summary"><div class="fila"><span>Subtotal</span><span>' + formatMoney(v.subtotal) + '</span></div><div class="fila"><span>IVA 15%</span><span>' + formatMoney(v.iva) + '</span></div><div class="fila total"><span>Total</span><span>' + formatMoney(v.total_factura) + '</span></div><div class="fila"><span>Pagado</span><b>' + formatMoney(v.monto_pagado) + '</b></div><div class="fila"><span>Cambio</span><b>' + formatMoney(v.cambio) + '</b></div></div>' +
                '<div class="receipt-barcode"></div>' +
                '<div class="receipt-footer">Gracias por su compra</div>' +
                '<div class="receipt-actions"><button class="receipt-btn-close" onclick="closeReceipt()">Cerrar</button><button class="receipt-btn-print" onclick="window.print()">Imprimir</button></div>';
        } catch (e) {
            body.innerHTML = '<div style="text-align:center;padding:30px;color:#ef4444;">Error al cargar la factura.</div>';
        }
    }

    function closeReceipt() { document.getElementById('receiptOverlay').classList.remove('show'); }

    document.getElementById('detailOverlay').addEventListener('click', function(e) { if (e.target === this) closeDetail(); });
    document.getElementById('receiptOverlay').addEventListener('click', function(e) { if (e.target === this) closeReceipt(); });
    </script>
</body>
</html>
