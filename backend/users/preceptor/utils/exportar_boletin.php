<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] < 1) {
    header("Location: /login.php?error=rol");
    exit;
}
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$boletin_id = $_GET['id'] ?? null;
if (!$boletin_id) {
    echo "ID de boletín no especificado.";
    exit;
}

// --- Cargar datos de boletín, alumno y calificaciones ---
$boletin = null;
$alumno = null;
$curso = null;
$calificaciones = [];

$sql = "SELECT * FROM boletin WHERE id=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $boletin_id);
$stmt->execute();
$result = $stmt->get_result();
$boletin = $result->fetch_assoc();
$stmt->close();

if (!$boletin) {
    echo "Boletín no encontrado.";
    exit;
}

// Alumno
$sql = "SELECT nombre, apellido, dni, codigo_miescuela FROM usuarios WHERE id=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $boletin['alumno_id']);
$stmt->execute();
$stmt->bind_result($nombre, $apellido, $dni, $codigo);
$stmt->fetch();
$alumno = ['nombre' => $nombre, 'apellido' => $apellido, 'dni' => $dni, 'codigo' => $codigo];
$stmt->close();

// Curso
$sql = "SELECT anio, division FROM cursos WHERE id=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $boletin['curso_id']);
$stmt->execute();
$stmt->bind_result($anio, $division);
$stmt->fetch();
$curso = ['anio' => $anio, 'division' => $division];
$stmt->close();

// Calificaciones
$sql = "SELECT m.nombre, cb.nota_numerica, cb.nota_conceptual, cb.observaciones
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

$backendDir   = dirname(__DIR__, 3); // sube desde .../backend/users/preceptor/utils -> .../backend
$templatePath = $backendDir . '/utils/plantillas/PlantillaBoletines.xlsx';
$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();

// Rellenar encabezado
$sheet->setCellValue('C6', $curso['anio'] . '°' . $curso['division']);
$sheet->setCellValue('D6', $alumno['apellido'] . ', ' . $alumno['nombre']);
$sheet->setCellValue('J6', $alumno['dni']);
$sheet->setCellValue('L6', $alumno['codigo']);

// Rellenar materias (fila 10 en adelante)
$fila = 10;
foreach ($calificaciones as $c) {
    $sheet->setCellValue('A' . $fila, $c['nombre']);
    $sheet->setCellValue('J' . $fila, $c['nota_numerica']);
    $sheet->setCellValue('M' . $fila, $c['nota_conceptual']);
    $fila++;
}

// Descargar Excel
$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Boletin_' . $alumno['apellido'] . '_' . $alumno['nombre'] . '.xlsx"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Boletín PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff;
        }

        .title {
            font-size: 2em;
            font-weight: bold;
        }

        .info {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #888;
            padding: 6px;
        }

        th {
            background: #f0f0f0;
        }
    </style>
</head>

<body>
    <div class="title">Boletín de Calificaciones</div>
    <div class="info">
        <b>Alumno:</b> <?php echo $alumno['apellido'] . ", " . $alumno['nombre']; ?><br>
        <b>DNI:</b> <?php echo $alumno['dni']; ?><br>
        <b>Curso:</b> <?php echo $curso['anio'] . "°" . $curso['division']; ?><br>
        <b>Año lectivo:</b> <?php echo $boletin['anio_lectivo']; ?><br>
        <b>Periodo:</b> <?php echo $boletin['periodo']; ?><br>
        <b>Estado:</b> <?php echo ucfirst($boletin['estado']); ?><br>
        <b>Fecha emisión:</b> <?php echo $boletin['fecha_emision'] ? date('d/m/Y', strtotime($boletin['fecha_emision'])) : "-"; ?>
    </div>
    <table>
        <thead>
            <tr>
                <th>Materia</th>
                <th>Nota numérica</th>
                <th>Nota conceptual</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($calificaciones as $c): ?>
                <tr>
                    <td><?php echo $c['nombre']; ?></td>
                    <td><?php echo $c['nota_numerica']; ?></td>
                    <td><?php echo htmlspecialchars($c['nota_conceptual']); ?></td>
                    <td><?php echo htmlspecialchars($c['observaciones']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div style="margin-top:20px">
        <b>Observaciones generales:</b><br>
        <?php echo nl2br(htmlspecialchars($boletin['observaciones'] ?? '')); ?>
    </div>
</body>
</html>