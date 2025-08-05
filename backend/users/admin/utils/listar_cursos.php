<?php
require_once __DIR__ . '/../../../../backend/includes/db.php';

$cursos = [];
$sql = "SELECT id, anio, division FROM cursos ORDER BY anio, division";
$result = $conexion->query($sql);
while ($row = $result->fetch_assoc()) {
    $row['nombre'] = $row['anio'] . "° " . $row['division'];
    $cursos[] = $row;
}

header('Content-Type: application/json');
echo json_encode($cursos);