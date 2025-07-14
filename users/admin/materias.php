<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once '../../includes/db.php';

// Materias
$materias = [];
$res = $conexion->query("SELECT id, nombre, codigo, categoria, es_contraturno, estado FROM materias ORDER BY nombre");
while ($m = $res->fetch_assoc()) {
    $materias[] = $m;
}

// Profesores
$profesores = [];
$res = $conexion->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = '3' ORDER BY apellido");
while ($p = $res->fetch_assoc()) {
    $profesores[] = $p;
}

// Cursos
$cursos = [];
$res = $conexion->query("SELECT id, anio, division FROM cursos ORDER BY anio, division");
while ($c = $res->fetch_assoc()) {
    $cursos[] = $c;
}

// Asignaciones
$asignaciones = [];
$profesor_id = $_GET['profesor_id'] ?? null;
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
        <a href="usuarios.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">👥 Usuarios</a>
        <a href="cursos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">🏫 Cursos</a>
        <a href="alumnos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">👤 Alumnos</a>
        <a href="materias.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">📚 Materias</a>
        <a href="horarios.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">⏰ Horarios</a>
        <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
            <form method="post" action="../../includes/cambiar_rol.php" class="mt-auto mb-3">
                <select name="rol" onchange="this.form.submit()" class="w-full px-3 py-2 border text-sm rounded-xl text-gray-700 bg-white">
                    <?php foreach ($_SESSION['roles_disponibles'] as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php if ($_SESSION['usuario']['rol'] == $r['id']) echo 'selected'; ?>>
                            Cambiar a: <?php echo ucfirst($r['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>

    <main class="flex-1 p-10">
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

            <!-- Card 2: Lista de materias -->
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
            <!-- Card 3: Asignar materias a profesores -->
            <div class="col-span-1 md:col-span-2 bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-semibold mb-4">👨‍🏫 Asignar materias a profesores</h2>

                <!-- Selección de profesor -->
                <form method="get" class="mb-6 flex gap-4 items-center">
                    <select name="profesor_id" class="px-4 py-2 rounded-xl border w-full md:w-1/3" required>
                        <option value="">Seleccionar profesor</option>
                        <?php foreach ($profesores as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php if ($profesor_id == $p['id']) echo "selected"; ?>>
                                <?php echo $p['apellido'] . ", " . $p['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="bg-indigo-600 text-white px-4 py-2 rounded-xl hover:bg-indigo-700">Ver asignaciones</button>
                </form>

                <?php if ($profesor_id): ?>
                    <!-- Asignar materia -->
                    <form method="post" action="./utils/admin_asignar_materia.php" class="flex flex-col md:flex-row gap-4 mb-6">
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
                            <option value="">Materia</option>
                            <?php foreach ($materias as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo $m['nombre']; ?></option>
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
                                        <form method="post" action="./utils/admin_eliminar_asignacion.php" onsubmit="return confirm('¿Eliminar esta asignación?');">
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

        </div> <!-- cierre grid -->
    </main>
</body>

</html>