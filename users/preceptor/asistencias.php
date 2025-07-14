<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 2) {
    header("Location: ../../login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once '../../includes/db.php';

// Todos los cursos del sistema
$cursos = [];
$sql = "SELECT id, anio, division FROM cursos ORDER BY anio, division";
$result = $conexion->query($sql);
while ($row = $result->fetch_assoc()) {
    $cursos[] = $row;
}

$curso_id = $_GET['curso_id'] ?? null;
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$modo = $_GET['modo'] ?? 'ver';

// Traer alumnos y asistencias (turno y contraturno)
$alumnos = [];
if ($curso_id) {
    // Listar alumnos del curso
    $sql2 = "SELECT u.id, u.nombre, u.apellido
             FROM alumno_curso ac
             JOIN usuarios u ON ac.alumno_id = u.id
             WHERE ac.curso_id = ?
             ORDER BY u.apellido, u.nombre";
    $stmt2 = $conexion->prepare($sql2);
    $stmt2->bind_param("i", $curso_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $alumnos[$row['id']] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'apellido' => $row['apellido'],
            'turno' => '-',         // Por defecto
            'contraturno' => '-',   // Por defecto
        ];
    }
    $stmt2->close();

    // Traer asistencias de ese día (para turno y contraturno)
    $sql3 = "SELECT alumno_id, estado, es_contraturno
             FROM asistencia_general
             WHERE curso_id = ? AND fecha = ?";
    $stmt3 = $conexion->prepare($sql3);
    $stmt3->bind_param("is", $curso_id, $fecha);
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    while ($row = $result3->fetch_assoc()) {
        if (isset($alumnos[$row['alumno_id']])) {
            if ($row['es_contraturno']) {
                $alumnos[$row['alumno_id']]['contraturno'] = $row['estado'];
            } else {
                $alumnos[$row['alumno_id']]['turno'] = $row['estado'];
            }
        }
    }
    $stmt3->close();
}

// Guardar cambios (solo si está en modo edición)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asistencias'])) {
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $preceptor_id = $usuario['id'];
    foreach ($_POST['asistencias'] as $alumno_id => $asist) {
        // Procesar ambos: turno y contraturno
        foreach (['turno' => 0, 'contraturno' => 1] as $tipo => $es_contraturno) {
            $estado = $asist[$tipo] ?? '';
            if (!$estado) continue; // Si no se envió, saltar

            // Buscar si ya existe
            $sql_check = "SELECT id FROM asistencia_general WHERE alumno_id=? AND curso_id=? AND fecha=? AND es_contraturno=?";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->bind_param("iisi", $alumno_id, $curso_id, $fecha, $es_contraturno);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                // Update
                $stmt_check->bind_result($asist_id);
                $stmt_check->fetch();
                $sql_upd = "UPDATE asistencia_general SET estado=? WHERE id=?";
                $stmt_upd = $conexion->prepare($sql_upd);
                $stmt_upd->bind_param("si", $estado, $asist_id);
                $stmt_upd->execute();
                $stmt_upd->close();
            } else {
                // Insert
                $sql_ins = "INSERT INTO asistencia_general (alumno_id, curso_id, fecha, estado, creado_por, es_contraturno)
                            VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_ins = $conexion->prepare($sql_ins);
                $stmt_ins->bind_param("iissii", $alumno_id, $curso_id, $fecha, $estado, $preceptor_id, $es_contraturno);
                $stmt_ins->execute();
                $stmt_ins->close();
            }
            $stmt_check->close();
        }
    }
    header("Location: asistencias.php?curso_id=$curso_id&fecha=$fecha&modo=editar&msg=ok");
    exit;
}
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Asistencias | Preceptor</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
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
            <img src="../../images/et20ico.ico" class="block items-center h-28 w-28">
        </div>
        <div class="flex items-center mb-10 gap-2">
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div class="flex flex-col pl-3">
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['nombre']; ?></div>
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['apellido']; ?></div>
                <div class="mt-2 text-xs text-gray-500">Preceptor/a</div>
            </div>
        </div>
        <a href="preceptor.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="asistencias.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">📆 Asistencias</a>
        <a href="calificaciones.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📝 Calificaciones</a>
        <a href="boletines.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📑 Boletines</a>
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
        <h1 class="text-2xl font-bold mb-6">📆 Gestión de Asistencias</h1>
        <?php if ($msg == 'ok'): ?>
            <div class="bg-green-100 text-green-700 rounded-xl p-3 mb-4">Asistencias guardadas correctamente.</div>
        <?php endif; ?>
        <form class="mb-8 flex gap-4" method="get">
            <select name="curso_id" class="px-4 py-2 rounded-xl border" required>
                <option value="">Seleccionar curso</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php if ($curso_id == $c['id']) echo "selected"; ?>>
                        <?php echo $c['anio'] . "°" . $c['division']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="fecha" value="<?php echo $fecha; ?>" class="px-4 py-2 rounded-xl border">
            <select name="modo" class="px-4 py-2 rounded-xl border">
                <option value="ver" <?php if ($modo == 'ver') echo 'selected'; ?>>Ver</option>
                <option value="editar" <?php if ($modo == 'editar') echo 'selected'; ?>>Editar</option>
            </select>
            <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Ver</button>
        </form>
        <?php if ($curso_id): ?>
            <?php if ($modo == 'editar'): ?>
                <form method="post" class="min-w-full">
                    <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                    <input type="hidden" name="fecha" value="<?php echo $fecha; ?>">
                    <div class="overflow-y-auto rounded-xl shadow bg-white" style="max-height: 500px;">
                        <table class="min-w-full bg-white rounded-xl shadow">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 text-left">Alumno</th>
                                    <th class="py-2 px-4 text-left">Turno</th>
                                    <th class="py-2 px-4 text-left">Contraturno</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alumnos as $a): ?>
                                    <tr>
                                        <td class="py-2 px-4"><?php echo $a['apellido'] . " " . $a['nombre']; ?></td>
                                        <td class="py-2 px-4">
                                            <select name="asistencias[<?php echo $a['id']; ?>][turno]" class="border rounded px-2 py-1">
                                                <option value="">-</option>
                                                <option value="P" <?php if ($a['turno'] == 'P') echo "selected"; ?>>Presente</option>
                                                <option value="A" <?php if ($a['turno'] == 'A') echo "selected"; ?>>Ausente</option>
                                                <option value="T" <?php if ($a['turno'] == 'T') echo "selected"; ?>>Tarde</option>
                                            </select>
                                        </td>
                                        <td class="py-2 px-4">
                                            <select name="asistencias[<?php echo $a['id']; ?>][contraturno]" class="border rounded px-2 py-1">
                                                <option value="">-</option>
                                                <option value="P" <?php if ($a['contraturno'] == 'P') echo "selected"; ?>>Presente</option>
                                                <option value="A" <?php if ($a['contraturno'] == 'A') echo "selected"; ?>>Ausente</option>
                                                <option value="T" <?php if ($a['contraturno'] == 'T') echo "selected"; ?>>Tarde</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($alumnos)): ?>
                                    <tr>
                                        <td colspan="3" class="py-4 text-center text-gray-500">No hay alumnos cargados.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="mt-4 px-6 py-2 rounded-xl bg-green-600 text-white hover:bg-green-700 font-bold">
                        Guardar asistencias
                    </button>
                </form>
            <?php else: ?>
                <!-- MODO VISUALIZACIÓN -->
                <div class="overflow-y-auto rounded-xl shadow bg-white" style="max-height: 500px;">
                    <table class="min-w-full bg-white rounded-xl shadow">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 text-left">Alumno</th>
                                <th class="py-2 px-4 text-left">Turno</th>
                                <th class="py-2 px-4 text-left">Contraturno</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alumnos as $a): ?>
                                <tr>
                                    <td class="py-2 px-4"><?php echo $a['apellido'] . " " . $a['nombre']; ?></td>
                                    <td class="py-2 px-4">
                                        <?php
                                        if ($a['turno'] == 'P') echo 'Presente';
                                        elseif ($a['turno'] == 'A') echo 'Ausente';
                                        elseif ($a['turno'] == 'T') echo 'Tarde';
                                        else echo '-';
                                        ?>
                                    </td>
                                    <td class="py-2 px-4">
                                        <?php
                                        if ($a['contraturno'] == 'P') echo 'Presente';
                                        elseif ($a['contraturno'] == 'A') echo 'Ausente';
                                        elseif ($a['contraturno'] == 'T') echo 'Tarde';
                                        else echo '-';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($alumnos)): ?>
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-gray-500">No hay alumnos cargados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-gray-500">Seleccioná un curso para gestionar asistencias.</div>
        <?php endif; ?>
    </main>
</body>

</html>