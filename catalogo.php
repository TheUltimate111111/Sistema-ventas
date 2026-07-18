<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit;
}

require_once 'backend/includes/conexion.php';

$message = '';
$messageType = 'success';
$search = trim((string)($_GET['q'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    if ($action === 'create') {
        $codigo = trim((string)($_POST['codigo'] ?? ''));
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $precio = (float)($_POST['precio'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);

        if ($codigo === '' || $nombre === '') {
            $message = 'Código y nombre son obligatorios.';
            $messageType = 'danger';
        } elseif ($precio < 0 || $stock < 0) {
            $message = 'Precio y stock deben ser valores válidos.';
            $messageType = 'danger';
        } else {
            $stmt = $pdo->prepare('INSERT INTO productos (codigo_barras, nombre_producto, precio_actual, stock_disponible) VALUES (?, ?, ?, ?)');
            $stmt->execute([$codigo, $nombre, $precio, $stock]);
            $message = 'Producto agregado correctamente.';
            $messageType = 'success';
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $precio = (float)($_POST['precio'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);

        if ($id <= 0) {
            $message = 'Producto inválido para actualizar.';
            $messageType = 'danger';
        } elseif ($precio < 0 || $stock < 0) {
            $message = 'Precio y stock deben ser valores válidos.';
            $messageType = 'danger';
        } else {
            $stmt = $pdo->prepare('UPDATE productos SET precio_actual = ?, stock_disponible = ? WHERE id = ?');
            $stmt->execute([$precio, $stock, $id]);
            $message = 'Precio y stock actualizados correctamente.';
            $messageType = 'success';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['delete_id'] ?? 0);
        if ($id <= 0) {
            $message = 'Producto inválido para eliminar.';
            $messageType = 'danger';
        } else {
            $stmt = $pdo->prepare('DELETE FROM productos WHERE id = ?');
            $stmt->execute([$id]);
            $message = 'Producto eliminado correctamente.';
            $messageType = 'success';
        }
    }
}

$params = [];
$sql = 'SELECT id, codigo_barras, nombre_producto, precio_actual, stock_disponible FROM productos';
if ($search !== '') {
    $sql .= ' WHERE nombre_producto LIKE ? OR codigo_barras LIKE ?';
    $params = ["%$search%", "%$search%"];
}
$sql .= ' ORDER BY id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$usuario = $_SESSION['usuario_activo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - Sistema de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
    <style>
        .btn-verde {background-color: var(--verde-oscuro); color: white;}
        .btn-verde:hover {background-color: var(--verde-medio); color: white;}
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'backend/includes/sidebar.php'; ?>
        <div id="content" class="w-100">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 p-3">
                <div class="container-fluid d-flex justify-content-between">
                    <span class="navbar-brand mb-0 h4 text-secondary">Catálogo de Productos</span>
                    <div>
                        <span class="me-4 fw-bold" style="color:var(--verde-oscuro);">
                            <?php echo strtoupper($usuario['nombre']) . ' | Rol: ' . ucfirst($usuario['rol']); ?>
                        </span>
                        <a href="backend/includes/logout.php" class="btn btn-sm btn-outline-danger fw-bold">Cerrar Sesión</a>
                    </div>
                </div>
            </nav>
            <div class="container-fluid px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                    <div class="w-100 w-md-auto flex-grow-1">
                        <div class="input-group">
                            <input id="catalogSearch" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>" class="form-control" type="search" placeholder="Buscar producto por código o nombre" aria-label="Buscar">
                            <button id="catalogSearchButton" class="btn btn-verde" type="button">Buscar</button>
                        </div>
                        <div id="catalogMessage" class="alert alert-success d-none mt-3" role="alert"></div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <button id="btnNewProduct" class="btn btn-verde">+ Agregar producto</button>
                        <span class="text-muted">Total productos: <span id="catalogCount"><?php echo count($productos); ?></span></span>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="catalogProducts">
                                    <?php if (count($productos) === 0): ?>
                                        <tr id="catalogNoResults"><td colspan="5" class="text-center text-muted py-4">No se encontraron productos.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($productos as $producto): ?>
                                            <tr
                                                data-product-id="<?php echo (int)$producto['id']; ?>"
                                                data-product-code="<?php echo htmlspecialchars($producto['codigo_barras'], ENT_QUOTES); ?>"
                                                data-product-name="<?php echo htmlspecialchars($producto['nombre_producto'], ENT_QUOTES); ?>"
                                                data-product-price="<?php echo number_format((float)$producto['precio_actual'], 2, '.', ''); ?>"
                                                data-product-stock="<?php echo htmlspecialchars((string)$producto['stock_disponible'], ENT_QUOTES); ?>"
                                            >
                                                <td><?php echo htmlspecialchars($producto['codigo_barras'], ENT_QUOTES); ?></td>
                                                <td><?php echo htmlspecialchars($producto['nombre_producto'], ENT_QUOTES); ?></td>
                                                <td><?php echo '$' . number_format((float)$producto['precio_actual'], 2); ?></td>
                                                <td><?php echo htmlspecialchars((string)$producto['stock_disponible'], ENT_QUOTES); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning text-dark btn-edit-product">Editar</button>
                                                    <button type="button" class="btn btn-sm btn-danger btn-delete-product">Eliminar</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr id="catalogNoResults" class="d-none"><td colspan="5" class="text-center text-muted py-4">No se encontraron productos.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de CRUD de productos -->
    <div class="modal fade" id="catalogModal" tabindex="-1" aria-labelledby="catalogModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="catalogModalLabel">Agregar producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                            <form id="catalogForm" method="POST" action="catalogo.php">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="productId" value="0">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="productCode" class="form-label">Código de barras</label>
                            <input name="codigo" id="productCode" type="text" class="form-control" placeholder="Código de barras" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="productName" class="form-label">Nombre del producto</label>
                            <input name="nombre" id="productName" type="text" class="form-control" placeholder="Nombre del producto" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="productPrice" class="form-label">Precio</label>
                            <input name="precio" id="productPrice" type="number" step="0.01" class="form-control" placeholder="0.00">
                        </div>
                        <div class="mb-3">
                            <label for="productStock" class="form-label">Stock</label>
                            <input name="stock" id="productStock" type="number" step="1" class="form-control" placeholder="0">
                        </div>
                        <div id="productModalMessage" class="alert d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button id="productSaveButton" type="submit" class="btn btn-verde">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-9ndCyUaId4L1k8R+biYS2szLj4jz2Cbh5luBu5w5u5qvXn2roKkDedbuoNIpI2xg" crossorigin="anonymous"></script>
    <script src="frontend/js/catalogo.js"></script>
</body>
</html>
