<?php
session_start();

$mail = trim($_POST['mail'] ?? '');
$contrasena = trim($_POST['contrasena'] ?? '');
session_regenerate_id(true);  // Previene session fixation

include '../includes/db.php';

$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE mail = ? AND status = 1 LIMIT 1");
$stmt->bind_param("s", $mail);
$stmt->execute();
$result = $stmt->get_result();

if ($usuario = $result->fetch_assoc()) {
    if ($contrasena === $usuario['contrasena']) {
        if (!empty($usuario['permNoticia']) && $usuario['permNoticia']) {
            $_SESSION['usuario'] = [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'permNoticia' => $usuario['permNoticia']
            ];
            header("Location: ../panelNoticias.php");
            exit;
        } else {
            header("Location: ../login.php?error=perm");
            exit;
        }
    }
}

header("Location: ../login.php?error=1");
exit;
?>
