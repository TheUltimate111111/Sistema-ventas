<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit;
}

require_once 'backend/includes/conexion.php';

$search = trim((string)($_GET['q'] ?? ''));
$params = [];
$sql = 'SELECT id, cedula, nombre_completo, correo, fecha_registro FROM clientes';
if ($search !== '') {
    $sql .= ' WHERE nombre_completo LIKE ? OR cedula LIKE ?';
    $params = ["%$search%", "%$search%"];
}
$sql .= ' ORDER BY id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$usuario = $_SESSION['usuario_activo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Sistema de Ventas</title>
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
                    <span class="navbar-brand mb-0 h4 text-secondary">Clientes</span>
                    <div>
                        <span class="me-4 fw-bold" style="color:var(--verde-oscuro);">
                            ?? <?php echo strtoupper($usuario['nombre']) . ' | Rol: ' . ucfirst($usuario['rol']); ?>
                        </span>
                        <a href="backend/includes/logout.php" class="btn btn-sm btn-outline-danger fw-bold">Cerrar Sesi�n</a>
                    </div>
                </div>
            </nav>
            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <form class="d-flex" method="GET" action="clientes.php">
                        <input name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>" class="form-control me-2" type="search" placeholder="Buscar cliente por c�dula o nombre" aria-label="Buscar">
                        <button class="btn btn-verde" type="submit">Buscar</button>
                    </form>
                    <span class="text-muted">Total clientes: <?php echo count($clientes); ?></span>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>C�dula</th>
                                        <th>Nombre completo</th>
                                        <th>Correo</th>
                                        <th>Fecha registro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($clientes) === 0): ?>
                                        <tr><td colspan="5" class="text-center text-muted py-4">No hay clientes registrados.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($clientes as $cliente): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars((string)$cliente['id'], ENT_QUOTES); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['cedula'] ?? '-', ENT_QUOTES); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['nombre_completo'], ENT_QUOTES); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['correo'] ?? '-', ENT_QUOTES); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['fecha_registro'], ENT_QUOTES); ?></td>
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
