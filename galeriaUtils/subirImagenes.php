<?php
session_start();

// Verifica si hay sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db.php';

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $categoria = $_POST["categoria"];
    $autor = $_POST["autor"];
    $descripcion = $_POST["descripcion"];

    $archivo = $_FILES["imagen"];

    if ($archivo["error"] === UPLOAD_ERR_OK) {
        $nombre_tmp = $archivo["tmp_name"];
        $nombre_final = uniqid() . "_" . basename($archivo["name"]);
        $ruta_categoria = "imagenes/" . $categoria;

        if (!is_dir($ruta_categoria)) {
            mkdir($ruta_categoria, 0777, true);
        }

        $ruta_destino = $ruta_categoria . "/" . $nombre_final;

        if (move_uploaded_file($nombre_tmp, $ruta_destino)) {
            $stmt = $conexion->prepare("INSERT INTO imagenes (categoria, archivo, autor, descripcion) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $categoria, $nombre_final, $autor, $descripcion);
            $stmt->execute();
            $mensaje = "✅ Imagen subida correctamente.";
        } else {
            $mensaje = "❌ Error al mover la imagen.";
        }
    } else {
        $mensaje = "❌ Error al subir la imagen.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Subir Imagen</title>
    <link rel="stylesheet" href="../output.css">
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-center items-center h-16">
                <div class="flex items-center">
                    <a href="./index.php" class="flex items-center">
                        <i class="fas text-3xl text-blue-600 mr-4 -right-500"></i>
                        <h1><img src="../images/et20ico.ico" alt="Icono personalizado" class="w-10 h-10"></h1>
                        <span class="text-xl font-semibold text-gray-800 ml-2">Escuela Técnica 20 D.E. 20</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section class="relative min-h-screen w-full pt-16 text-white overflow-hidden">
        <!-- Imagen con blur -->
        <div class="absolute inset-0 bg-front-et20 bg-no-repeat bg-cover bg-center filter blur-sm scale-105"></div>

        <!-- Overlay opcional (oscurece un poco para mejorar legibilidad) -->
        <div class="absolute inset-0 bg-black/30"></div>

        <div class="relative max-w-xl mx-auto mt-12 mb-12 bg-white p-6 rounded shadow text-black">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold">Subir nueva imagen</h1>
                <a href="../includes/logout.php" class="bg-red-600 text-white px-4 py-2 rounded transition-colors hover:bg-red-700">
                    Cerrar sesión
                </a>
            </div>


            <?php if ($mensaje): ?>
                <div class="mb-4 p-3 rounded bg-blue-100 border border-blue-300 text-blue-800"><?= $mensaje ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-4 mb-12">
                <div>
                    <label class="block font-semibold">Categoría</label>
                    <select name="categoria" required class="w-full border rounded p-2">
                        <option value="">Seleccioná una</option>
                        <option value="Eventos">Eventos</option>
                        <option value="Talleres">Talleres</option>
                        <option value="Especialidades">Especialidades</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold">Autor</label>
                    <input type="text" name="autor" required class="w-full border rounded p-2">
                </div>

                <div>
                    <label class="block font-semibold">Descripción</label>
                    <textarea name="descripcion" rows="3" required class="w-full border rounded p-2"></textarea>
                </div>

                <div>
                    <label class="block font-semibold">Subir una imagen</label>
                    <input type="file" name="imagen" accept=".webp,.webp,.png" required class="bg-yellow-500 text-white px-2 py-2 rounded transition-colors hover:bg-yellow-600">
                </div>

                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                    Subir
                </button>
            </form>
        </div>
    </section>
    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 Escuela Técnica 20 D.E. 20 "Carolina Muzilli". Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
</body>

</html>