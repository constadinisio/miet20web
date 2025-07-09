<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 2) {
    header("Location: ../login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once '../../includes/db.php';

// Cursos asignados
$preceptor_id = $usuario['id'];
$cursos = [];
$sql = "SELECT c.id, c.anio, c.division
        FROM preceptor_curso pc
        JOIN cursos c ON pc.curso_id = c.id
        WHERE pc.preceptor_id = ? AND pc.estado = 'activo'
        ORDER BY c.anio, c.division";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $preceptor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cursos[] = $row;
}
$stmt->close();

$curso_id = $_GET['curso_id'] ?? null;
$fecha = $_GET['fecha'] ?? date('Y-m-d');

$asistencias = [];
if ($curso_id) {
    // Listar alumnos y sus asistencias de ese día
    $sql2 = "SELECT u.id, u.nombre, u.apellido, ag.estado
             FROM alumno_curso ac
             JOIN usuarios u ON ac.alumno_id = u.id
             LEFT JOIN asistencia_general ag ON ag.alumno_id = u.id AND ag.curso_id = ac.curso_id AND ag.fecha = ?
             WHERE ac.curso_id = ?
             ORDER BY u.apellido, u.nombre";
    $stmt2 = $conexion->prepare($sql2);
    $stmt2->bind_param("si", $fecha, $curso_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $asistencias[] = $row;
    }
    $stmt2->close();
}

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asistencias'])) {
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    foreach ($_POST['asistencias'] as $alumno_id => $estado) {
        // Buscar si ya existe
        $sql_check = "SELECT id FROM asistencia_general WHERE alumno_id=? AND curso_id=? AND fecha=?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("iis", $alumno_id, $curso_id, $fecha);
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
            $sql_ins = "INSERT INTO asistencia_general (alumno_id, curso_id, fecha, estado, creado_por) VALUES (?, ?, ?, ?, ?)";
            $stmt_ins = $conexion->prepare($sql_ins);
            $stmt_ins->bind_param("iissi", $alumno_id, $curso_id, $fecha, $estado, $preceptor_id);
            $stmt_ins->execute();
            $stmt_ins->close();
        }
        $stmt_check->close();
    }
    header("Location: preceptor_asistencias.php?curso_id=$curso_id&fecha=$fecha&msg=ok");
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
        <div class="flex items-center gap-3 mb-10">
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div>
                <div class="font-bold text-lg"><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></div>
                <div class="text-xs text-gray-500">Preceptor/a</div>
            </div>
        </div>
        <a href="preceptor.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="asistencias.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">📆 Asistencias</a>
        <a href="calificaciones.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📝 Calificaciones</a>
        <a href="boletines.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📑 Boletines</a>
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
            <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Ver</button>
        </form>
        <?php if ($curso_id): ?>
            <form method="post">
                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                <input type="hidden" name="fecha" value="<?php echo $fecha; ?>">
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-xl shadow">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 text-left">Alumno</th>
                                <th class="py-2 px-4 text-left">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asistencias as $a): ?>
                                <tr>
                                    <td class="py-2 px-4"><?php echo $a['apellido'] . " " . $a['nombre']; ?></td>
                                    <td class="py-2 px-4">
                                        <select name="asistencias[<?php echo $a['id']; ?>]" class="border rounded px-2 py-1">
                                            <option value="P" <?php if ($a['estado'] == 'P') echo "selected"; ?>>Presente</option>
                                            <option value="A" <?php if ($a['estado'] == 'A') echo "selected"; ?>>Ausente</option>
                                            <option value="T" <?php if ($a['estado'] == 'T') echo "selected"; ?>>Tarde</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($asistencias)): ?>
                                <tr>
                                    <td colspan="2" class="py-4 text-center text-gray-500">No hay alumnos cargados.</td>
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
            <div class="text-gray-500">Seleccioná un curso para gestionar asistencias.</div>
        <?php endif; ?>
    </main>
</body>

</html>