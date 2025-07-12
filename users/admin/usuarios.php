<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once '../../includes/db.php';

// Usuarios pendientes (status=0)
$usuarios_pendientes = [];
$sql = "SELECT id, nombre, apellido, mail, rol, status FROM usuarios WHERE status = 0";
$result = $conexion->query($sql);
while ($row = $result->fetch_assoc()) {
    $usuarios_pendientes[] = $row;
}

// Usuarios activos (no alumnos, solo rol 1-3 y 5)
$usuarios_activos = [];
$sql = "SELECT id, nombre, apellido, mail, rol FROM usuarios 
        WHERE status = 1 AND rol IN (1,2,3,5)";
$result = $conexion->query($sql);
while ($row = $result->fetch_assoc()) {
    $usuarios_activos[] = $row;
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
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
        <a href="usuarios.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">👥 Usuarios</a>
        <a href="cursos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">🏫 Cursos</a>
        <a href="alumnos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">👤 Alumnos</a>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6">👥 Gestión de Usuarios Pendientes</h1>
        <div class="overflow-x-auto">
            <div class="max-h-[400px] overflow-y-auto rounded-xl shadow">
                <table class="min-w-full bg-white rounded-xl shadow">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 text-left">Nombre</th>
                            <th class="py-2 px-4 text-left">Apellido</th>
                            <th class="py-2 px-4 text-left">Mail</th>
                            <th class="py-2 px-4 text-left">Rol</th>
                            <th class="py-2 px-4 text-left">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios_pendientes as $u): ?>
                            <tr>
                                <td class="py-2 px-4"><?php echo $u['nombre']; ?></td>
                                <td class="py-2 px-4"><?php echo $u['apellido']; ?></td>
                                <td class="py-2 px-4"><?php echo $u['mail']; ?></td>
                                <td class="py-2 px-4">
                                    <form method="post" action="./utils/admin_usuario_aprobar.php" class="flex items-center gap-2">
                                        <input type="hidden" name="usuario_id" value="<?php echo $u['id']; ?>">
                                        <select name="rol" class="border rounded px-2 py-1">
                                            <option value="1">Administrador</option>
                                            <option value="2">Preceptor</option>
                                            <option value="3">Profesor</option>
                                            <option value="4">Alumno</option>
                                        </select>
                                        <button type="submit" class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700">Aprobar</button>
                                    </form>
                                </td>
                                <td class="py-2 px-4">
                                    <form method="post" action="./utils/admin_usuario_rechazar.php" onsubmit="return confirm('¿Estás seguro de rechazar este usuario?');">
                                        <input type="hidden" name="usuario_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600">Rechazar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($usuarios_pendientes)): ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">No hay usuarios pendientes de aprobación.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <h2 class="text-xl font-bold mt-10 mb-4">👤 Usuarios Activos (No alumnos)</h2>
        <div class="overflow-x-auto">
            <div class="max-h-[400px] overflow-y-auto rounded-xl shadow">
                <table class="min-w-full bg-white rounded-xl shadow">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 text-left">Nombre</th>
                            <th class="py-2 px-4 text-left">Apellido</th>
                            <th class="py-2 px-4 text-left">Mail</th>
                            <th class="py-2 px-4 text-left">Rol</th>
                            <th class="py-2 px-4 text-left">Editar</th>
                            <!-- Si querés, sumar Borrar/Desactivar -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios_activos as $u): ?>
                            <tr>
                                <td class="py-2 px-4"><?= htmlspecialchars($u['nombre']) ?></td>
                                <td class="py-2 px-4"><?= htmlspecialchars($u['apellido']) ?></td>
                                <td class="py-2 px-4"><?= htmlspecialchars($u['mail']) ?></td>
                                <td class="py-2 px-4">
                                    <?php
                                    switch ($u['rol']) {
                                        case 1:
                                            echo "Administrador";
                                            break;
                                        case 2:
                                            echo "Preceptor";
                                            break;
                                        case 3:
                                            echo "Profesor";
                                            break;
                                        case 5:
                                            echo "ATTP";
                                            break;
                                        default:
                                            echo "Desconocido";
                                            break;
                                    }
                                    ?>
                                </td>
                                <td class="py-2 px-4">
                                    <a href="./utils/admin_editar_usuario.php?id=<?= $u['id'] ?>" class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">Editar</a>
                                </td>
                                <!--
                                <td class="py-2 px-4">
                                    <form method="post" action="usuario_borrar.php" onsubmit="return confirm('¿Seguro que querés borrar este usuario?');">
                                        <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600">Borrar</button>
                                    </form>
                                </td>
                                -->
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($usuarios_activos)): ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">No hay usuarios activos (Admin, Preceptor, Profesor, ATTP).</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>