<?php
session_start();
session_regenerate_id(true);

include './db.php';

$mail = trim($_POST['mail'] ?? '');
$contrasena = trim($_POST['contrasena'] ?? '');

$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE mail = ? AND status = 1 LIMIT 1");
$stmt->bind_param("s", $mail);
$stmt->execute();
$result = $stmt->get_result();

if ($usuario = $result->fetch_assoc()) {
    if ($contrasena === $usuario['contrasena']) {

        // Guardar usuario en sesión
        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'rol' => $usuario['rol'],
            'permNoticia' => $usuario['permNoticia'] ?? 0,
            'permSubidaArch' => $usuario['permSubidaArch'] ?? 0,
            'foto_url' => $usuario['foto_url'] ?? null
        ];

        // Verificamos los permisos
        $tieneATTP = ((int)$usuario['rol'] === 5);
        $tieneNoticias = (!empty($usuario['permNoticia']) && $usuario['permNoticia']);
        $tieneSubida = (!empty($usuario['permSubidaArch']) && $usuario['permSubidaArch']);

        $totalPermisos = 0;
        $totalPermisos += $tieneATTP ? 1 : 0;
        $totalPermisos += $tieneNoticias ? 1 : 0;
        $totalPermisos += $tieneSubida ? 1 : 0;

        // Más de un permiso → seleccionar panel
        if ($totalPermisos > 1) {
            header("Location: ../includes/seleccionar_panel.php");
            exit;
        }

        // Casos individuales
        if ($tieneATTP) {
            header("Location: ../attpSystem/index.php");
            exit;
        } elseif ($tieneNoticias) {
            header("Location: ../panelNoticias/panelNoticias.php");
            exit;
        } elseif ($tieneSubida) {
            header("Location: ../galeriaUtils/subirImagenes.php");
            exit;
        } else {
            // No tiene permisos
            header("Location: ../login.php?error=perm");
            exit;
        }
    }
}

// Credenciales incorrectas
header("Location: ../login.php?error=1");
exit;
