<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once '../../includes/db.php';

// Obtener materias
$materias = [];
$sql = "SELECT id, nombre, codigo, categoria, es_contraturno, estado FROM materias ORDER BY nombre";
$result = $conexion->query($sql);
while ($row = $result->fetch_assoc()) {
    $materias[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Materias</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex">
    <nav class="w-60 bg-white shadow-lg px-6 py-4 flex flex-col gap-2">
        <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400">
            <img src="../../images/et20ico.ico" class="h-28 w-28">
        </div>
        <div class="flex items-center mb-10 gap-2">
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div class="flex flex-col pl-3">
                <div class="font-bold text-lg"><?php echo $usuario['nombre']; ?></div>
                <div class="font-bold text-lg"><?php echo $usuario['apellido']; ?></div>
                <div class="mt-2 text-xs text-gray-500">Administrador/a</div>
            </div>
        </div>
        <a href="admin.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200">🏠 Inicio</a>
        <a href="usuarios.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">👥 Usuarios</a>
        <a href="cursos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">🏫 Cursos</a>
        <a href="alumnos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">👤 Alumnos</a>
        <a href="materias.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">📚 Materias</a>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>

    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6">📚 Gestión de Materias</h1>
        <?php if (isset($_GET['ok'])): ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-green-100 border border-green-400 text-green-800">
                <?php
                switch ($_GET['ok']) {
                    case 'nueva':
                        echo '✅ Materia creada correctamente.';
                        break;
                    case 'estado_actualizado':
                        echo '🔁 Estado de la materia actualizado.';
                        break;
                }
                ?>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-red-100 border border-red-400 text-red-800">
                <?php
                switch ($_GET['error']) {
                    case 'faltan_campos':
                        echo '❗ Por favor completá el nombre de la materia.';
                        break;
                    case 'duplicado':
                        echo '⚠️ Ya existe una materia con ese nombre.';
                        break;
                    case 'estado_invalido':
                        echo '❌ Estado no válido.';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Crear materia -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-semibold mb-4">➕ Crear nueva materia</h2>
                <form action="./utils/admin_crear_materia.php" method="post" class="flex flex-col gap-4">
                    <input type="text" name="nombre" placeholder="Nombre de la materia" class="px-4 py-2 border rounded-xl" required>
                    <input type="text" name="codigo" placeholder="Código interno (opcional)" class="px-4 py-2 border rounded-xl">
                    <input type="text" name="categoria" placeholder="Categoría (ej: Ciencias, Talleres...)" class="px-4 py-2 border rounded-xl">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="es_contraturno" value="1" class="accent-indigo-600">
                        Es contraturno
                    </label>
                    <button type="submit" class="bg-indigo-600 text-white py-2 rounded-xl hover:bg-indigo-700">Crear materia</button>
                </form>
            </div>

            <!-- Lista de materias -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-semibold mb-4">📋 Materias registradas</h2>
                <ul class="max-h-[400px] overflow-y-auto text-sm">
                    <?php foreach ($materias as $m): ?>
                        <li class="flex justify-between items-center border-b py-2">
                            <div>
                                <div class="font-medium"><?php echo $m['nombre']; ?> <?php echo $m['codigo'] ? "({$m['codigo']})" : ""; ?></div>
                                <div class="text-xs text-gray-500"><?php echo $m['categoria'] ?: 'Sin categoría'; ?> <?php echo $m['es_contraturno'] ? '• Contraturno' : ''; ?></div>
                            </div>
                            <form action="./utils/admin_toggle_estado_materia.php" method="post">
                                <input type="hidden" name="materia_id" value="<?php echo $m['id']; ?>">
                                <input type="hidden" name="estado" value="<?php echo $m['estado']; ?>">
                                <button type="submit" class="text-xs px-3 py-1 rounded-xl text-white <?php echo $m['estado'] === 'activo' ? 'bg-red-500 hover:bg-red-600' : 'bg-green-600 hover:bg-green-700'; ?>">
                                    <?php echo $m['estado'] === 'activo' ? 'Desactivar' : 'Activar'; ?>
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </div>
    </main>
</body>

</html>