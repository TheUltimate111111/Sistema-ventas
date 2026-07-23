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

function validarCedulaEC(string $cedula): ?string {
    if (!preg_match('/^\d{10}$/', $cedula)) return 'La cedula debe tener exactamente 10 digitos.';
    $provincia = (int)substr($cedula, 0, 2);
    if ($provincia < 1 || $provincia > 24) return 'Los dos primeros digitos deben ser una provincia valida (01-24).';
    $factores = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $val = (int)$cedula[$i] * $factores[$i];
        if ($val >= 10) $val -= 9;
        $suma += $val;
    }
    $resto = $suma % 10;
    $verificador = $resto === 0 ? 0 : 10 - $resto;
    if ((int)$cedula[9] !== $verificador) return 'La cedula no es valida (digito verificador incorrecto).';
    return null;
}

function validarNombreCliente(string $nombre): ?string {
    $nombre = trim($nombre);
    if ($nombre === '') return 'El nombre es obligatorio.';
    if (mb_strlen($nombre) < 2) return 'El nombre debe tener al menos 2 caracteres.';
    if (!preg_match('/^[a-zA-Z\x{00C0}-\x{024F}\s]+$/u', $nombre)) return 'El nombre solo puede contener letras, espacios y tildes.';
    return null;
}

function validarCorreo(?string $correo): ?string {
    if ($correo === null || $correo === '') return null;
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) return 'Ingrese un correo electronico valido.';
    return null;
}

