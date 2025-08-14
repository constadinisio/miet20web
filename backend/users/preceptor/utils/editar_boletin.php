<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 2) {
    header("Location: /login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once __DIR__ . '/../../../includes/db.php';

$curso_id = $_GET['curso_id'] ?? null;
$alumno_id = $_GET['alumno_id'] ?? null;
$nuevo = isset($_GET['nuevo']);
$boletin_id = $_GET['boletin_id'] ?? null;

// Traer datos del alumno y curso
$sql = "SELECT u.nombre, u.apellido, u.dni, c.anio, c.division
        FROM usuarios u, cursos c, alumno_curso ac
        WHERE u.id=? AND ac.curso_id=c.id AND ac.alumno_id=u.id AND c.id=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $alumno_id, $curso_id);
$stmt->execute();
$stmt->bind_result($nombre, $apellido, $dni, $anio, $division);
$stmt->fetch();
$stmt->close();

// CREAR NUEVO BOLETÍN
if ($nuevo && $curso_id && $alumno_id) {
    // Año y periodo por defecto
    $anio_lectivo = date('Y');
    $periodo = 1;
    // Crear boletín
    $sql = "INSERT INTO boletin (alumno_id, curso_id, anio_lectivo, periodo, estado) VALUES (?, ?, ?, ?, 'borrador')";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiii", $alumno_id, $curso_id, $anio_lectivo, $periodo);
    $stmt->execute();
    $boletin_id = $conexion->insert_id;
    $stmt->close();

    // Traer datos del alumno y curso (usa JOIN, más seguro y legible)
    $sql = "SELECT u.nombre, u.apellido, u.dni, c.anio, c.division
        FROM alumno_curso ac
        JOIN usuarios u ON ac.alumno_id = u.id
        JOIN cursos c ON ac.curso_id = c.id
        WHERE u.id = ? AND c.id = ?";
    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conexion->error);
    }

    $stmt->bind_param("ii", $alumno_id, $curso_id);
    $stmt->execute();
    $stmt->bind_result($nombre, $apellido, $dni, $anio, $division);
    $stmt->fetch();
    $stmt->close();

    // Crear calificaciones iniciales
    foreach ($materias as $m) {
        $sql = "INSERT INTO calificacion_boletin (boletin_id, materia_id) VALUES (?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ii", $boletin_id, $m['id']);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: editar_boletin.php?curso_id=$curso_id&alumno_id=$alumno_id&boletin_id=$boletin_id");
    exit;
}

// GUARDAR EDICIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $boletin_id) {
    $observaciones = $_POST['observaciones'] ?? '';
    // Update boletin
    $sql = "UPDATE boletin SET observaciones=? WHERE id=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $observaciones, $boletin_id);
    $stmt->execute();
    $stmt->close();

    // Update calificaciones
    foreach ($_POST['notas'] as $calif_id => $nota) {
        $nota_num = $nota['numerica'] ?? null;
        $nota_conc = $nota['conceptual'] ?? '';
        $obs_mat = $nota['observaciones'] ?? '';
        $sql = "UPDATE calificacion_boletin SET nota_numerica=?, nota_conceptual=?, observaciones=? WHERE id=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("issi", $nota_num, $nota_conc, $obs_mat, $calif_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: editar_boletin.php?curso_id=$curso_id&alumno_id=$alumno_id&boletin_id=$boletin_id&guardado=1");
    exit;
}

// TRAER BOLETÍN Y CALIFICACIONES
$boletin = null;
$calificaciones = [];
if ($boletin_id) {
    $sql = "SELECT * FROM boletin WHERE id=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $boletin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $boletin = $result->fetch_assoc();
    $stmt->close();

    $sql = "SELECT cb.id, m.nombre, cb.nota_numerica, cb.nota_conceptual, cb.observaciones
            FROM calificacion_boletin cb
            JOIN materias m ON cb.materia_id = m.id
            WHERE cb.boletin_id=? ORDER BY m.nombre";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $boletin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $calificaciones[] = $row;
    }
    $stmt->close();
}

