<?php
session_start();
require_once 'db.php'; // Cambiá si tu path es distinto

// Validar usuario y contraseña
$mail = trim($_POST['mail'] ?? '');
$contrasena = trim($_POST['contrasena'] ?? '');

if (!$mail || !$contrasena) {
    header("Location: /login.php?error=campos");
    exit;
}

$sql = "SELECT * FROM usuarios WHERE mail = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $mail);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

/*
if (!$usuario || $contrasena !== $usuario['contrasena']) {
    header("Location: /login.php?error=login");
    exit;
}
*/


if (!password_verify($contrasena, $usuario['contrasena'])) {
    header("Location: /login.php?error=login");
    exit;
}


// ---------- CAMBIO ACÁ: roles principal + secundarios ----------

// 1. Primero agrego el rol principal del usuario (si tiene)
$roles = [];
if (!empty($usuario['rol'])) {
    $sql_rol = "SELECT id, nombre FROM roles WHERE id = ?";
    $stmt_rol = $conexion->prepare($sql_rol);
    $stmt_rol->bind_param("i", $usuario['rol']);
    $stmt_rol->execute();
    $res_rol = $stmt_rol->get_result();
    if ($rol_row = $res_rol->fetch_assoc()) {
        $roles[] = $rol_row;
    }
    $stmt_rol->close();
}

// 2. Ahora agrego los adicionales de usuario_roles, evitando duplicados
$sql = "SELECT r.id, r.nombre FROM usuario_roles ur JOIN roles r ON ur.rol_id = r.id WHERE ur.usuario_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $usuario['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $duplicado = false;
    foreach ($roles as $r) {
        if ($r['id'] == $row['id']) {
            $duplicado = true;
            break;
        }
    }
    if (!$duplicado) $roles[] = $row;
}
$stmt->close();

// ---------------------------------------------------------------

$tienePermisoEspecial = (!empty($usuario['permNoticia']) || !empty($usuario['permSubidaArch']));

if (count($roles) === 0) {
    header("Location: /login.php?error=sin_rol");
    exit;
} elseif (count($roles) === 1 && !$tienePermisoEspecial) {
    // Un solo rol Y NO tiene permisos especiales, mandá directo
    $_SESSION['usuario'] = $usuario;
    $_SESSION['usuario']['rol'] = $roles[0]['id'];
    $_SESSION['usuario']['rol_nombre'] = $roles[0]['nombre'];
    $_SESSION['usuario']['permNoticia'] = isset($usuario['permNoticia']) ? (int)$usuario['permNoticia'] : 0;
    $_SESSION['usuario']['permSubidaArch'] = isset($usuario['permSubidaArch']) ? (int)$usuario['permSubidaArch'] : 0;
    switch ($roles[0]['id']) {
        case 1:
            header("Location: /users/admin/admin.php");
            exit;
        case 2:
            header("Location: /users/preceptor/preceptor.php");
            exit;
        case 3:
            header("Location: /users/profesor/profesor.php");
            exit;
        case 4:
            header("Location: /users/alumno/alumno.php");
            exit;
        case 5:
            header("Location: /users/spei/index.php");
            exit;
        default:
            header("Location: /seleccionar_panel.php");
            exit;
    }
} else {
    // Tiene más de un rol O permisos especiales, mostrar selección
    $_SESSION['usuario'] = $usuario;
    $_SESSION['usuario_pending_roles'] = $roles;
    $_SESSION['usuario']['permNoticia'] = isset($usuario['permNoticia']) ? (int)$usuario['permNoticia'] : 0;
    $_SESSION['usuario']['permSubidaArch'] = isset($usuario['permSubidaArch']) ? (int)$usuario['permSubidaArch'] : 0;
    header("Location: /seleccionar_panel.php");
    exit;
}
