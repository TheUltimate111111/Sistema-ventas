<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit;
}

$usuario = $_SESSION['usuario_activo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM - Clientes | Sistema POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
    <link rel="stylesheet" href="frontend/css/clientes.css">
</head>
<body>
    <div class="app-layout">
        <?php include 'backend/includes/sidebar.php'; ?>
        <div id="content" class="w-100">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 p-3">
                <div class="container-fluid d-flex justify-content-between">
                    <span class="navbar-brand mb-0 h4 text-secondary">CRM - Clientes</span>
                    <div>
                        <span class="me-4 fw-bold" style="color:var(--verde-oscuro);">
                            <?php echo strtoupper($usuario['nombre']) . ' | Rol: ' . ucfirst($usuario['rol']); ?>
                        </span>
                        <a href="backend/includes/logout.php" class="btn btn-sm btn-outline-danger fw-bold">Cerrar Sesion</a>
                    </div>
                </div>
            </nav>

            <div class="container-fluid px-4">
                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="crm-stat-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="crm-stat-icon" style="background:linear-gradient(135deg,#22c55e,#16a34a);">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                                </div>
                                <div>
                                    <div class="crm-stat-label">Total Clientes</div>
                                    <div class="crm-stat-value" id="statTotalClientes">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="crm-stat-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="crm-stat-icon" style="background:linear-gradient(135deg,#3b82f6,#2563eb);">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                                </div>
                                <div>
                                    <div class="crm-stat-label">Total Facturado</div>
                                    <div class="crm-stat-value" id="statTotalFacturado">$0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="crm-stat-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="crm-stat-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 5h6"/></svg>
                                </div>
                                <div>
                                    <div class="crm-stat-label">Ticket Promedio</div>
                                    <div class="crm-stat-value" id="statTicketPromedio">$0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="crm-stat-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="crm-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </div>
                                <div>
                                    <div class="crm-stat-label">Clientes Activos</div>
                                    <div class="crm-stat-value" id="statActivos">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="card crm-charts-card shadow-sm mb-4">
                    <div class="card-header crm-charts-header" id="crmChartsToggle" role="button" data-bs-toggle="collapse" data-bs-target="#crmChartsBody" aria-expanded="true" aria-controls="crmChartsBody">
                        <div class="d-flex align-items-center gap-2">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                            <span>Panel de Graficas</span>
                        </div>
                        <svg class="crm-charts-chevron" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <div id="crmChartsBody" class="collapse show">
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-lg-4 col-md-6">
                                    <div class="crm-chart-box">
                                        <div class="crm-chart-title">Distribucion por Clasificacion</div>
                                        <div id="crmDonutChart" class="crm-donut-container"></div>
                                        <div id="crmDonutLegend" class="crm-donut-legend"></div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="crm-chart-box">
                                        <div class="crm-chart-title">Ingresos por Clasificacion</div>
                                        <div id="crmRevenueChart" class="crm-revenue-chart"></div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <div class="crm-chart-box">
                                        <div class="crm-chart-title">Top 10 Clientes</div>
                                        <div id="crmTopClientsChart" class="crm-top-chart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card crm-filters-card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">Buscar</label>
                                <input id="crmSearch" class="form-control" type="text" placeholder="Nombre, cedula o correo..." autocomplete="off">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label">Clasificacion</label>
                                <select id="crmFilterClasificacion" class="form-select">
                                    <option value="">Todas</option>
                                    <option value="premium">Premium</option>
                                    <option value="frecuente">Frecuente</option>
                                    <option value="nuevo">Nuevo</option>
                                    <option value="inactivo">Inactivo</option>
                                    <option value="sin-compras">Sin compras</option>
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label">Registro desde</label>
                                <input id="crmFilterFechaDesde" class="form-control" type="date">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label">Registro hasta</label>
                                <input id="crmFilterFechaHasta" class="form-control" type="date">
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">Ordenar por</label>
                                <select id="crmFilterOrdenar" class="form-select">
                                    <option value="id-asc">ID (asc)</option>
                                    <option value="id-desc">ID (desc)</option>
                                    <option value="nombre-asc">Nombre A-Z</option>
                                    <option value="nombre-desc">Nombre Z-A</option>
                                    <option value="gasto-desc">Mayor gasto</option>
                                    <option value="gasto-asc">Menor gasto</option>
                                    <option value="compras-desc">Mas compras</option>
                                    <option value="ultima-desc">Ultima compra</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mt-1 align-items-end">
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label">Gasto minimo</label>
                                <input id="crmFilterGastoMin" class="form-control" type="number" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label">Gasto maximo</label>
                                <input id="crmFilterGastoMax" class="form-control" type="number" step="0.01" min="0" placeholder="Sin limite">
                            </div>
                            <div class="col-md-8 col-sm-6 d-flex justify-content-end gap-2">
                                <button class="btn" id="crmBtnBuscar" type="button" style="background:var(--verde-medio);color:#fff;border:none;border-radius:10px;padding:10px 20px;font-weight:600;">Buscar</button>
                                <button class="btn btn-outline-secondary" id="crmBtnLimpiar" type="button" style="border-radius:10px;padding:10px 20px;">Limpiar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="card crm-table-card shadow-sm">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center px-4 pt-3">
                            <span class="text-muted" style="font-size:0.88rem;">Clientes encontrados: <strong id="crmTotalFiltered">0</strong></span>
                            <div class="d-flex gap-2">
                                <button class="btn" id="crmBtnReport" type="button" style="background:var(--verde-medio);color:#fff;border:none;border-radius:10px;font-weight:600;padding:8px 18px;font-size:0.85rem;">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:4px;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                    Reporte
                                </button>
                                <button class="btn" id="crmBtnNewClient" type="button" style="background:var(--verde-medio);color:#fff;border:none;border-radius:10px;font-weight:600;padding:8px 18px;font-size:0.85rem;">
                                    + Nuevo Cliente
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive mt-2">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cedula</th>
                                        <th>Nombre</th>
                                        <th>Correo</th>
                                        <th>Clasificacion</th>
                                        <th>Total Gastado</th>
                                        <th style="text-align:center;">Compras</th>
                                        <th>Ultima Compra</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="crmTableBody">
                                </tbody>
                            </table>
                            <div id="crmNoResults" class="crm-empty-state d-none">
                                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                                <p class="mb-0">No se encontraron clientes con los filtros seleccionados.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Panel -->
    <div id="crmDetailOverlay" class="crm-detail-overlay"></div>
    <div id="crmDetailPanel" class="crm-detail-panel" role="dialog" aria-modal="true">
        <div class="crm-detail-header">
            <h3>Detalle del Cliente</h3>
            <button id="crmDetailClose" class="crm-detail-close" aria-label="Cerrar">&times;</button>
        </div>
        <div class="crm-detail-body" id="crmDetailBody"></div>
    </div>

    <!-- Add/Edit Client Modal -->
    <div class="modal fade" id="crmClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div class="modal-header bg-light" style="border-radius:16px 16px 0 0;">
                    <h5 class="modal-title" id="crmClientModalTitle">Agregar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="crmClientForm">
                    <input type="hidden" name="action" id="crmFormAction" value="create">
                    <input type="hidden" name="id" id="crmFormId" value="0">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="crmFormCedula" class="form-label">Cedula</label>
                                <input id="crmFormCedula" type="text" class="form-control" placeholder="Cedula (10 digitos)" maxlength="10" inputmode="numeric" pattern="\d*" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="crmFormNombre" class="form-label">Nombre completo *</label>
                            <input id="crmFormNombre" type="text" class="form-control" placeholder="Nombre completo" required autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="crmFormCorreo" class="form-label">Correo</label>
                            <input id="crmFormCorreo" type="email" class="form-control" placeholder="Correo electronico" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="crmFormTelefono" class="form-label">Telefono</label>
                            <input id="crmFormTelefono" type="text" class="form-control" placeholder="Telefono" maxlength="20" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="crmFormDireccion" class="form-label">Direccion</label>
                            <input id="crmFormDireccion" type="text" class="form-control" placeholder="Direccion" maxlength="150" autocomplete="off">
                        </div>
                        <div id="crmFormMessage" class="alert d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn" style="background:var(--verde-medio);color:#fff;font-weight:600;">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Report Modal -->
    <div class="modal fade" id="crmReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div class="modal-header bg-light" style="border-radius:16px 16px 0 0;">
                    <h5 class="modal-title">Reporte de Clientes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="crmReportContent">
                    <p class="text-muted">Generando reporte...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn" onclick="window.print()" style="background:var(--verde-medio);color:#fff;font-weight:600;">Imprimir</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="frontend/js/clientes.js"></script>
</body>
</html>
