<?php
session_start();
require_once __DIR__ . '/../../backend/includes/db.php';

$usuario_id = $_SESSION['usuario']['id'] ?? 0;
$id = intval($_POST['id'] ?? 0);

if ($usuario_id && $id) {
    $stmt = $conexion->prepare(
        "UPDATE notificaciones_destinatarios SET estado_lectura='LEIDA', fecha_leida=NOW() WHERE id=? AND destinatario_id=?"
    );
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
}
