<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: /login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once __DIR__ . '/../../../backend/includes/db.php';

// Conexión a la base...
$sql = "SELECT * FROM usuarios WHERE rol = 0";
$result = $conexion->query($sql);
$usuarios_pendientes = [];
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
    <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .sidebar-item {
            min-height: 3.5rem;
            width: 100%;
        }

        .w-16 .sidebar-item {
            justify-content: center !important;
        }

        .w-16 .sidebar-item span.sidebar-label {
            display: none;
        }

        .w-16 .sidebar-item span.text-xl {
            margin: 0 auto;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex">
    <button id="toggleSidebar" class="absolute top-4 left-4 z-50 text-2xl hover:text-indigo-600 transition">
        ☰
    </button>
    <nav id="sidebar" class="w-60 transition-all duration-300 bg-white shadow-lg px-4 py-4 flex flex-col gap-2">
        <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400 h-28">
            <img src="/images/et20ico.ico" class="sidebar-expanded block h-full w-auto object-contain">
            <img src="/images/et20ico.ico" class="sidebar-collapsed hidden h-10 w-auto object-contain">
        </div>
        <div class="flex items-center mb-10 gap-2">
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div class="flex flex-col pl-3 sidebar-label">
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['nombre']; ?></div>
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['apellido']; ?></div>
                <div class="mt-2 text-xs text-gray-500">Administrador/a</div>
            </div>
        </div>
        <a href="admin.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200 transition" title="Inicio">
            <span class="text-xl">🏠</span><span class="sidebar-label">Inicio</span>
        </a>
        <a href="usuarios.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100 transition" title="Usuarios">
            <span class="text-xl">👥</span><span class="sidebar-label">Usuarios</span>
        </a>
        <a href="cursos.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Cursos">
            <span class="text-xl">🏫</span><span class="sidebar-label">Cursos</span>
        </a>
        <a href="alumnos.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Alumnos">
            <span class="text-xl">👤</span><span class="sidebar-label">Alumnos</span>
        </a>
        <a href="materias.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Materias">
            <span class="text-xl">📚</span><span class="sidebar-label">Materias</span>
        </a>
        <a href="horarios.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Horarios">
            <span class="text-xl">⏰</span><span class="sidebar-label">Horarios</span>
        </a>
        <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
            <form method="post" action="/includes/cambiar_rol.php" class="mt-auto mb-3 sidebar-label">
                <select name="rol" onchange="this.form.submit()" class="w-full px-3 py-2 border text-sm rounded-xl text-gray-700 bg-white">
                    <?php foreach ($_SESSION['roles_disponibles'] as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php if ($_SESSION['usuario']['rol'] == $r['id']) echo 'selected'; ?>>
                            Cambiar a: <?php echo ucfirst($r['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <button onclick="window.location='/includes/logout.php'" class="sidebar-item flex items-center justify-center gap-2 mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">
            <span class="text-xl">🚪</span><span class="sidebar-label">Salir</span>
        </button>
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
                                    <form method="post" action="admin_usuario_aprobar.php" class="flex items-center gap-2">
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
                                    <form method="post" action="admin_usuario_rechazar.php" onsubmit="return confirm('¿Estás seguro de rechazar este usuario?');">
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
                                    <a href="editar_usuario.php?id=<?= $u['id'] ?>" class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">Editar</a>
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
    <script>
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const labels = sidebar.querySelectorAll('.sidebar-label');
            const expandedElements = sidebar.querySelectorAll('.sidebar-expanded');
            const collapsedElements = sidebar.querySelectorAll('.sidebar-collapsed');

            if (sidebar.classList.contains('w-60')) {
                sidebar.classList.remove('w-60');
                sidebar.classList.add('w-16');
                labels.forEach(label => label.classList.add('hidden'));
                expandedElements.forEach(el => el.classList.add('hidden'));
                collapsedElements.forEach(el => el.classList.remove('hidden'));
            } else {
                sidebar.classList.remove('w-16');
                sidebar.classList.add('w-60');
                labels.forEach(label => label.classList.remove('hidden'));
                expandedElements.forEach(el => el.classList.remove('hidden'));
                collapsedElements.forEach(el => el.classList.add('hidden'));
            }
        });
    </script>
</body>

</html>