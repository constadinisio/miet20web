<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: /login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once __DIR__ . '/../../../backend/includes/db.php';

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

// Categorías
$categorias = [];
$res = $conexion->query("SELECT id, nombre FROM categorias ORDER BY nombre");
while ($row = $res->fetch_assoc()) {
    $categorias[] = $row;
}

// Materias
$materiasPorCategoria = [];
$res = $conexion->query("
    SELECT m.id, m.nombre, m.codigo, m.categoria_id, m.es_contraturno, m.estado, c.nombre AS categoria_nombre
    FROM materias m
    LEFT JOIN categorias c ON m.categoria_id = c.id
    ORDER BY c.nombre, m.nombre
");
while ($m = $res->fetch_assoc()) {
    $categoria = $m['categoria_nombre'] ?: 'Sin categoría';
    $materiasPorCategoria[$categoria][] = $m;
}

// Cursos
$cursos = [];
$res = $conexion->query("SELECT id, anio, division FROM cursos ORDER BY anio, division");
while ($c = $res->fetch_assoc()) {
    $cursos[] = $c;
}

// --- BLOQUE BUSCADOR DE PROFESORES ---
$busqueda = $_GET['busqueda_profesor'] ?? '';
$profesores = [];
if ($busqueda !== '') {
    $sql = "SELECT id, nombre, apellido FROM usuarios 
            WHERE rol = 3 AND (nombre LIKE ? OR apellido LIKE ?) 
            ORDER BY apellido, nombre LIMIT 30";
    $stmt = $conexion->prepare($sql);
    $like = "%$busqueda%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $profesores[] = $row;
    $stmt->close();
}

// Asignaciones solo para el profe elegido
$profesor_id = $_GET['profesor_id'] ?? null;
$asignaciones = [];
if ($profesor_id) {
    $stmt = $conexion->prepare("SELECT pcm.id, c.anio, c.division, m.nombre AS materia 
        FROM profesor_curso_materia pcm
        JOIN cursos c ON pcm.curso_id = c.id
        JOIN materias m ON pcm.materia_id = m.id
        WHERE pcm.profesor_id = ?");
    $stmt->bind_param("i", $profesor_id);
    $stmt->execute();
    $asignaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Materias</title>
    <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
    <!-- Sidebar -->
    <nav id="sidebar" class="w-60 transition-all duration-300 bg-white shadow-lg px-4 py-4 flex flex-col gap-2">
        <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400 h-28">
            <img src="/images/et20ico.ico" class="sidebar-expanded block h-full w-auto object-contain">
            <img src="/images/et20ico.ico" class="sidebar-collapsed hidden h-10 w-auto object-contain">
        </div>
        <a href="admin.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Inicio">
            <span class="text-xl">🏠</span><span class="sidebar-label">Inicio</span>
        </a>
        <a href="usuarios.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Usuarios">
            <span class="text-xl">👥</span><span class="sidebar-label">Usuarios</span>
        </a>
        <a href="cursos.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Cursos">
            <span class="text-xl">🏫</span><span class="sidebar-label">Cursos</span>
        </a>
        <a href="alumnos.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Alumnos">
            <span class="text-xl">👤</span><span class="sidebar-label">Alumnos</span>
        </a>
        <a href="materias.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition" title="Materias">
            <span class="text-xl">📚</span><span class="sidebar-label">Materias</span>
        </a>
        <a href="horarios.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Horarios">
            <span class="text-xl">⏰</span><span class="sidebar-label">Horarios</span>
        </a>
        <a href="progresion.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Progresión">
            <span class="text-xl">📈</span><span class="sidebar-label">Progresión</span>
        </a>
        <a href="historial.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Historial p/ Curso">
            <span class="text-xl">📋</span><span class="sidebar-label">Historial p/ Curso</span>
        </a>
        <button onclick="window.location='/includes/logout.php'" class="sidebar-item flex items-center justify-center gap-2 mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">
            <span class="text-xl">🚪</span><span class="sidebar-label">Salir</span>
        </button>
    </nav>
    <main class="flex-1 p-10">
        <div class="w-full flex justify-end items-center gap-4 mb-6">
            <div class="flex items-center gap-3 bg-white rounded-xl px-5 py-2 shadow border">
                <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-12 h-12 object-cover">
                <div class="flex flex-col pr-2 text-right">
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['nombre']; ?></div>
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['apellido']; ?></div>
                    <div class="mt-1 text-xs text-gray-500">Administrador/a</div>
                </div>
                <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
                    <form method="post" action="/includes/cambiar_rol.php" class="ml-4">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <select name="rol" onchange="this.form.submit()" class="px-2 py-1 border text-sm rounded-xl text-gray-700 bg-white">
                            <?php foreach ($_SESSION['roles_disponibles'] as $r): ?>
                                <option value="<?php echo $r['id']; ?>" <?php if ($_SESSION['usuario']['rol'] == $r['id']) echo 'selected'; ?>>
                                    Cambiar a: <?php echo ucfirst($r['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
                <button id="btn-notificaciones" class="relative focus:outline-none group">
                    <!-- Campanita Font Awesome -->
                    <i id="icono-campana" class="fa-regular fa-bell text-2xl text-gray-400 group-hover:text-gray-700 transition-colors"></i>
                    <!-- Badge cantidad (oculto si no hay notificaciones) -->
                    <span id="badge-notificaciones"
                        class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1 hidden border border-white font-bold"
                        style="min-width:1.2em; text-align:center;"></span>
                </button>
            </div>
        </div>

        <!-- POPUP DE NOTIFICACIONES -->
        <div id="popup-notificaciones" class="hidden fixed right-4 top-16 w-80 max-h-[70vh] bg-white shadow-2xl rounded-2xl border border-gray-200 z-50 flex flex-col">
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <span class="font-bold text-gray-800 text-lg">Notificaciones</span>
                <button onclick="cerrarPopup()" class="text-gray-400 hover:text-red-400 text-xl">&times;</button>
            </div>
            <div id="lista-notificaciones" class="overflow-y-auto p-2">
                <!-- Notificaciones aquí -->
            </div>
        </div>

        <h1 class="text-2xl font-bold mb-6">📚 Gestión de Materias</h1>

        <!-- ALERTAS -->
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
                    case 'asignada':
                        echo '✅ Materia asignada al profesor.';
                        break;
                    case 'eliminada':
                        echo '🗑️ Asignación eliminada.';
                        break;
                }
                ?>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-red-100 border border-red-400 text-red-800">
                <?php
                switch ($_GET['error']) {
                    case 'faltan_campos':
                        echo '❗ Por favor completá todos los campos requeridos.';
                        break;
                    case 'duplicado':
                        echo '⚠️ Ya existe una materia con ese nombre.';
                        break;
                    case 'estado_invalido':
                        echo '❌ Estado inválido.';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Card 1: Crear nueva materia -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-semibold mb-4">➕ Crear nueva materia</h2>
                <form action="admin_crear_materia.php" method="post" class="flex flex-col gap-4">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="text" name="nombre" placeholder="Nombre de la materia" class="px-4 py-2 border rounded-xl" required>
                    <input type="text" name="codigo" placeholder="Código interno (opcional)" class="px-4 py-2 border rounded-xl">
                    <select name="categoria_id" required class="px-4 py-2 border rounded-xl">
                        <option value="">Seleccionar categoría</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="es_contraturno" value="1" class="accent-indigo-600">
                        Es contraturno
                    </label>
                    <button type="submit" class="bg-indigo-600 text-white py-2 rounded-xl hover:bg-indigo-700">Crear materia</button>
                </form>
            </div>

            <!-- Card 2: Lista de materias agrupadas por categoría -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-semibold mb-4">📋 Materias registradas por categoría</h2>

                <?php foreach ($materiasPorCategoria as $categoria => $materias): ?>
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-indigo-700 mb-2">📁 <?= htmlspecialchars($categoria) ?></h3>
                        <ul class="border rounded-xl divide-y max-h-[300px] overflow-y-auto text-sm">
                            <?php foreach ($materias as $m): ?>
                                <li class="flex justify-between items-center px-4 py-2">
                                    <div>
                                        <div class="font-medium">
                                            <?= htmlspecialchars($m['nombre']) ?>
                                            <?= $m['codigo'] ? " ({$m['codigo']})" : "" ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= $m['es_contraturno'] ? 'Contraturno' : '' ?>
                                        </div>
                                    </div>
                                    <form action="admin_toggle_estado_materia.php" method="post">
                                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="materia_id" value="<?= $m['id'] ?>">
                                        <input type="hidden" name="estado" value="<?= $m['estado'] ?>">
                                        <button type="submit"
                                            class="text-xs px-3 py-1 rounded-xl text-white <?= $m['estado'] === 'activo' ? 'bg-red-500 hover:bg-red-600' : 'bg-green-600 hover:bg-green-700' ?>">
                                            <?= $m['estado'] === 'activo' ? 'Desactivar' : 'Activar' ?>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Card 3: Asignar materias a profesores -->
            <div class="col-span-1 md:col-span-2 bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-semibold mb-4">👨‍🏫 Asignar materias a profesores</h2>

                <!-- Selección de profesor -->
                <!-- Buscador de profesores -->
                <form method="get" class="mb-6 flex flex-col md:flex-row gap-4 items-center">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="text" name="busqueda_profesor" class="border rounded-xl px-4 py-2 flex-1"
                        placeholder="Buscar profesor por nombre o apellido" value="<?= htmlspecialchars($_GET['busqueda_profesor'] ?? '') ?>">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl hover:bg-blue-700">Buscar</button>
                </form>

                <!-- Selección de profesor tras búsqueda -->
                <?php if (!empty($profesores)): ?>
                    <form method="get" class="mb-6 flex gap-4 items-center">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="busqueda_profesor" value="<?= htmlspecialchars($busqueda) ?>">
                        <select name="profesor_id" class="px-4 py-2 rounded-xl border w-full md:w-1/3" required onchange="this.form.submit()">
                            <option value="">Seleccioná un profesor</option>
                            <?php foreach ($profesores as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($profesor_id == $p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['apellido'] . ', ' . $p['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php elseif ($busqueda !== ''): ?>
                    <div class="text-red-600 mb-6">No se encontraron profesores con ese nombre/apellido.</div>
                <?php endif; ?>

                <?php if ($profesor_id): ?>
                    <!-- Asignar materia -->
                    <form method="post" action="admin_asignar_materia.php" class="flex flex-col md:flex-row gap-4 mb-6">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="profesor_id" value="<?php echo $profesor_id; ?>">
                        <select name="curso_id" class="border rounded-xl px-4 py-2 flex-1" required>
                            <option value="">Curso</option>
                            <?php foreach ($cursos as $c): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo $c['anio'] . "°" . $c['division']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="materia_id" class="border rounded-xl px-4 py-2 flex-1" required>
                            <option value="" disabled hidden selected>Seleccionar materia</option>
                            <?php foreach ($materiasPorCategoria as $categoria => $lista): ?>
                                <optgroup label="📂 <?= htmlspecialchars($categoria) ?>">
                                    <?php foreach ($lista as $m): ?>
                                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-xl hover:bg-green-700">Asignar</button>
                    </form>

                    <!-- Lista de asignaciones -->
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="text-left px-4 py-2">Curso</th>
                                <th class="text-left px-4 py-2">Materia</th>
                                <th class="text-left px-4 py-2">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asignaciones as $a): ?>
                                <tr class="border-b">
                                    <td class="px-4 py-2"><?php echo $a['anio'] . "°" . $a['division']; ?></td>
                                    <td class="px-4 py-2"><?php echo $a['materia']; ?></td>
                                    <td class="px-4 py-2">
                                        <form method="post" action="admin_eliminar_asignacion.php" onsubmit="return confirm('¿Eliminar esta asignación?');">
                                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                            <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                            <input type="hidden" name="profesor_id" value="<?php echo $profesor_id; ?>">
                                            <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($asignaciones)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-gray-500">No hay asignaciones registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
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
    <script>
        document.getElementById('btn-notificaciones').addEventListener('click', function() {
            const popup = document.getElementById('popup-notificaciones');
            popup.classList.toggle('hidden');
            cargarNotificaciones();
        });

        function cerrarPopup() {
            document.getElementById('popup-notificaciones').classList.add('hidden');
        }

        function marcarLeida(destinatarioId) {
            fetch('/../../../includes/notificaciones/marcar_leida.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + encodeURIComponent(destinatarioId)
                }).then(res => res.json())
                .then(data => {
                    if (data.ok) cargarNotificaciones();
                });
        }

        function confirmar(destinatarioId) {
            fetch('/../../../includes/notificaciones/confirmar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + encodeURIComponent(destinatarioId)
                }).then(res => res.json())
                .then(data => {
                    if (data.ok) cargarNotificaciones();
                });
        }

        function cargarNotificaciones() {
            fetch('/../../../includes/notificaciones/listar.php')
                .then(res => res.json())
                .then(data => {
                    const lista = document.getElementById('lista-notificaciones');
                    const badge = document.getElementById('badge-notificaciones');
                    const campana = document.getElementById('icono-campana');
                    lista.innerHTML = '';
                    let sinLeer = 0;
                    if (data.length === 0) {
                        lista.innerHTML = '<div class="text-center text-gray-400 p-4">Sin notificaciones nuevas.</div>';
                        badge.classList.add('hidden');
                        // Ícono gris claro, sin detalles rojos
                        campana.classList.remove('text-red-500');
                        campana.classList.add('text-gray-400');
                        campana.classList.remove('fa-shake');
                    } else {
                        data.forEach(n => {
                            if (n.estado_lectura === 'NO_LEIDA') sinLeer++;
                            lista.innerHTML += `
                                <div class="rounded-xl px-3 py-2 mb-2 bg-gray-100 shadow hover:bg-gray-50 flex flex-col">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-base font-semibold">${n.titulo}</span>
                                    <span class="ml-auto text-xs">${n.fecha_creacion}</span>
                                </div>
                                <div class="text-sm text-gray-700 mb-2">${n.contenido}</div>
                                <div class="flex gap-2">
                                    ${n.estado_lectura === 'NO_LEIDA' ? `<button class="text-blue-600 text-xs" onclick="marcarLeida(${n.destinatario_row_id})">Marcar como leída</button>` : ''}
                                    ${(n.requiere_confirmacion == 1 && n.estado_lectura !== 'CONFIRMADA') ? `<button class="text-green-600 text-xs" onclick="confirmar(${n.destinatario_row_id})">Confirmar</button>` : ''}
                                    ${n.estado_lectura === 'LEIDA' ? '<span class="text-green-700 text-xs">Leída</span>' : ''}
                                    ${n.estado_lectura === 'CONFIRMADA' ? '<span class="text-green-700 text-xs">Confirmada</span>' : ''}
                                </div>
                                </div>`;
                        });

                        if (sinLeer > 0) {
                            badge.textContent = sinLeer;
                            badge.classList.remove('hidden');
                            // Ícono gris pero con detalle rojo (y/o animación, opcional)
                            campana.classList.remove('text-gray-400');
                            campana.classList.add('text-red-500');
                            campana.classList.add('fa-shake'); // animación de FA, opcional
                        } else {
                            badge.classList.add('hidden');
                            campana.classList.remove('text-red-500');
                            campana.classList.add('text-gray-400');
                            campana.classList.remove('fa-shake');
                        }
                    }
                });
        }
        document.addEventListener('DOMContentLoaded', function() {
            cargarNotificaciones(); // Esto chequea notificaciones ni bien se carga la página
            setInterval(cargarNotificaciones, 15000);
        });
    </script>
</body>

</html>