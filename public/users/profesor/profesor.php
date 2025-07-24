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
$usuario = $_SESSION['usuario'];
require_once __DIR__ . '/../../../backend/includes/db.php';

$mostrar_modal = ($usuario['rol'] != 0 && $usuario['rol'] != 4 && empty($usuario['ficha_censal']));

// Buscar cursos asignados al profesor
$profesor_id = $usuario['id'];
$cursos = [];

$sql = "SELECT c.id, c.anio, c.division, m.nombre AS materia
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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Profesor</title>
    <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .sidebar-item { min-height: 3.5rem; width: 100%; }
        .w-16 .sidebar-item { justify-content: center !important; }
        .w-16 .sidebar-item span.sidebar-label { display: none; }
        .w-16 .sidebar-item span.text-xl { margin: 0 auto; }
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
        <a href="profesor.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition" title="Inicio">
            <span class="text-xl">🏠</span><span class="sidebar-label">Inicio</span>
        </a>
        <a href="libro_temas.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Libro de Temas">
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
            </div>
        </div>

        <h1 class="text-3xl font-bold mb-4">¡Bienvenido/a, <?php echo $usuario['nombre']; ?>!</h1>
        <div class="mt-4 text-lg text-gray-700">
            Desde tu panel podés cargar <b>temas vistos</b>, <b>calificaciones</b> y más.
        </div>
        <div class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">📚</div>
                <h2 class="text-xl font-bold mb-2">Libro de Temas</h2>
                <p class="text-gray-500 text-center mb-4">Registrá o consultá los temas dados en cada clase.</p>
                <a href="profesor_libro_temas.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Ir al Libro</a>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">📝</div>
                <h2 class="text-xl font-bold mb-2">Calificaciones</h2>
                <p class="text-gray-500 text-center mb-4">Subí las notas de tus alumnos.</p>
                <a href="profesor_calificaciones.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Ir a Calificaciones</a>
            </div>
        </div>
        <div class="mt-10">
            <div class="bg-yellow-100 p-4 rounded-xl text-yellow-900 text-center">
                📢 <b>Próximamente</b> notificaciones y avisos importantes.
            </div>
        </div>
        <div class="mt-8">
            <h2 class="font-bold mb-2">Tus Cursos y Materias:</h2>
            <ul class="bg-white rounded-xl p-4 shadow grid grid-cols-1 gap-2">
                <?php foreach ($cursos as $curso): ?>
                    <li>
                        <span class="font-semibold"><?php echo $curso['anio'] . "°" . $curso['division']; ?></span> — <span><?php echo $curso['materia']; ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($cursos)): ?>
                    <li class="text-gray-500">No tenés cursos asignados.</li>
                <?php endif; ?>
            </ul>
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
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalFichaCensal');
            const form = document.getElementById('fichaCensalForm');
            const errorMsg = document.getElementById('errorFichaCensal');

            if (modal && form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    errorMsg.classList.add('hidden');
                    const ficha = form.ficha_censal.value.trim();
                    if (!ficha) {
                        errorMsg.textContent = "El campo ficha censal es obligatorio.";
                        errorMsg.classList.remove('hidden');
                        return;
                    }

                    // Enviar AJAX
                    fetch('../../includes/guardar_ficha_censal.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'ficha_censal=' + encodeURIComponent(ficha)
                        })
                        .then(res => res.text())
                        .then(res => {
                            if (res.trim() === 'OK') {
                                modal.classList.add('hidden');
                                location.reload();
                            } else {
                                errorMsg.textContent = res;
                                errorMsg.classList.remove('hidden');
                            }
                        })
                        .catch(() => {
                            errorMsg.textContent = "Hubo un error. Intentá de nuevo.";
                            errorMsg.classList.remove('hidden');
                        });
                });
            }
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