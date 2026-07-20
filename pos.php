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
    <title>POS - Sistema de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
    <link rel="stylesheet" href="frontend/css/pos.css">
</head>
<body>
    <div class="app-layout">
        <?php include 'backend/includes/sidebar.php'; ?>

        <div id="content" class="w-100">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 p-3">
                <div class="container-fluid d-flex justify-content-between">
                    <span class="navbar-brand mb-0 h4 text-secondary">Punto de Venta</span>
                    <div>
                        <span class="me-4 fw-bold" style="color: var(--verde-oscuro);">
                            👤 <?php echo strtoupper($usuario['nombre']) . ' | Rol: ' . ucfirst($usuario['rol']); ?>
                        </span>
                        <a href="backend/includes/logout.php" class="btn btn-sm btn-outline-danger fw-bold">Cerrar sesión</a>
                    </div>
                </div>
            </nav>

            <div class="container-fluid px-4 py-2 pos-page">
                <main class="pos-grid">
                    <section class="pos-panel">
                        <div class="pos-card pos-header">
                            <h2>Zona de operación</h2>
                            <p>Escanea o busca el producto para agregarlo al carrito.</p>
                        </div>

                        <div class="pos-card pos-barcode-box">
                            <label for="posBarcodeInput">Buscador rápido / lector de código de barras</label>
                            <div class="barcode-actions">
                                <input id="posBarcodeInput" class="pos-barcode-input" type="text" placeholder="Código de barras o nombre del producto" autocomplete="off" autofocus>
                                <button id="btnCameraScan" type="button" class="pos-action-btn" style="background:#2563eb; margin-top: 0;">Escanear con cámara</button>
                            </div>
                            <div id="posSuggestions" class="pos-suggestions"></div>
                            <div id="posMessage" class="pos-message d-none"></div>
                        </div>

                        <div class="pos-card">
                            <div class="pos-card-header">
                                <h3>Carrito de compras</h3>
                                <small>Actualiza las cantidades con + / -</small>
                            </div>
                            <div id="posCartItems"></div>
                        </div>
                    </section>

                    <aside class="pos-panel">
                        <div class="pos-card pos-header">
                            <h2>Panel de facturación</h2>
                            <p>Asigna cliente, calcula total e ingresa el pago.</p>
                        </div>

                        <div class="pos-card pos-input-group">
                            <label for="clienteSearch">Cliente</label>
                            <input id="clienteSearch" type="text" placeholder="Nombre del cliente" autocomplete="off" value="Consumidor Final" maxlength="100" oninput="this.value=this.value.replace(/[^a-zA-ZáéíóúñüÁÉÍÓÚÑÜ\s]/g,'')">
                            <input id="clienteId" type="hidden" value="">
                            <div id="clientSuggestions" class="pos-suggestions"></div>
                            <button id="btnConsumidorFinal" type="button" class="pos-action-btn" style="background:#0f766e;">Consumidor Final</button>
                        </div>
                        <div id="clientDetails" class="pos-card pos-input-group d-none">
                            <label for="clienteCedula">Cédula</label>
                            <input id="clienteCedula" type="text" placeholder="Cédula del cliente" maxlength="10" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                            <label for="clienteCorreo">Correo</label>
                            <input id="clienteCorreo" type="email" placeholder="Correo del cliente">
                        </div>

                        <div class="pos-card pos-specs">
                            <h3>Especificaciones del POS</h3>
                            <ul>
                                <li>No se permite vender productos sin stock.</li>
                                <li>El monto pagado debe cubrir el total de la venta.</li>
                                <li>El carrito no puede quedar vacío al procesar.</li>
                                <li>Se descuenta el stock automáticamente al guardar la venta.</li>
                            </ul>
                        </div>

                        <div class="pos-card pos-summary">
                            <div class="pos-summary-row">
                                <span>Subtotal</span>
                                <strong id="subtotalValue">$0.00</strong>
                            </div>
                            <div class="pos-summary-row">
                                <span>IVA 15%</span>
                                <strong id="ivaValue">$0.00</strong>
                            </div>
                            <div class="pos-summary-row total">
                                <span>Total a pagar</span>
                                <strong id="totalValue">$0.00</strong>
                            </div>
                        </div>

                        <div class="pos-card pos-input-group">
                            <label for="montoPagado">Monto pagado</label>
                            <input id="montoPagado" type="number" step="0.01" min="0" placeholder="Ingrese el pago del cliente">
                        </div>
                        <div class="pos-card pos-input-group">
                            <label for="vuelto">Vuelto</label>
                            <input id="vuelto" type="text" value="$0.00" readonly>
                        </div>

                        <button id="btnProcesarVenta" type="button" class="pos-action-btn">Procesar venta</button>
                        <button id="btnMostrarFactura" type="button" class="pos-action-btn d-none mt-3" style="background:#2563eb;">Mostrar factura</button>

                        <div class="pos-card receipt-preview" id="receiptPreview">
                            <h3>Recibo / factura</h3>
                            <p>Aquí se mostrará el resumen de la venta antes de imprimir.</p>
                        </div>
                    </aside>
                </main>
            </div>
        </div>
    </div>

    <div id="invoiceModal" class="overlay" role="dialog" aria-modal="true" aria-labelledby="invoiceModalTitle">
        <div class="ticket">
            <div class="dentado-top"></div>
            <button id="closeInvoiceModal" type="button" class="cerrar" aria-label="Cerrar ventana">✕</button>
            <div id="invoiceModalBody" class="contenido"></div>
            <div class="dentado-bottom"></div>
        </div>
    </div>

    <div id="cameraModal" class="camera-modal" role="dialog" aria-modal="true" aria-labelledby="cameraModalTitle">
        <div class="camera-modal-content">
            <div class="camera-modal-header">
                <h5 id="cameraModalTitle">Escanear código de barras</h5>
                <button id="closeCameraModal" type="button" class="pos-action-btn btn-secondary">Cerrar</button>
            </div>
            <div class="camera-modal-body">
                <video id="cameraVideo" autoplay muted playsinline></video>
                <div id="cameraMessage" class="pos-message"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.18.6/umd/index.min.js"></script>
    <script src="frontend/js/pos.js"></script>
</body>
</html>