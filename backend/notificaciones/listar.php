<?php
session_start();
require_once __DIR__ . '/../../backend/includes/db.php';
$usuario_id = $_SESSION['usuario']['id'] ?? 0;

// Solo para DEBUG, después sacalo:
if (!$usuario_id) die(json_encode(['error'=>'No hay usuario en sesión','usuario'=>$_SESSION]));

$sql = "
SELECT 
    n.id, n.titulo, n.contenido, n.tipo_notificacion, n.fecha_creacion, n.prioridad, n.icono, n.color, n.estado, 
    n.requiere_confirmacion, n.tipo_especial,
    nd.id AS destinatario_row_id, nd.fecha_leida, nd.fecha_confirmada, nd.estado_lectura
FROM notificaciones_destinatarios nd
JOIN notificaciones n ON nd.notificacion_id = n.id
WHERE nd.destinatario_id = ? AND n.estado = 'ACTIVA'
ORDER BY n.fecha_creacion DESC
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result();

$notificaciones = [];
while ($n = $res->fetch_assoc()) {
    $notificaciones[] = $n;
}
echo json_encode($notificaciones);
