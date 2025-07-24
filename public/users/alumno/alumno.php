<?php
session_start();
if (
    !isset($_SESSION['usuario']) ||
    !is_array($_SESSION['usuario']) ||
    (int)$_SESSION['usuario']['rol'] !== 4
) {
    header("Location: /login.php?error=rol");
    exit;
}

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

$usuario = $_SESSION['usuario'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Alumno</title>
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
    <button id="toggleSidebar" class="absolute top-4 left-4 z-50 text-2xl hover:text-indigo-600 transition">☰</button>
    <!-- Sidebar -->
    <nav id="sidebar" class="w-60 transition-all duration-300 bg-white shadow-lg px-4 py-4 flex flex-col gap-2">
        <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400 h-28">
            <img src="/images/et20ico.ico" class="sidebar-expanded block h-full w-auto object-contain">
            <img src="/images/et20ico.ico" class="sidebar-collapsed hidden h-10 w-auto object-contain">
        </div>
        <a href="alumno.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition" title="Inicio">
            <span class="text-xl">🏠</span><span class="sidebar-label">Inicio</span>
        </a>
        <a href="asistencias.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Asistencias">
            <span class="text-xl">📆</span><span class="sidebar-label">Asistencias</span>
        </a>
        <a href="notas.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Notas">
            <span class="text-xl">📝</span><span class="sidebar-label">Notas</span>
        </a>
        <button onclick="window.location='/includes/logout.php'" class="sidebar-item flex items-center justify-center gap-2 mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">
            <span class="text-xl">🚪</span><span class="sidebar-label">Salir</span>
        </button>
    </nav>

    <!-- Contenido principal -->
    <main class="flex-1 p-10">
        <!-- BLOQUE DE USUARIO, ROL Y SALIR A LA DERECHA -->
        <div class="w-full flex justify-end mb-6">
            <div class="flex items-center gap-3 bg-white rounded-xl px-5 py-2 shadow border">
                <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-12 h-12 object-cover">
                <div class="flex flex-col pr-2 text-right">
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['nombre']; ?></div>
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['apellido']; ?></div>
                    <div class="mt-1 text-xs text-gray-500">Alumno/a</div>
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

        <h1 class="text-3xl font-bold mb-4">¡Bienvenid@, <?php echo $usuario['nombre']; ?>!</h1>
        <div class="mt-4 text-lg text-gray-700">
            Desde tu panel podrás consultar tus <b>asistencias</b>, <b>notas</b> y recibir notificaciones escolares.
        </div>
        <div class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">📆</div>
                <h2 class="text-xl font-bold mb-2">Asistencias</h2>
                <p class="text-gray-500 text-center mb-4">Revisá tus asistencias diarias.</p>
                <a href="asistencias.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Ver Asistencias</a>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">📝</div>
                <h2 class="text-xl font-bold mb-2">Notas</h2>
                <p class="text-gray-500 text-center mb-4">Consultá todas tus calificaciones.</p>
                <a href="notas.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Ver Notas</a>
            </div>
        </div>
        <div class="mt-10">
            <div class="bg-yellow-100 p-4 rounded-xl text-yellow-900 text-center">
                📢 <b>Próximamente</b> podrás recibir notificaciones importantes directamente desde tu panel.
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