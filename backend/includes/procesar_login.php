<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioInput = trim((string)($_POST['usuario'] ?? ''));
    $passwordInput = (string)($_POST['password'] ?? '');

    try {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ? AND estado = 1 LIMIT 1');
        $stmt->execute([$usuarioInput]);
        $usuarioDB = $stmt->fetch(PDO::FETCH_ASSOC);

        $passwordOk = false;
        if ($usuarioDB) {
            $storedHash = (string)($usuarioDB['password_hash'] ?? $usuarioDB['password'] ?? '');
            if ($storedHash !== '' && password_verify($passwordInput, $storedHash)) {
                $passwordOk = true;
            } elseif ($storedHash !== '' && $passwordInput === $storedHash) {
                $passwordOk = true;
            }
        }

        if ($usuarioDB && $passwordOk) {
            $_SESSION['usuario_activo'] = [
                'id' => $usuarioDB['id'],
                'usuario' => $usuarioDB['usuario'],
                'nombre' => $usuarioDB['nombre'] ?? $usuarioDB['usuario'],
                'rol' => $usuarioDB['rol']
            ];
            header('Location: ../../dashboard.php');
            exit();
        }

        header('Location: ../../index.php?error=1');
        exit();
    } catch (PDOException $e) {
        die('Error en la base de datos: ' . $e->getMessage());
    }
}

header('Location: ../../index.php');
exit();