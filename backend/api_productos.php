<?php
    declare(strict_types=1);

    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type');

    require_once 'includes/conexion.php';

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    try {
        switch ($method) {
            case 'GET':
                $search = trim((string)($_GET['search'] ?? $_GET['sql'] ?? ''));
                $sql = 'SELECT * FROM productos WHERE nombre_producto LIKE ? OR codigo_barras LIKE ?';
                $stmt = $pdo->prepare($sql);
                $stmt->execute(["%$search%", "%$search%"]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'POST':
                $sql = 'INSERT INTO productos (codigo_barras, nombre_producto, precio_actual, stock_disponible) VALUES (?, ?, ?, ?)';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $input['codigo'] ?? '',
                    $input['nombre'] ?? '',
                    $input['precio'] ?? 0,
                    $input['stock'] ?? 0
                ]);
                echo json_encode(['estado' => 'success', 'mensaje' => 'Producto agregado correctamente']);
                break;

            case 'PUT':
                $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
                if ($id <= 0) {
                    http_response_code(400);
                    echo json_encode(['estado' => 'error', 'mensaje' => 'ID de producto inválido.']);
                    exit;
                }

                $sql = 'UPDATE productos SET precio_actual = ?, stock_disponible = ? WHERE id = ?';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $input['precio'] ?? 0,
                    $input['stock'] ?? 0,
                    $id
                ]);
                echo json_encode(['estado' => 'success', 'mensaje' => 'Precio y stock actualizados correctamente']);
                break;

            case 'DELETE':
                $id = (int)($_GET['id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM productos WHERE id = ?');
                $stmt->execute([$id]);
                echo json_encode(['estado' => 'success', 'mensaje' => 'Producto eliminado correctamente']);
                break;

            default:
                http_response_code(405);
                echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido']);
                break;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'Error en la base de datos: ' . $e->getMessage()
        ]);
    }
?>