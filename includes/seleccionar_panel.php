<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit;
}

$usuario = $_SESSION['usuario'];
$roles = $_SESSION['usuario_pending_roles'] ?? [];

// Detectar permisos especiales
$tieneNoticias = (!empty($usuario['permNoticia']) && $usuario['permNoticia']);
$tieneSubida = (!empty($usuario['permSubidaArch']) && $usuario['permSubidaArch']);
$tieneATTP = false;
foreach ($roles as $r) {
    if ($r['id'] == 5) $tieneATTP = true; // 5 = ATTP
}
// Si ATTP se maneja como permiso, podes usar también: $tieneATTP = !empty($usuario['permATTP']);

// Procesar selección
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['opcion'])) {
    $opcion = $_POST['opcion'];
    // Si selecciona un rol principal
    foreach ($roles as $r) {
        if ($opcion == 'rol_' . $r['id']) {
            $_SESSION['usuario']['rol'] = $r['id'];
            $_SESSION['usuario']['rol_nombre'] = $r['nombre'];
            unset($_SESSION['usuario_pending_roles']);
            // Redirección según el rol:
            switch ($r['id']) {
                case 1:
                    header("Location: ../users/admin/admin.php");
                    exit;
                case 2:
                    header("Location: ../users/preceptor/preceptor.php");
                    exit;
                case 3:
                    header("Location: ../users/profesor/profesor.php");
                    exit;
                case 4:
                    header("Location: ../users/alumno/alumno.php");
                    exit;
                case 5:
                    header("Location: ../attpSystem/index.php");
                    exit;
                default:
                    header("Location: seleccionar_panel.php");
                    exit;
            }
        }
    }
    // Si selecciona un permiso especial
    if ($opcion == 'noticias') {
        $_SESSION['usuario']['permNoticia'] = true;
        header("Location: ../panelNoticias/panelNoticias.php");
        exit;
    } elseif ($opcion == 'galeria') {
        $_SESSION['usuario']['permSubidaArch'] = true;
        header("Location: ../galeriaUtils/subirImagenes.php");
        exit;
    } elseif ($opcion == 'attp') {
        $_SESSION['usuario']['rol'] = 5;
        $_SESSION['usuario']['rol_nombre'] = "ATTP";
        header("Location: ../attpSystem/index.php");
        exit;
    }
    // Si llega acá, opción inválida
    $error = "Opción seleccionada inválida.";
}

$nombre = htmlspecialchars($usuario['nombre'] ?? '');
$apellido = htmlspecialchars($usuario['apellido'] ?? '');
$tieneNoticias = !empty($usuario['permNoticia']);
$tieneSubida = !empty($usuario['permSubidaArch']);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mi ET20 - Seleccionar Panel</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="images/et20png.png">
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-center items-center h-16">
                <div class="flex items-center">
                    <a href="../index.php" class="flex items-center">
                        <h1><img src="../images/et20ico.ico" alt="Icono personalizado" class="w-10 h-10"></h1>
                        <span class="text-xl font-semibold text-gray-800 ml-2">Escuela Técnica 20 D.E. 20</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section class="bg-gray-100 min-h-screen flex flex-col items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md text-center space-y-6">
            <h1 class="text-2xl font-bold text-gray-800">¡Hola <?= $nombre ?> <?= $apellido ?>!</h1>
            <p class="text-gray-600">Seleccioná a qué sistema/panel querés acceder:</p>
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 text-red-700 rounded-xl p-3 mb-4"><?= $error ?></div>
            <?php endif; ?>
            <form method="post" class="space-y-2">
                <!-- Roles principales -->
                <?php foreach ($roles as $r): ?>
                    <button type="submit" name="opcion" value="rol_<?= $r['id'] ?>"
                        class="block w-full text-lg mb-3 px-4 py-3 rounded-xl bg-indigo-600 text-white hover:bg-indigo-800 font-bold">
                        <?= htmlspecialchars("Entrar como {$r['nombre']}") ?>
                    </button>
                <?php endforeach; ?>
                <!-- Permisos especiales -->
                <?php if ($tieneNoticias): ?>
                    <button type="submit" name="opcion" value="noticias"
                        class="block w-full text-lg mb-3 px-4 py-3 rounded-xl bg-green-600 text-white hover:bg-green-800 font-bold">
                        Panel de Noticias
                    </button>
                <?php endif; ?>
                <?php if ($tieneSubida): ?>
                    <button type="submit" name="opcion" value="galeria"
                        class="block w-full text-lg mb-3 px-4 py-3 rounded-xl bg-purple-600 text-white hover:bg-purple-800 font-bold">
                        Galería de Imágenes
                    </button>
                <?php endif; ?>
                <?php if ($tieneATTP && (!in_array('rol_5', array_map(fn($r) => "rol_" . $r['id'], $roles)))): ?>
                    <button type="submit" name="opcion" value="attp"
                        class="block w-full text-lg mb-3 px-4 py-3 rounded-xl bg-blue-600 text-white hover:bg-blue-800 font-bold">
                        Sistema de Préstamos (ATTP)
                    </button>
                <?php endif; ?>
            </form>
            <div class="mt-8 text-xs text-gray-400">Estás logueado como <?= $nombre . " " . $apellido ?></div>
        </div>
    </section>
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 Escuela Técnica 20 D.E. 20 "Carolina Muzilli". Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
</body>

</html>