<?php
    declare(strict_types=1);
    session_start();

    if (!isset($_SESSION['usuario_activo'])) {
        http_response_code(401);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Sesión expirada']);
        exit;
    }

    require_once 'includes/conexion.php';

    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $items = $input['items'] ?? [];
    $cliente = trim((string)($input['cliente'] ?? 'Consumidor Final'));
    $cedula = trim((string)($input['cedula'] ?? ''));
    $correo = trim((string)($input['correo'] ?? ''));
    $montoPagado = (float)($input['monto_pagado'] ?? 0);
    $usuario = $_SESSION['usuario_activo'];

    if (!is_array($items) || count($items) === 0) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'El carrito no puede estar vacío.']);
        exit;
    }

    $subtotal = 0.0;
    $detalleItems = [];

    foreach ($items as $item) {
        $idProducto = (int)($item['id'] ?? 0);
        $cantidad = (int)($item['cantidad'] ?? 0);

        if ($idProducto <= 0 || $cantidad <= 0) {
            http_response_code(400);
            echo json_encode(['estado' => 'error', 'mensaje' => 'Datos de producto inválidos.']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id, nombre_producto, precio_actual, stock_disponible FROM productos WHERE id = ? LIMIT 1');
        $stmt->execute([$idProducto]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            http_response_code(404);
            echo json_encode(['estado' => 'error', 'mensaje' => 'Producto no encontrado.']);
            exit;
        }

        $stockActual = (int)($producto['stock_disponible'] ?? 0);
        if ($cantidad > $stockActual) {
            http_response_code(400);
            echo json_encode(['estado' => 'error', 'mensaje' => 'No hay suficiente stock para ' . $producto['nombre_producto'] . '.']);
            exit;
        }

        $precioUnitario = (float)($producto['precio_actual'] ?? 0);
        $subtotal += $precioUnitario * $cantidad;
        $detalleItems[] = [
            'id' => $idProducto,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'stock' => $stockActual
        ];
    }

    $iva = round($subtotal * 0.15, 2);
    $total = round($subtotal + $iva, 2);

    if ($montoPagado < 0) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'El monto pagado no puede ser negativo.']);
        exit;
    }

    if ($montoPagado < $total) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'El monto pagado debe ser mayor o igual al total de la venta.']);
        exit;
    }

    $colsStmt = $pdo->prepare("SHOW COLUMNS FROM ventas LIKE 'estado'");
    $colsStmt->execute();
    if (!$colsStmt->fetch()) {
        $pdo->exec("ALTER TABLE ventas ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'Pagada'");
    }

    try {
        $pdo->beginTransaction();

        $clienteId = null;
        $clienteIdFromPayload = isset($input['cliente_id']) ? (int)($input['cliente_id'] ?? 0) : 0;
        if (strcasecmp($cliente, 'Consumidor Final') !== 0) {
            if ($clienteIdFromPayload > 0) {
                $clienteStmt = $pdo->prepare('SELECT id, cedula, nombre_completo, correo FROM clientes WHERE id = ? LIMIT 1');
                $clienteStmt->execute([$clienteIdFromPayload]);
                $clienteRow = $clienteStmt->fetch(PDO::FETCH_ASSOC);

                if (!$clienteRow) {
                    http_response_code(404);
                    echo json_encode(['estado' => 'error', 'mensaje' => 'El cliente seleccionado ya no está disponible.']);
                    exit;
                }

                $clienteId = (int)$clienteRow['id'];
                $cedula = trim((string)($clienteRow['cedula'] ?? ''));
                $correo = trim((string)($clienteRow['correo'] ?? ''));
            } else {
                if ($cedula === '') {
                    http_response_code(400);
                    echo json_encode(['estado' => 'error', 'mensaje' => 'La cédula del cliente es obligatoria para clientes distintos a Consumidor Final.']);
                    exit;
                }

                if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode(['estado' => 'error', 'mensaje' => 'El correo del cliente no es válido.']);
                    exit;
                }

                $cedulaStmt = $pdo->prepare('SELECT id, nombre_completo FROM clientes WHERE cedula = ? LIMIT 1');
                $cedulaStmt->execute([$cedula]);
                $clienteCedulaRow = $cedulaStmt->fetch(PDO::FETCH_ASSOC);

                if ($clienteCedulaRow) {
                    if (strcasecmp(trim($clienteCedulaRow['nombre_completo']), $cliente) !== 0) {
                        http_response_code(409);
                        echo json_encode(['estado' => 'error', 'mensaje' => 'La cédula ya está registrada con otro cliente.']);
                        exit;
                    }
                    $clienteId = (int)$clienteCedulaRow['id'];
                } else {
                    $clienteStmt = $pdo->prepare('SELECT id FROM clientes WHERE nombre_completo = ? LIMIT 1');
                    $clienteStmt->execute([$cliente]);
                    $clienteRow = $clienteStmt->fetch(PDO::FETCH_ASSOC);

                    if ($clienteRow) {
                        $clienteId = (int)$clienteRow['id'];
                    } else {
                        $insertCliente = $pdo->prepare('INSERT INTO clientes (cedula, nombre_completo, correo) VALUES (?, ?, ?)');
                        $insertCliente->execute([$cedula, $cliente, $correo]);
                        $clienteId = (int)$pdo->lastInsertId();
                    }
                }
            }
        } else {
            $clienteStmt = $pdo->prepare('SELECT id FROM clientes WHERE nombre_completo = ? LIMIT 1');
            $clienteStmt->execute([$cliente]);
            $clienteRow = $clienteStmt->fetch(PDO::FETCH_ASSOC);

            if ($clienteRow) {
                $clienteId = (int)$clienteRow['id'];
            } else {
                $insertCliente = $pdo->prepare('INSERT INTO clientes (cedula, nombre_completo, correo) VALUES (?, ?, ?)');
                $insertCliente->execute(['', $cliente, '']);
                $clienteId = (int)$pdo->lastInsertId();
            }
        }

        $ventaStmt = $pdo->prepare('INSERT INTO ventas (cliente_id, usuario_id, subtotal, iva, total_factura, monto_pagado, cambio, fecha_emision, estado) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)');
        $cambio = round($montoPagado - $total, 2);
        $ventaStmt->execute([$clienteId, (int)$usuario['id'], $subtotal, $iva, $total, $montoPagado, $cambio, 'Pagada']);
        $ventaId = (int)$pdo->lastInsertId();

        $detalleStmt = $pdo->prepare('INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_congelado) VALUES (?, ?, ?, ?)');
        foreach ($detalleItems as $item) {
            $detalleStmt->execute([$ventaId, $item['id'], $item['cantidad'], $item['precio_unitario']]);

            $stockStmt = $pdo->prepare('UPDATE productos SET stock_disponible = stock_disponible - ? WHERE id = ?');
            $stockStmt->execute([$item['cantidad'], $item['id']]);
        }

        $pdo->commit();

        echo json_encode([
            'estado' => 'success',
            'mensaje' => 'Venta registrada correctamente.',
            'venta_id' => $ventaId,
            'total' => $total,
            'cajero' => $usuario['nombre']
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'No se pudo registrar la venta: ' . $e->getMessage()]);
    }
?>