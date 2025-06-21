<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db.php';
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
        header("Location: login.php?error=oauth");
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
        $_SESSION['usuario'] = $usuario;

        $tieneATTP = ((int)$usuario['rol'] === 5);
        $tieneNoticias = (!empty($usuario['permNoticia']) && $usuario['permNoticia']);

        if ($tieneATTP && $tieneNoticias) {
            // Tiene ambos permisos → redirigimos a ATTP por defecto (podés cambiar esto)
            header("Location: ./includes/seleccionar_panel.php");
            exit;
        } elseif ($tieneATTP) {
            header("Location: ./attpSystem/index.php");
            exit;
        } elseif ($tieneNoticias) {
            header("Location: ./panelNoticias/panelNoticias.php");
            exit;
        }
    } else {
        header("Location: login.php?error=correo");
        exit;
    }
}
?>
