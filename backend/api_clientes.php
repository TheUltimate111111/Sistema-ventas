<?php
    declare(strict_types=1);

    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');

    require_once 'includes/conexion.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido']);
        exit;
    }

    $search = trim((string)($_GET['search'] ?? ''));
    if ($search === '') {
        echo json_encode([]);
        exit;
    }

    try {
        $sql = 'SELECT id, cedula, nombre_completo, correo FROM clientes WHERE nombre_completo LIKE ? OR cedula LIKE ? LIMIT 10';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$search%", "%$search%"]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
?>