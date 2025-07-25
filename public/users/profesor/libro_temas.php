<?php
session_start();
if (
    !isset($_SESSION['usuario']) ||
    !is_array($_SESSION['usuario']) ||
    (int)$_SESSION['usuario']['rol'] !== 3
) {
    header("Location: /login.php?error=rol");
    exit;
}

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

$usuario = $_SESSION['usuario'];
require_once __DIR__ . '/../../../backend/includes/db.php';

$profesor_id = $usuario['id'];
// Buscar cursos y materias asignadas
$cursos = [];
$sql = "SELECT pcm.id, c.id AS curso_id, m.id AS materia_id, c.anio, c.division, m.nombre AS materia
        FROM profesor_curso_materia pcm
        JOIN cursos c ON pcm.curso_id = c.id
        JOIN materias m ON pcm.materia_id = m.id
        WHERE pcm.profesor_id = ?
        ORDER BY c.anio, c.division, m.nombre";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $profesor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cursos[] = $row;
}
$stmt->close();

$curso_id = $_GET['curso_id'] ?? null;
$materia_id = $_GET['materia_id'] ?? null;

// --- ALTA DE NUEVO TEMA ---
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_tema'])) {
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $contenido = trim($_POST['contenido'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $curso_id = $_POST['curso_id'];
    $materia_id = $_POST['materia_id'];

    if ($contenido && $curso_id && $materia_id) {
        // 1. Buscar o crear el libro de temas para ese curso, materia y profesor
        $libro_id = null;
        $sql_libro = "SELECT id FROM libros_temas WHERE curso_id=? AND materia_id=? AND profesor_id=?";
        $stmt_libro = $conexion->prepare($sql_libro);
        $stmt_libro->bind_param("iii", $curso_id, $materia_id, $profesor_id);
        $stmt_libro->execute();
        $stmt_libro->bind_result($libro_id_res);
        if ($stmt_libro->fetch()) {
            $libro_id = $libro_id_res;
        }
        $stmt_libro->close();
        if (!$libro_id) {
            $sql_new = "INSERT INTO libros_temas (curso_id, materia_id, profesor_id, anio_lectivo, estado) VALUES (?, ?, ?, YEAR(CURDATE()), 'activo')";
            $stmt_new = $conexion->prepare($sql_new);
            $stmt_new->bind_param("iii", $curso_id, $materia_id, $profesor_id);
            $stmt_new->execute();
            $libro_id = $conexion->insert_id;
            $stmt_new->close();
        }
        // 2. Insertar nuevo contenido
        $sql_insert = "INSERT INTO contenidos_libro (libro_id, fecha, contenido, observaciones, fecha_creacion) VALUES (?, ?, ?, ?, NOW())";
        $stmt_insert = $conexion->prepare($sql_insert);
        $stmt_insert->bind_param("isss", $libro_id, $fecha, $contenido, $observaciones);
        if ($stmt_insert->execute()) {
            $mensaje = '<div class="bg-green-100 text-green-700 rounded-xl p-3 mb-4">Tema guardado correctamente.</div>';
        } else {
            $mensaje = '<div class="bg-red-100 text-red-700 rounded-xl p-3 mb-4">Error al guardar el tema: ' . $stmt_insert->error . '</div>';
        }
        $stmt_insert->close();
    } else {
        $mensaje = '<div class="bg-yellow-100 text-yellow-800 rounded-xl p-3 mb-4">Completá el contenido del tema.</div>';
    }
}

// Mostrar los temas
$temas = [];
if ($curso_id && $materia_id) {
    $sql2 = "SELECT cl.id, cl.fecha, cl.contenido, cl.observaciones, cl.contenido
         FROM libros_temas lt
         JOIN contenidos_libro cl ON lt.id = cl.libro_id
         WHERE lt.curso_id = ? AND lt.materia_id = ? AND lt.profesor_id = ?
         ORDER BY cl.fecha DESC";
    $stmt2 = $conexion->prepare($sql2);
    $stmt2->bind_param("iii", $curso_id, $materia_id, $profesor_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row2 = $result2->fetch_assoc()) {
        $temas[] = $row2;
    }
    $stmt2->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Libro de Temas</title>
    <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
    <nav id="sidebar" class="w-60 transition-all duration-300 bg-white shadow-lg px-4 py-4 flex flex-col gap-2">
        <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400 h-28">
            <img src="../../images/et20ico.ico" class="sidebar-expanded block h-full w-auto object-contain">
            <img src="../../images/et20ico.ico" class="sidebar-collapsed hidden h-10 w-auto object-contain">
        </div>
        <!-- Bloque usuario/rol/salir ELIMINADO DEL SIDEBAR -->
        <a href="profesor.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Inicio">
            <span class="text-xl">🏠</span><span class="sidebar-label">Inicio</span>
        </a>
        <a href="libro_temas.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition" title="Libro de Temas">
            <span class="text-xl">📚</span><span class="sidebar-label">Libro de Temas</span>
        </a>
        <a href="calificaciones.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Calificaciones">
            <span class="text-xl">📝</span><span class="sidebar-label">Calificaciones</span>
        </a>
        <button onclick="window.location='../../includes/logout.php'" class="sidebar-item flex items-center justify-center gap-2 mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">
            <span class="text-xl">🚪</span><span class="sidebar-label">Salir</span>
        </button>
    </nav>

    <main class="flex-1 p-10">
        <!-- BLOQUE DE USUARIO, ROL Y SALIR A LA DERECHA -->
        <div class="w-full flex justify-end mb-6">
            <div class="flex items-center gap-3 bg-white rounded-xl px-5 py-2 shadow border">
                <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-12 h-12 object-cover">
                <div class="flex flex-col pr-2 text-right">
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['nombre']; ?></div>
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['apellido']; ?></div>
                    <div class="mt-1 text-xs text-gray-500">Profesor/a</div>
                </div>
                <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
                    <form method="post" action="../../includes/cambiar_rol.php" class="ml-4">
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

        <h1 class="text-2xl font-bold mb-6">📚 Libro de Temas</h1>
        <form class="mb-8 flex gap-4" method="get" id="form-filtros">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <select name="curso_id" class="px-4 py-2 rounded-xl border" required onchange="document.getElementById('form-filtros').submit()">
                <option value="">Seleccionar curso</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?php echo $c['curso_id']; ?>" <?php if ($curso_id == $c['curso_id']) echo "selected"; ?>>
                        <?php echo $c['anio'] . "°" . $c['division']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="materia_id" class="px-4 py-2 rounded-xl border" required>
                <option value="">Seleccionar materia</option>
                <?php foreach ($cursos as $c): ?>
                    <?php if ($curso_id == $c['curso_id']): ?>
                        <option value="<?php echo $c['materia_id']; ?>" <?php if ($materia_id == $c['materia_id']) echo "selected"; ?>>
                            <?php echo $c['materia']; ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Ver</button>
        </form>
        <?php echo $mensaje; ?>

        <?php if ($curso_id && $materia_id): ?>
            <div class="flex flex-col md:flex-row gap-6 mb-10 items-start">
                <!-- FORMULARIO AÑADIR TEMA -->
                <form method="post" class="bg-white rounded-xl shadow p-6 flex flex-col gap-3 w-full md:w-1/3">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                    <input type="hidden" name="materia_id" value="<?php echo $materia_id; ?>">
                    <input type="hidden" name="nuevo_tema" value="1">

                    <div>
                        <label class="font-semibold">Fecha:</label>
                        <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" class="px-4 py-2 border rounded-xl w-full" required>
                    </div>
                    <div>
                        <label class="font-semibold">Contenido del tema:</label>
                        <textarea name="contenido" rows="2" class="w-full px-4 py-2 border rounded-xl" required></textarea>
                    </div>
                    <div>
                        <label class="font-semibold">Observaciones:</label>
                        <input name="observaciones" type="text" class="w-full px-4 py-2 border rounded-xl" placeholder="Opcional">
                    </div>
                    <button type="submit" class="mt-2 px-6 py-2 rounded-xl bg-green-600 text-white hover:bg-green-700 font-bold">
                        + Agregar tema
                    </button>
                </form>

                <!-- LISTADO DE TEMAS -->
                <div class="w-full md:w-2/3 max-h-[400px] overflow-y-auto rounded-xl shadow border bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="sticky top-0 bg-white shadow z-10">
                            <tr>
                                <th class="py-2 px-4 text-left">ID</th>
                                <th class="py-2 px-4 text-left">Fecha</th>
                                <th class="py-2 px-4 text-left">Contenido</th>
                                <th class="py-2 px-4 text-left">Observaciones</th>
                                <th class="py-2 px-4 text-left">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($temas as $t): ?>
                                <tr class="border-t">
                                    <td class="py-2 px-4"><?php echo $t['id']; ?></td>
                                    <td class="py-2 px-4"><?php echo date("d/m/Y", strtotime($t['fecha'])); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($t['contenido']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($t['observaciones']); ?></td>
                                    <td class="py-2 px-4 flex gap-2">
                                        <!-- Editar -->
                                        <form method="post" action="editar_contenido.php" class="inline-block">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                            <button class="text-blue-600 hover:underline text-sm" type="submit">Editar</button>
                                        </form>
                                        <!-- Eliminar -->
                                        <form method="post" action="eliminar_contenido.php" class="inline-block" onsubmit="return confirm('¿Seguro que querés eliminar este contenido?');">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                            <button class="text-red-600 hover:underline text-sm" type="submit">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($temas)): ?>
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-gray-500">No hay temas cargados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="text-gray-500">Seleccioná un curso y una materia para ver el libro de temas.</div>
        <?php endif; ?>
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