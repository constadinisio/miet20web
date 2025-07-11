<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once '../../includes/db.php';

$busqueda = $_GET['busqueda'] ?? '';
$alumnos = [];
$sql = "SELECT id, nombre, apellido, dni, mail, anio, division FROM usuarios WHERE rol = 4";

if ($busqueda !== '') {
    $sql .= " AND (nombre LIKE ? OR apellido LIKE ?)";
    $stmt = $conexion->prepare($sql . " ORDER BY apellido, nombre");
    $like = "%$busqueda%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql .= " ORDER BY apellido, nombre";
    $result = $conexion->query($sql);
}

while ($row = $result->fetch_assoc()) {
    $alumnos[] = $row;
}
if (isset($stmt)) $stmt->close();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Alumnos</title>
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
            <img src="../../images/et20ico.ico" class="block items-center h-28 w-28">
        </div>
        <div class="flex items-center mb-10 gap-2">
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div class="flex flex-col pl-3">
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['nombre']; ?></div>
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['apellido']; ?></div>
                <div class="mt-2 text-xs text-gray-500">Administrador/a</div>
            </div>
        </div>
        <a href="admin.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="usuarios.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">👥 Usuarios</a>
        <a href="cursos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">🏫 Cursos</a>
        <a href="alumnos.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">👤 Alumnos</a>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6">👤 Gestión de Alumnos</h1>
        <div class="overflow-x-auto">
            <form method="get" class="mb-4 flex gap-3">
                <input type="text" name="busqueda" placeholder="Buscar por nombre o apellido" value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>" class="px-4 py-2 rounded-xl border w-64">
                <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Buscar</button>
            </form>
            <div class="overflow-y-auto rounded-xl shadow bg-white" style="max-height: 500px;">
                <table class="min-w-full bg-white rounded-xl shadow">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 text-left">Nombre</th>
                            <th class="py-2 px-4 text-left">Apellido</th>
                            <th class="py-2 px-4 text-left">DNI</th>
                            <th class="py-2 px-4 text-left">Mail</th>
                            <th class="py-2 px-4 text-left">Año</th>
                            <th class="py-2 px-4 text-left">División</th>
                            <th class="py-2 px-4 text-left">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumnos as $a): ?>
                            <tr>
                                <td class="py-2 px-4"><?php echo $a['nombre']; ?></td>
                                <td class="py-2 px-4"><?php echo $a['apellido']; ?></td>
                                <td class="py-2 px-4"><?php echo $a['dni']; ?></td>
                                <td class="py-2 px-4"><?php echo $a['mail']; ?></td>
                                <td class="py-2 px-4"><?php echo $a['anio']; ?></td>
                                <td class="py-2 px-4"><?php echo $a['division']; ?></td>
                                <td class="py-2 px-4">
                                    <a href="./utils/admin_alumno_editar.php?id=<?php echo $a['id']; ?>" class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($alumnos)): ?>
                            <tr>
                                <td colspan="7" class="py-4 text-center text-gray-500">No hay alumnos registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>