<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../../login.php?error=rol");
    exit;
}
require_once '../../../includes/db.php';

$curso_id = $_POST['curso_id'] ?? null;
$dni = trim($_POST['dni'] ?? '');

if ($curso_id && $dni) {
    // Buscar alumno por DNI
    $sql = "SELECT id FROM usuarios WHERE dni = ? AND rol = 4";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $stmt->bind_result($alumno_id);
    if ($stmt->fetch()) {
        $stmt->close();
        // Verificar si ya está en el curso
        $sql_check = "SELECT id FROM alumno_curso WHERE alumno_id = ? AND curso_id = ?";
        $stmt2 = $conexion->prepare($sql_check);
        $stmt2->bind_param("ii", $alumno_id, $curso_id);
        $stmt2->execute();
        $stmt2->store_result();
        if ($stmt2->num_rows === 0) {
            $stmt2->close();
            // Insertar alumno en curso
            $sql_insert = "INSERT INTO alumno_curso (alumno_id, curso_id, estado) VALUES (?, ?, 'activo')";
            $stmt3 = $conexion->prepare($sql_insert);
            $stmt3->bind_param("ii", $alumno_id, $curso_id);
            $stmt3->execute();
            $stmt3->close();
        } else {
            $stmt2->close();
        }
    } else {
        $stmt->close();
        // (Opcional) manejar error de alumno no encontrado
    }
}
header("Location: ../cursos.php?curso_id=$curso_id");
exit;
