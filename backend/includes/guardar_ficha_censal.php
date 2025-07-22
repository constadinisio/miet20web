<?php
session_start();
require_once __DIR__ . './db.php';

$usuario_id = $_SESSION['usuario']['id'];
$ficha_censal = trim($_POST['ficha_censal'] ?? '');

if (!$ficha_censal) {
    echo "El campo ficha censal es obligatorio.";
    exit;
}

$sql = "UPDATE usuarios SET ficha_censal = ? WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("si", $ficha_censal, $usuario_id);
$stmt->execute();
$stmt->close();

$_SESSION['usuario']['ficha_censal'] = $ficha_censal;

echo "OK";
exit;