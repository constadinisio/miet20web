<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Usuario no autenticado']);
    exit;
}

if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
    echo json_encode(['ok' => false, 'mensaje' => 'CSRF inválido']);
    exit;
}

$usuario_id = $_SESSION['usuario']['id'];
$campos = $_POST;
unset($campos['csrf']);

if (empty($campos)) {
    echo json_encode(['ok' => false, 'mensaje' => 'No se recibieron datos']);
    exit;
}

// Construimos dinámicamente el UPDATE
$set = [];
$values = [];
$types = "";

foreach ($campos as $campo => $valor) {
    $set[] = "$campo = ?";
    $values[] = $valor;
    $types .= "s";
}
$values[] = $usuario_id;
$types .= "i";

$sql = "UPDATE usuarios SET " . implode(", ", $set) . " WHERE id = ?";
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok' => false, 'mensaje' => 'Error en la consulta']);
    exit;
}
$stmt->bind_param($types, ...$values);
$stmt->execute();

// Refrescamos datos de usuario en sesión
$res = $conexion->query("SELECT * FROM usuarios WHERE id = $usuario_id LIMIT 1");
if ($res && $res->num_rows > 0) {
    $_SESSION['usuario'] = $res->fetch_assoc();
}

unset($_SESSION['completar_datos']);

echo json_encode(['ok' => true]);
