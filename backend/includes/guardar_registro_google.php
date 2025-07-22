<?php
require_once __DIR__ . '/../../../backend/includes/db.php';

$mail = trim($_POST['mail'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$dni = trim($_POST['dni'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

// Validación mínima
if (!$mail || !$nombre || !$apellido || !$dni || !$fecha_nacimiento || !$contrasena) {
    die("Faltan campos obligatorios.");
}

// Hashear la contraseña
$hash = password_hash($contrasena, PASSWORD_DEFAULT);

// Insertar usuario (ajustá el SQL según tu tabla)
$sql = "INSERT INTO usuarios (mail, nombre, apellido, dni, telefono, direccion, fecha_nacimiento, contrasena, rol) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)"; // rol 4 = alumno por defecto
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssssssss", $mail, $nombre, $apellido, $dni, $telefono, $direccion, $fecha_nacimiento, $hash);
$ok = $stmt->execute();

if ($ok) {
    // Mostrar mensaje de pendiente de aprobación y no intentar login
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head><meta charset='UTF-8'><title>Pendiente de Aprobación</title>
    <script src='https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4'></script>    <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
    <style>body {font-family: 'Poppins', sans-serif;}</style></head>
    <body class='bg-gray-100 flex flex-col min-h-screen'>
    <nav class='bg-white shadow-lg fixed w-full z-50'>
        <div class='max-w-7xl mx-auto px-4'>
            <div class='flex justify-center items-center h-16'>
                <div class='flex items-center'>
                    <a href='/index.php' class='flex items-center'>
                        <i class='fas text-3xl text-blue-600 mr-4 -right-500'></i>
                        <h1><img src='/images/et20ico.ico' alt='Icono personalizado' class='w-10 h-10'></h1>
                        <span class='text-xl font-semibold text-gray-800 ml-2'>Escuela Técnica 20 D.E. 20</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <main class='flex-grow flex justify-center items-center'>
    <div class='bg-white p-10 rounded-2xl shadow-xl text-center'>
        <h1 class='text-2xl font-bold text-yellow-600 mb-4'>Registro pendiente de aprobación</h1>
        <p class='text-gray-700 mb-6'>Tu registro fue enviado correctamente.<br>
        Un administrador lo revisará y te habilitará el acceso.<br>
        Volvé a intentar en unas horas.</p>
        <a href='/login.php' class='inline-block px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700'>Ir al inicio</a>

    </main></div></body></html>";
    exit;
} else {
    echo "Error al registrar: " . $stmt->error;
}