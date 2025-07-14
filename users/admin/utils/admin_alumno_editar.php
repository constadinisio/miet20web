<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../../login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once '../../../includes/db.php';

$alumno_id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alumno_id = $_POST['alumno_id'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $dni = $_POST['dni'];
    $mail = $_POST['mail'];
    $anio = $_POST['anio'];
    $division = $_POST['division'];

    $sql = "UPDATE usuarios SET nombre=?, apellido=?, dni=?, mail=?, anio=?, division=? WHERE id=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssssi", $nombre, $apellido, $dni, $mail, $anio, $division, $alumno_id);
    $stmt->execute();
    $stmt->close();

    header("Location: ../alumnos.php");
    exit;
}

// Traer datos actuales
$alumno = null;
if ($alumno_id) {
    $sql = "SELECT id, nombre, apellido, dni, mail, anio, division FROM usuarios WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $alumno_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $alumno = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Alumno</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex">
    <nav class="w-60 bg-white shadow-lg px-6 py-4 flex flex-col gap-2">
        <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400">
            <img src="../../../images/et20ico.ico" class="block items-center h-28 w-28">
        </div>
        <div class="flex items-center mb-10 gap-2">
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div class="flex flex-col pl-3">
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['nombre']; ?></div>
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['apellido']; ?></div>
                <div class="mt-2 text-xs text-gray-500">Administrador/a</div>
            </div>
        </div>
        <a href="../admin.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="../usuarios.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">👥 Usuarios</a>
        <a href="../cursos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">🏫 Cursos</a>
        <a href="../alumnos.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">👤 Alumnos</a>
        <a href="../materias.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📚 Materias</a>
        <a href="../horarios.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">⏰ Horarios</a>
        <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
            <form method="post" action="../../../includes/cambiar_rol.php" class="mt-auto mb-3">
                <select name="rol" onchange="this.form.submit()" class="w-full px-3 py-2 border text-sm rounded-xl text-gray-700 bg-white">
                    <?php foreach ($_SESSION['roles_disponibles'] as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php if ($_SESSION['usuario']['rol'] == $r['id']) echo 'selected'; ?>>
                            Cambiar a: <?php echo ucfirst($r['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <button onclick="window.location='../../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6">Editar Alumno</h1>
        <?php if ($alumno): ?>
            <form method="post" class="bg-white rounded-xl p-8 shadow flex flex-col gap-4 max-w-xl">
                <input type="hidden" name="alumno_id" value="<?php echo $alumno['id']; ?>">
                <div>
                    <label class="font-bold">Nombre:</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($alumno['nombre']); ?>" class="border rounded px-4 py-2 w-full" required>
                </div>
                <div>
                    <label class="font-bold">Apellido:</label>
                    <input type="text" name="apellido" value="<?php echo htmlspecialchars($alumno['apellido']); ?>" class="border rounded px-4 py-2 w-full" required>
                </div>
                <div>
                    <label class="font-bold">DNI:</label>
                    <input type="text" name="dni" value="<?php echo htmlspecialchars($alumno['dni']); ?>" class="border rounded px-4 py-2 w-full" required>
                </div>
                <div>
                    <label class="font-bold">Mail:</label>
                    <input type="email" name="mail" value="<?php echo htmlspecialchars($alumno['mail']); ?>" class="border rounded px-4 py-2 w-full" required>
                </div>
                <div>
                    <label class="font-bold">Año:</label>
                    <input type="text" name="anio" value="<?php echo htmlspecialchars($alumno['anio']); ?>" class="border rounded px-4 py-2 w-full">
                </div>
                <div>
                    <label class="font-bold">División:</label>
                    <input type="text" name="division" value="<?php echo htmlspecialchars($alumno['division']); ?>" class="border rounded px-4 py-2 w-full">
                </div>
                <div>
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl hover:bg-indigo-700 font-bold">Guardar cambios</button>
                    <a href="../alumnos.php" class="ml-4 text-gray-600 hover:underline">Cancelar</a>
                </div>
            </form>
        <?php else: ?>
            <div class="text-red-600 font-bold">Alumno no encontrado.</div>
        <?php endif; ?>
    </main>
</body>

</html>