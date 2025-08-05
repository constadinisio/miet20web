<?php
require_once __DIR__ . '/../../../../backend/includes/db.php';
header('Content-Type: application/json');

$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
if (!$curso_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Curso inválido']);
    exit;
}

$alumnos = [];
$sql = "
    SELECT 
        u.id,
        u.nombre,
        u.apellido,
        u.dni,
        ROUND(AVG(n.nota), 1) AS promedio
    FROM usuarios u
    JOIN alumno_curso ac ON ac.alumno_id = u.id
    LEFT JOIN notas n ON n.alumno_id = u.id
    WHERE u.rol = 4
      AND ac.curso_id = $curso_id
      AND ac.estado = 'activo'
      AND u.status = 1
    GROUP BY u.id, u.nombre, u.apellido, u.dni
    ORDER BY u.apellido, u.nombre
";

$res = $conexion->query($sql);
if (!$res) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en SQL: ' . $conexion->error]);
    exit;
}

while ($row = $res->fetch_assoc()) {
    $alumnos[] = $row;
}

echo json_encode($alumnos);