$guardado = $_GET['guardado'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Boletín</title>
    <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex">
    <nav class="w-60 bg-white shadow-lg px-6 py-8 flex flex-col gap-2">
        <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400">
            <img src="/images/et20ico.ico" class="block items-center h-28 w-28">
        </div>
        <div class="flex items-center mb-10 gap-2">
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div class="flex flex-col pl-3">
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['nombre']; ?></div>
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['apellido']; ?></div>
                <div class="mt-2 text-xs text-gray-500">Preceptor/a</div>
            </div>
        </div>
        <a href="/users/preceptor/preceptor.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="/users/preceptor/asistencias.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📆 Asistencias</a>
        <a href="/users/preceptor/calificaciones.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📝 Calificaciones</a>
        <a href="/users/preceptor/boletines.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">📑 Boletines</a>
        <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
            <form method="post" action="/includes/cambiar_rol.php" class="mt-auto mb-3">
                <select name="rol" onchange="this.form.submit()" class="w-full px-3 py-2 border text-sm rounded-xl text-gray-700 bg-white">
                    <?php foreach ($_SESSION['roles_disponibles'] as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php if ($_SESSION['usuario']['rol'] == $r['id']) echo 'selected'; ?>>
                            Cambiar a: <?php echo ucfirst($r['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <button onclick="window.location='/includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <main class="flex-1 p-10 max-w-4xl">
        <h1 class="text-2xl font-bold mb-4">Boletín de <?php echo "$apellido, $nombre"; ?></h1>
        <div class="mb-4">
            <?php echo "{$anio}°{$division}"; ?> |
            <b>DNI:</b> <?php echo $dni; ?> |
            <b>Año lectivo:</b> <?php echo $boletin['anio_lectivo'] ?? date('Y'); ?> |
            <b>Periodo:</b> <?php echo $boletin['periodo'] ?? 1; ?> |
            <b>Estado:</b> <?php echo ucfirst($boletin['estado'] ?? 'borrador'); ?>
        </div>
        <?php if ($guardado): ?>
            <div class="bg-green-100 text-green-800 rounded-xl p-3 mb-4">Boletín guardado.</div>
        <?php endif; ?>

        <?php if ($boletin && $boletin['estado'] == 'borrador'): ?>
            <form method="post">
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full bg-white rounded-xl shadow">
                        <thead>
                            <tr>
                                <th class="py-2 px-4">Materia</th>
                                <th class="py-2 px-4">Nota numérica</th>
                                <th class="py-2 px-4">Nota conceptual</th>
                                <th class="py-2 px-4">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calificaciones as $c): ?>
                                <tr>
                                    <td class="py-2 px-4"><?php echo $c['nombre']; ?></td>
                                    <td class="py-2 px-4"><input type="number" min="1" max="10" name="notas[<?php echo $c['id']; ?>][numerica]" value="<?php echo $c['nota_numerica']; ?>" class="w-16 border rounded px-2"></td>
                                    <td class="py-2 px-4"><input type="text" name="notas[<?php echo $c['id']; ?>][conceptual]" value="<?php echo htmlspecialchars($c['nota_conceptual']); ?>" class="w-24 border rounded px-2"></td>
                                    <td class="py-2 px-4"><input type="text" name="notas[<?php echo $c['id']; ?>][observaciones]" value="<?php echo htmlspecialchars($c['observaciones']); ?>" class="w-40 border rounded px-2"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mb-4">
                    <label class="font-bold">Observaciones generales:</label><br>
                    <textarea name="observaciones" rows="3" class="w-full border rounded px-2 py-1"><?php echo htmlspecialchars($boletin['observaciones'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl hover:bg-indigo-700 font-bold">Guardar cambios</button>
                <a href="/users/preceptor/boletines.php?curso_id=<?php echo $curso_id; ?>&alumno_id=<?php echo $alumno_id; ?>" class="ml-4 text-gray-600 hover:underline">Volver</a>
            </form>
        <?php elseif ($boletin): ?>
            <!-- Boletín en modo solo lectura -->
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full bg-white rounded-xl shadow">
                    <thead>
                        <tr>
                            <th class="py-2 px-4">Materia</th>
                            <th class="py-2 px-4">Nota numérica</th>
                            <th class="py-2 px-4">Nota conceptual</th>
                            <th class="py-2 px-4">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calificaciones as $c): ?>
                            <tr>
                                <td class="py-2 px-4"><?php echo $c['nombre']; ?></td>
                                <td class="py-2 px-4"><?php echo $c['nota_numerica']; ?></td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($c['nota_conceptual']); ?></td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($c['observaciones']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mb-4">
                <b>Observaciones generales:</b><br>
                <div class="border rounded p-3 bg-gray-50"><?php echo nl2br(htmlspecialchars($boletin['observaciones'] ?? '')); ?></div>
            </div>
            <a href="/users/preceptor/boletines.php?curso_id=<?php echo $curso_id; ?>&alumno_id=<?php echo $alumno_id; ?>" class="text-gray-600 hover:underline">Volver</a>
        <?php endif; ?>
    </main>
</body>

</html>