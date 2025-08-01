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

// Cargar cursos y materias del profesor
$cursosMaterias = [];
$stmt = $conexion->prepare("
    SELECT DISTINCT c.id AS curso_id, CONCAT(c.anio, '°', c.division) AS curso_nombre,
           m.id AS materia_id, m.nombre AS materia_nombre
    FROM horarios_materia h
    JOIN cursos c ON c.id = h.curso_id
    JOIN materias m ON m.id = h.materia_id
    WHERE h.profesor_id = ?
    ORDER BY c.anio, c.division, m.nombre
");
$stmt->bind_param("i", $usuario['id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $cursosMaterias[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Profesor</title>
    <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
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
        <a href="libro_temas.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Libro de Temas">
            <span class="text-xl">📚</span><span class="sidebar-label">Libro de Temas</span>
        </a>
        <a href="calificaciones.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Calificaciones">
            <span class="text-xl">📝</span><span class="sidebar-label">Calificaciones</span>
        </a>
        <a href="asistencias.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition" title="Asistencias P/ Materia">
            <span class="text-xl">👋</span><span class="sidebar-label">Asistencias P/ Materia</span>
        </a>
        <a href="trabajos.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Calificaciones">
            <span class="text-xl">📎</span><span class="sidebar-label">TPs y Actividades</span>
        </a>
        <a href="notificaciones.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Panel de Notificaciones">
            <span class="text-xl">🔔</span><span class="sidebar-label">Panel de Notificaciones</span>
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

        <!-- Sistema de Asistencia por Materia -->
        <div class="bg-white p-6 rounded-2xl shadow-xl max-w-7xl mx-auto">
            <h2 class="text-2xl font-bold mb-4">🗓️ Asistencias por materia</h2>

            <form id="form-asistencias" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 items-center">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <label class="font-semibold">Curso y Materia:</label>
                <select id="seleccion" name="seleccion" required class="border rounded p-2 col-span-3">
                    <option value="">Seleccionar...</option>
                    <?php foreach ($cursosMaterias as $cm): ?>
                        <option value="<?= $cm['curso_id'] ?>_<?= $cm['materia_id'] ?>">
                            <?= $cm['curso_nombre'] ?> - <?= $cm['materia_nombre'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label class="font-semibold">Fecha base:</label>
                <input type="date" id="fecha" name="fecha" class="border rounded p-2 col-span-3" value="<?= date('Y-m-d') ?>">
            </form>
            <div id="contenedor-tabla" class="overflow-x-auto hidden">
                <table id="tabla-asistencias" class="min-w-full bg-white border border-gray-300 text-sm">
                    <thead class="bg-indigo-100 text-indigo-900 font-bold text-center"></thead>
                    <tbody></tbody>
                </table>
                <div class="flex gap-4 mt-4">
                    <button id="btn-guardar" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl">
                        Guardar asistencias
                    </button>
                    <button id="btn-importar" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl">
                        Importar del preceptor
                    </button>
                </div>
            </div>

            <div id="mensaje" class="mt-4 text-center font-medium hidden"></div>
        </div>
    </main>
    <script>
        document.getElementById('seleccion').addEventListener('change', cargarAsistencias);
        document.getElementById('fecha').addEventListener('change', cargarAsistencias);

        function cargarAsistencias() {
            const seleccion = document.getElementById('seleccion').value;
            const fecha = document.getElementById('fecha').value;
            if (!seleccion || !fecha) return;

            const [curso_id, materia_id] = seleccion.split('_');
            fetch(`obtener_asistencias_materia.php?curso_id=${curso_id}&materia_id=${materia_id}&fecha=${fecha}`)
                .then(res => res.json())
                .then(data => {
                    const tabla = document.getElementById('tabla-asistencias');
                    const thead = tabla.querySelector('thead');
                    const tbody = tabla.querySelector('tbody');
                    thead.innerHTML = '';
                    tbody.innerHTML = '';

                    const trHead = document.createElement('tr');
                    data.columnas.forEach(col => {
                        const th = document.createElement('th');
                        th.className = 'border px-2 py-1';
                        th.textContent = col;
                        trHead.appendChild(th);
                    });
                    thead.appendChild(trHead);

                    data.filas.forEach(fila => {
                        const tr = document.createElement('tr');
                        fila.forEach((valor, i) => {
                            const td = document.createElement('td');
                            td.className = 'border px-2 py-1 text-center';
                            if (i >= 2) {
                                const sel = document.createElement('select');
                                ['NC', 'P', 'A', 'T', 'AP'].forEach(est => {
                                    const opt = document.createElement('option');
                                    opt.value = est;
                                    opt.textContent = est;
                                    if (est === valor) opt.selected = true;
                                    sel.appendChild(opt);
                                });
                                td.appendChild(sel);
                            } else {
                                td.textContent = valor;
                            }
                            tr.appendChild(td);
                        });
                        tbody.appendChild(tr);
                    });

                    document.getElementById('contenedor-tabla').classList.remove('hidden');
                });
        }

        document.getElementById('btn-guardar').addEventListener('click', () => {
            const seleccion = document.getElementById('seleccion').value;
            const fecha = document.getElementById('fecha').value;
            const csrf = document.querySelector('input[name="csrf"]').value;
            const [curso_id, materia_id] = seleccion.split('_');

            const tabla = document.getElementById('tabla-asistencias');
            const encabezados = Array.from(tabla.querySelectorAll('thead th')).slice(2);
            const filas = tabla.querySelectorAll('tbody tr');

            const asistencias = Array.from(filas).map(tr => {
                const nro = tr.children[0].textContent;
                const datos = Array.from(tr.querySelectorAll('td select')).map(s => s.value);
                return {
                    nro,
                    estados: datos
                };
            });

            fetch('guardar_asistencias_materia.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        csrf,
                        curso_id,
                        materia_id,
                        fecha,
                        encabezados,
                        asistencias
                    })
                })
                .then(res => res.json())
                .then(data => {
                    const mensaje = document.getElementById('mensaje');
                    mensaje.textContent = data.mensaje;
                    mensaje.className = "mt-4 text-center font-medium " + (data.ok ? 'text-green-600' : 'text-red-600');
                    mensaje.classList.remove('hidden');
                    setTimeout(() => mensaje.classList.add('hidden'), 5000);
                });
        });

        document.getElementById('btn-importar').addEventListener('click', () => {
            const seleccion = document.getElementById('seleccion').value;
            const fecha = document.getElementById('fecha').value;
            const csrf = document.querySelector('input[name="csrf"]').value;
            if (!seleccion || !fecha) return alert("Seleccioná curso, materia y fecha.");

            const [curso_id, materia_id] = seleccion.split('_');

            fetch('importar_asistencia_general.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        csrf,
                        curso_id,
                        materia_id,
                        fecha
                    })
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.mensaje);
                    if (data.ok) cargarAsistencias(); // refresca la tabla si se importó
                });
        });
    </script>
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
    <!-- Modal de ficha censal -->
    <div id="modalFichaCensal"
        class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 <?= $mostrar_modal ? '' : 'hidden' ?>">
        <form id="fichaCensalForm"
            class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md space-y-5"
            method="POST"
            autocomplete="off"
            style="min-width:300px">
            <h2 class="text-2xl font-bold text-center mb-3 text-blue-700">Completar ficha censal</h2>
            <p class="mb-2 text-gray-700 text-center">Para continuar, ingresá tu número de ficha censal:</p>
            <input type="text" id="ficha_censal" name="ficha_censal" required
                class="w-full border rounded-xl p-2" maxlength="30" autofocus>
            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-xl transition mt-2">
                Guardar
            </button>
            <p id="errorFichaCensal" class="text-red-600 text-center text-sm mt-2 hidden"></p>
        </form>
    </div>
</body>

</html>