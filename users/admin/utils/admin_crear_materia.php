<?php
require_once '../../../includes/db.php';

$nombre = trim($_POST['nombre'] ?? '');
$codigo = trim($_POST['codigo'] ?? '');
$categoria_id = $_POST['categoria_id'] ?? '';
$categoria_id = is_numeric($categoria_id) ? (int)$categoria_id : 0;
$es_contraturno = isset($_POST['es_contraturno']) ? 1 : 0;

// Log de depuración
$logData = [
    'POST' => $_POST,
    'nombre' => $nombre,
    'codigo' => $codigo,
    'categoria_id' => $categoria_id,
    'es_contraturno' => $es_contraturno,
    'timestamp' => date('Y-m-d H:i:s')
];
file_put_contents(__DIR__ . '/debug_materia.log', print_r($logData, true) . "\n---\n", FILE_APPEND);

if (!$nombre || !$categoria_id) {
    header("Location: ../materias.php?error=faltan_campos");
    exit;
}

$sql = "SELECT COUNT(*) FROM materias WHERE nombre = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $nombre);
$stmt->execute();
$stmt->bind_result($existe);
$stmt->fetch();
$stmt->close();

if ($existe > 0) {
    header("Location: ../materias.php?error=duplicado");
    exit;
}

// Nuevo insert con categoria_id
$sql = "INSERT INTO materias (nombre, codigo, categoria_id, es_contraturno, estado) VALUES (?, ?, ?, ?, 'activo')";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssii", $nombre, $codigo, $categoria_id, $es_contraturno);
$stmt->execute();
$stmt->close();

header("Location: ../materias.php?ok=nueva");
exit;
?>