function validarTelefono(?string $telefono): ?string {
    if ($telefono === null || $telefono === '') return null;
    $cleaned = preg_replace('/[\s\-\(\)]/', '', $telefono);
    if (!preg_match('/^\d{7,15}$/', $cleaned)) return 'El telefono debe tener entre 7 y 15 digitos.';
    return null;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $search = trim((string)($_GET['search'] ?? ''));

            $sql = "SELECT
                c.id, c.cedula, c.nombre_completo, c.correo, c.telefono, c.direccion, c.fecha_registro,
                COALESCE(SUM(CASE WHEN v.estado = 'Pagada' THEN v.total_factura ELSE 0 END), 0) AS total_gastado,
                COUNT(DISTINCT CASE WHEN v.estado = 'Pagada' THEN v.id END) AS num_compras,
                MAX(CASE WHEN v.estado = 'Pagada' THEN v.fecha_emision END) AS ultima_compra,
                MIN(CASE WHEN v.estado = 'Pagada' THEN v.fecha_emision END) AS primera_compra
                FROM clientes c
                LEFT JOIN ventas v ON c.id = v.cliente_id";

            $params = [];
            if ($search !== '') {
                $sql .= " WHERE c.nombre_completo LIKE ? OR c.cedula LIKE ? OR c.correo LIKE ?";
                $params = ["%$search%", "%$search%", "%$search%"];
            }

            $sql .= " GROUP BY c.id ORDER BY c.id ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($clientes);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $cedula = trim((string)($input['cedula'] ?? ''));
            $nombre = trim((string)($input['nombre'] ?? ''));
            $correo = trim((string)($input['correo'] ?? ''));
            $telefono = trim((string)($input['telefono'] ?? ''));
            $direccion = trim((string)($input['direccion'] ?? ''));

            $err = validarNombreCliente($nombre);
            if ($err) { http_response_code(400); echo json_encode(['estado' => 'error', 'mensaje' => $err]); exit; }

            if ($cedula !== '') {
                $err = validarCedulaEC($cedula);
                if ($err) { http_response_code(400); echo json_encode(['estado' => 'error', 'mensaje' => $err]); exit; }
                $check = $pdo->prepare('SELECT id FROM clientes WHERE cedula = ? LIMIT 1');
                $check->execute([$cedula]);
                if ($check->fetch()) {
                    http_response_code(409);
                    echo json_encode(['estado' => 'error', 'mensaje' => 'Ya existe un cliente con esa cedula.']);
                    exit;
                }
            }

            $err = validarCorreo($correo !== '' ? $correo : null);
            if ($err) { http_response_code(400); echo json_encode(['estado' => 'error', 'mensaje' => $err]); exit; }

            $err = validarTelefono($telefono !== '' ? $telefono : null);
            if ($err) { http_response_code(400); echo json_encode(['estado' => 'error', 'mensaje' => $err]); exit; }

            $stmt = $pdo->prepare('INSERT INTO clientes (cedula, nombre_completo, correo, telefono, direccion) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $cedula !== '' ? $cedula : null,
                $nombre,
                $correo !== '' ? $correo : null,
                $telefono !== '' ? $telefono : null,
                $direccion !== '' ? $direccion : null
            ]);

            echo json_encode([
                'estado' => 'success',
                'mensaje' => 'Cliente registrado correctamente.',
                'id' => (int)$pdo->lastInsertId()
            ]);
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = (int)($input['id'] ?? 0);

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['estado' => 'error', 'mensaje' => 'ID de cliente invalido.']);
                exit;
            }

            $cedula = trim((string)($input['cedula'] ?? ''));
            $nombre = trim((string)($input['nombre'] ?? ''));
            $correo = trim((string)($input['correo'] ?? ''));
            $telefono = trim((string)($input['telefono'] ?? ''));
            $direccion = trim((string)($input['direccion'] ?? ''));

            $err = validarNombreCliente($nombre);
            if ($err) { http_response_code(400); echo json_encode(['estado' => 'error', 'mensaje' => $err]); exit; }

            if ($cedula !== '') {
                $err = validarCedulaEC($cedula);
                if ($err) { http_response_code(400); echo json_encode(['estado' => 'error', 'mensaje' => $err]); exit; }
                $check = $pdo->prepare('SELECT id FROM clientes WHERE cedula = ? AND id != ? LIMIT 1');
                $check->execute([$cedula, $id]);
                if ($check->fetch()) {
                    http_response_code(409);
                    echo json_encode(['estado' => 'error', 'mensaje' => 'Ya existe otro cliente con esa cedula.']);
                    exit;
                }
            }

            $err = validarCorreo($correo !== '' ? $correo : null);
            if ($err) { http_response_code(400); echo json_encode(['estado' => 'error', 'mensaje' => $err]); exit; }

            $err = validarTelefono($telefono !== '' ? $telefono : null);
            if ($err) { http_response_code(400); echo json_encode(['estado' => 'error', 'mensaje' => $err]); exit; }

            $stmt = $pdo->prepare('UPDATE clientes SET cedula = ?, nombre_completo = ?, correo = ?, telefono = ?, direccion = ? WHERE id = ?');
            $stmt->execute([
                $cedula !== '' ? $cedula : null,
                $nombre,
                $correo !== '' ? $correo : null,
                $telefono !== '' ? $telefono : null,
                $direccion !== '' ? $direccion : null,
                $id
            ]);

            echo json_encode(['estado' => 'success', 'mensaje' => 'Cliente actualizado correctamente.']);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = (int)($input['id'] ?? 0);

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['estado' => 'error', 'mensaje' => 'ID de cliente invalido.']);
                exit;
            }

            $check = $pdo->prepare('SELECT COUNT(*) AS total FROM ventas WHERE cliente_id = ?');
            $check->execute([$id]);
            $count = $check->fetch(PDO::FETCH_ASSOC);

            if ((int)$count['total'] > 0) {
                http_response_code(409);
                echo json_encode(['estado' => 'error', 'mensaje' => 'No se puede eliminar un cliente que tiene compras registradas.']);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM clientes WHERE id = ?');
            $stmt->execute([$id]);

            echo json_encode(['estado' => 'success', 'mensaje' => 'Cliente eliminado correctamente.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['estado' => 'error', 'mensaje' => 'Metodo no permitido']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
