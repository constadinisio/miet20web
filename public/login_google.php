<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../backend/includes/db.php';
require_once __DIR__ . '/../backend/includes/loadEnv.php';
cargarEntorno(__DIR__ . '/../config/.env');
session_start();

$client = new Google\Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope("email");
$client->addScope("profile");

if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . $auth_url);
    exit;
} else {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        header("Location: /login.php?error=oauth");
        exit;
    }

    $client->setAccessToken($token);
    $oauth = new Google\Service\Oauth2($client);
    $perfil = $oauth->userinfo->get();

    $email = $perfil->email;

    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE mail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // 1. Chequeo si está pendiente de aprobación (rol 0)
        if ((int)$usuario['rol'] === 0) {
            echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Pendiente de aprobación</title>
                <link href='output.css' rel='stylesheet'>
            </head>
            <body class='bg-gray-100 flex items-center justify-center min-h-screen'>
            <div class='bg-white p-10 rounded-2xl shadow-xl text-center'>
                <h1 class='text-2xl font-bold text-yellow-600 mb-4'>Registro pendiente de aprobación</h1>
                <p class='text-gray-700 mb-6'>Tu registro fue enviado correctamente.<br>
                Un administrador lo revisará y te habilitará el acceso.<br>
                Volvé a intentar en unas horas.</p>
                <a href='login.php' class='inline-block px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700'>Ir al inicio</a>
            </div></body></html>";
            exit;
        }

        // --------- Lógica de roles principal + secundarios ---------
        // 1. Principal
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
        // 2. Secundarios/adicionales
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
            // Un solo rol y NO permisos especiales, redirigí directo
            $_SESSION['usuario'] = $usuario;
            $_SESSION['usuario']['rol'] = $roles[0]['id'];
            $_SESSION['usuario']['rol_nombre'] = $roles[0]['nombre'];
            $_SESSION['usuario']['permNoticia'] = isset($usuario['permNoticia']) ? (int)$usuario['permNoticia'] : 0;
            $_SESSION['usuario']['permSubidaArch'] = isset($usuario['permSubidaArch']) ? (int)$usuario['permSubidaArch'] : 0;
            switch ($roles[0]['id']) {
                case 1: header("Location: /users/admin/admin.php"); exit;
                case 2: header("Location: /users/preceptor/preceptor.php"); exit;
                case 3: header("Location: /users/profesor/profesor.php"); exit;
                case 4: header("Location: /users/alumno/alumno.php"); exit;
                case 5: header("Location: /users/spei/index.php"); exit;
                default: header("Location: /includes/seleccionar_panel.php"); exit;
            }
        } else {
            // Tiene más de un rol O permisos especiales, mostrar selección
            $_SESSION['usuario'] = $usuario;
            $_SESSION['usuario_pending_roles'] = $roles;
            $_SESSION['usuario']['permNoticia'] = isset($usuario['permNoticia']) ? (int)$usuario['permNoticia'] : 0;
            $_SESSION['usuario']['permSubidaArch'] = isset($usuario['permSubidaArch']) ? (int)$usuario['permSubidaArch'] : 0;
            header("Location: /includes/seleccionar_panel.php");
            exit;
        }
    } else {
        // Si NO existe el mail, redirigí a registro
        $_SESSION['google_email'] = $email; // opcional para prellenar
        header("Location: /includes/registro_google.php");
        exit;
    }
}
?>