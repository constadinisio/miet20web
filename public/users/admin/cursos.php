<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: /login.php?error=rol");
    exit;
}

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

$usuario = $_SESSION['usuario'];
require_once __DIR__ . '/../../../backend/includes/db.php';

// Listado de cursos
$cursos = [];
$sql = "SELECT id, anio, division, turno, estado FROM cursos ORDER BY anio, division";
$result = $conexion->query($sql);
while ($row = $result->fetch_assoc()) {
    $cursos[] = $row;
}
$curso_id = $_GET['curso_id'] ?? null;

// Listado de alumnos en un curso
$alumnos = [];
$total = 0;
if ($curso_id) {
    $sql2 = "SELECT u.id, u.nombre, u.apellido FROM alumno_curso ac JOIN usuarios u ON ac.alumno_id = u.id WHERE ac.curso_id = ?";
    $stmt2 = $conexion->prepare($sql2);
    $stmt2->bind_param("i", $curso_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row2 = $result2->fetch_assoc()) {
        $alumnos[] = $row2;
    }
    $stmt2->close();

    $sql = "SELECT COUNT(*) AS total FROM alumno_curso WHERE curso_id = ? AND estado = 'activo'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $curso_id);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Cursos</title>
    <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
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
        <a href="usuarios.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Usuarios">
            <span class="text-xl">👥</span><span class="sidebar-label">Usuarios</span>
        </a>
        <a href="cursos.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100 transition" title="Cursos">
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
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
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
        <?php if (isset($_GET['ok'])): ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-green-100 border border-green-400 text-green-800">
                <?php
                switch ($_GET['ok']) {
                    case 'nuevo':
                        echo '✅ Curso creado correctamente.';
                        break;
                    case 'estado_cambiado':
                        echo '🔁 Estado del curso actualizado.';
                        break;
                }
                ?>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-red-100 border border-red-400 text-red-800">
                <?php
                switch ($_GET['error']) {
                    case 'duplicado':
                        echo '⚠️ Ya existe un curso con ese año, división y turno.';
                        break;
                    case 'estado_invalido':
                        echo '❌ Estado no válido para cambiar.';
                        break;
                    case 'faltan_campos':
                        echo '❗ Por favor completá todos los campos.';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        <h1 class="text-2xl font-bold mb-6">🏫 Gestión de Cursos</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Card: Ver alumnos -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-semibold mb-4">🔍 Ver alumnos por curso</h2>
                <form class="mb-4 flex gap-4" method="get">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <select name="curso_id" class="px-4 py-2 rounded-xl border flex-1" required>
                        <option value="">Seleccionar curso</option>
                        <?php foreach ($cursos as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php if ($curso_id == $c['id']) echo "selected"; ?>>
                                <?php echo $c['anio'] . "°" . $c['division'] . " (" . $c['turno'] . ")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Ver</button>
                </form>

                <?php if ($curso_id): ?>
                    <div class="text-sm mb-3 text-gray-700">👥 Total de alumnos: <span class="text-indigo-600 font-semibold"><?php echo $total; ?></span></div>
                    <div class="max-h-[300px] overflow-y-auto border rounded-xl">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="text-left px-4 py-2">Nombre</th>
                                    <th class="text-left px-4 py-2">Apellido</th>
                                    <th class="text-left px-4 py-2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alumnos as $a): ?>
                                    <tr class="border-b">
                                        <td class="px-4 py-2"><?php echo $a['nombre']; ?></td>
                                        <td class="px-4 py-2"><?php echo $a['apellido']; ?></td>
                                        <td class="px-4 py-2">
                                            <form method="post" action="admin_curso_eliminar_alumno.php" onsubmit="return confirm('¿Eliminar este alumno del curso?');">
                                                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                                                <input type="hidden" name="alumno_id" value="<?php echo $a['id']; ?>">
                                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                                <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($alumnos)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-gray-500 py-3">No hay alumnos en este curso.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <form method="post" action="admin_curso_agregar_alumno.php" class="flex gap-2 items-end mt-4">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                        <input type="text" name="dni" placeholder="DNI del alumno" class="px-4 py-2 border rounded-xl flex-1" required>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700">Agregar</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Card: Crear y gestionar cursos -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-semibold mb-4">⚙️ Cambiar estado de cursos</h2>
                <h3 class="text-lg font-semibold mb-2">Cambiar estado de cursos</h3>
                <ul class="text-sm max-h-[400px] overflow-y-auto">
                    <?php foreach ($cursos as $c): ?>
                        <li class="flex justify-between items-center border-b py-2">
                            <span><?php echo $c['anio'] . "°" . $c['division'] . " (" . $c['turno'] . ")"; ?></span>
                            <form action="admin_curso_toggle_estado.php" method="post">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="curso_id" value="<?php echo $c['id']; ?>">
                                <input type="hidden" name="estado" value="<?php echo $c['estado']; ?>">
                                <button type="submit" class="text-sm px-3 py-1 rounded-xl <?php echo $c['estado'] === 'activo' ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'; ?> text-white">
                                    <?php echo $c['estado'] === 'activo' ? 'Desactivar' : 'Activar'; ?>
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
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