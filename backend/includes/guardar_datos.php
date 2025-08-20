<?php
session_start();
require_once 'db.php'; // Cambiá si tu path es distinto

if (!isset($_SESSION['usuario'])) {
    header("Location: /login.php"); exit;
}

$usuario_id = $_SESSION['usuario']['id'];
$updates = [];
$params = [];
$types = '';

foreach ($_POST as $campo => $valor) {
    if ($valor !== '' && $valor !== null) {
        $updates[] = "$campo = ?";
        $params[] = $valor;
        $types .= 's';
    }
}

if (!empty($updates)) {
    $sql = "UPDATE usuarios SET " . implode(", ", $updates) . " WHERE id = ?";
    $params[] = $usuario_id;
    $types .= 'i';

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    // Recargar datos del usuario desde DB
    $res = $conexion->query("SELECT * FROM usuarios WHERE id = $usuario_id LIMIT 1");
    $_SESSION['usuario'] = $res->fetch_assoc();

    unset($_SESSION['completar_datos']);
}

$redirect = $_SESSION['redirect_after_datos'] ?? '/index.php';
unset($_SESSION['redirect_after_datos']);

header("Location: $redirect");
exit;