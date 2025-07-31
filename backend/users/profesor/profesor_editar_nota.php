<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 3) {
    header("Location: /login.php?error=rol");
    exit;
}

require_once __DIR__ . '/../../../backend/includes/db.php';

$csrf = $_POST['csrf'] ?? '';
if ($csrf !== $_SESSION['csrf']) {
    die("❌ CSRF inválido");
}

if (!isset($_POST['notas']) || !is_array($_POST['notas'])) {
    header("Location: /users/profesor/calificaciones.php?error=sin_datos");
    exit;
}

foreach ($_POST['notas'] as $nota_id => $valor) {
    $nota_id = (int)$nota_id;
    $valor = is_numeric($valor) ? (float)$valor : null;

    if ($nota_id && $valor >= 1 && $valor <= 10) {
        $sql = "UPDATE notas_bimestrales SET nota = ?, fecha_carga = NOW() WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("di", $valor, $nota_id);
        $stmt->execute();
        $stmt->close();
    }
}

$curso_id = $_POST['curso_id'] ?? '';
$materia_id = $_POST['materia_id'] ?? '';
$periodo = $_POST['periodo'] ?? '';

header("Location: /users/profesor/calificaciones.php?curso_id=$curso_id&materia_id=$materia_id&periodo=" . urlencode($periodo) . "&ok=notas_editadas");
exit;