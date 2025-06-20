<?php
session_start();
session_regenerate_id(true);

// Conexión a base de datos
include './db.php'; // Asegurate de que sirve para ambas apps

// Captura de datos
$mail = trim($_POST['mail'] ?? '');
$contrasena = trim($_POST['contrasena'] ?? '');

// Buscar usuario activo
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE mail = ? AND status = 1 LIMIT 1");
$stmt->bind_param("s", $mail);
$stmt->execute();
$result = $stmt->get_result();

if ($usuario = $result->fetch_assoc()) {
    if ($contrasena === $usuario['contrasena']) {

        // Setea la sesión con todos los datos relevantes
        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'rol' => $usuario['rol'],
            'permNoticia' => $usuario['permNoticia'] ?? 0,
            'foto_url' => $usuario['foto_url'] ?? null
        ];

        // Redirección según permisos
        $tieneATTP = ((int)$usuario['rol'] === 5);
        $tieneNoticias = (!empty($usuario['permNoticia']) && $usuario['permNoticia']);

        if ($tieneATTP && $tieneNoticias) {
            // Tiene ambos permisos → redirigimos a ATTP por defecto (podés cambiar esto)
            header("Location: ../includes/seleccionar_panel.php");
            exit;
        } elseif ($tieneATTP) {
            header("Location: ../attpSystem/index.php");
            exit;
        } elseif ($tieneNoticias) {
            header("Location: ../panelNoticias/panelNoticias.php");
            exit;
        } else {
            // Usuario válido pero sin permisos
            header("Location: ../login.php?error=perm");
            exit;
        }
    }
}

// Error de usuario o contraseña
header("Location: ../login.php?error=1");
exit;
?>