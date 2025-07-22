<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: /login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once __DIR__ . '/../../../backend/includes/db.php';

$busqueda = $_GET['busqueda'] ?? '';
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$resultados_por_pagina = 10;
$offset = ($pagina - 1) * $resultados_por_pagina;

$sql_base = "FROM usuarios WHERE rol = 4";
$params = [];
$tipos = "";

if ($busqueda !== '') {
    $sql_base .= " AND (nombre LIKE ? OR apellido LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $tipos .= "ss";
}

$stmt_count = $conexion->prepare("SELECT COUNT(*) $sql_base");
if ($tipos) $stmt_count->bind_param($tipos, ...$params);
$stmt_count->execute();
$stmt_count->bind_result($total_resultados);
$stmt_count->fetch();
$stmt_count->close();

$total_paginas = ceil($total_resultados / $resultados_por_pagina);

$sql_datos = "SELECT id, nombre, apellido, dni, mail, anio, division $sql_base ORDER BY apellido, nombre LIMIT ? OFFSET ?";
$stmt = $conexion->prepare($sql_datos);
if ($tipos) {
    $tipos .= "ii";
    $params[] = $resultados_por_pagina;
    $params[] = $offset;
    $stmt->bind_param($tipos, ...$params);
} else {
    $stmt->bind_param("ii", $resultados_por_pagina, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$alumnos = [];
while ($row = $result->fetch_assoc()) {
    $alumnos[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Alumnos</title>
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
    <!-- Navbar -->
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
        <a href="cursos.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Cursos">
            <span class="text-xl">🏫</span><span class="sidebar-label">Cursos</span>
        </a>
        <a href="alumnos.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100 transition" title="Alumnos">
            <span class="text-xl">👤</span><span class="sidebar-label">Alumnos</span>
        </a>
        <a href="materias.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Materias">
            <span class="text-xl">📚</span><span class="sidebar-label">Materias</span>
        </a>
        <a href="horarios.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Horarios">
            <span class="text-xl">⏰</span><span class="sidebar-label">Horarios</span>
        </a>
        <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
            <form method="post" action="/../../../backend/includes/cambiar_rol.php" class="mt-auto mb-3 sidebar-label">
                <select name="rol" onchange="this.form.submit()" class="w-full px-3 py-2 border text-sm rounded-xl text-gray-700 bg-white">
                    <?php foreach ($_SESSION['roles_disponibles'] as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php if ($_SESSION['usuario']['rol'] == $r['id']) echo 'selected'; ?>>
                            Cambiar a: <?php echo ucfirst($r['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <button onclick="window.location='/../../../backend/includes/logout.php'" class="sidebar-item flex items-center justify-center gap-2 mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">
            <span class="text-xl">🚪</span><span class="sidebar-label">Salir</span>
        </button>
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
                                    <a href="editar_alumno.php?id=<?php echo $a['id']; ?>" class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">🩹 Editar</a>
                                    <a href="eliminar_alumno.php?id=<?= $alumno['id'] ?>" class="bg-red-600 hover:bg-red-700 text-white py-1 px-3 rounded text-sm" onclick="return confirm('¿Estás seguro de que querés eliminar este alumno?')">🗑️ Eliminar</a>
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
                <?php if ($total_paginas > 1): ?>
                    <div class="mt-6 flex flex-wrap gap-2 justify-center">
                        <?php
                        $rango = 2; // Páginas antes y después de la actual
                        $mostrar_inicio = max(1, $pagina - $rango);
                        $mostrar_fin = min($total_paginas, $pagina + $rango);

                        // Mostrar primer página siempre
                        if ($mostrar_inicio > 1) {
                            echo '<a href="?pagina=1' . ($busqueda ? '&busqueda=' . urlencode($busqueda) : '') . '" class="px-3 py-1 rounded-xl border bg-white text-gray-700 hover:bg-gray-100">1</a>';
                            if ($mostrar_inicio > 2) echo '<span class="px-2 py-1 text-gray-400">...</span>';
                        }

                        // Páginas del rango actual
                        for ($i = $mostrar_inicio; $i <= $mostrar_fin; $i++): ?>
                            <a href="?pagina=<?php echo $i; ?><?php echo $busqueda ? '&busqueda=' . urlencode($busqueda) : ''; ?>"
                                class="px-3 py-1 rounded-xl border <?php echo $i === $pagina ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor;

                        // Mostrar última página siempre
                        if ($mostrar_fin < $total_paginas) {
                            if ($mostrar_fin < $total_paginas - 1) echo '<span class="px-2 py-1 text-gray-400">...</span>';
                            echo '<a href="?pagina=' . $total_paginas . ($busqueda ? '&busqueda=' . urlencode($busqueda) : '') . '" class="px-3 py-1 rounded-xl border bg-white text-gray-700 hover:bg-gray-100">' . $total_paginas . '</a>';
                        }
                        ?>
                    </div>
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
</body>

</html>