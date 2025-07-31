<?php
session_start();
require_once __DIR__ . '/../../../backend/includes/db.php';

// Chequear si se envió el formulario
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? 'Prueba';
    $contenido = $_POST['contenido'] ?? 'Este es el contenido de prueba.';
    $destinatario_id = intval($_POST['destinatario_id'] ?? 0);
    $remitente_id = $_SESSION['usuario']['id'] ?? 1;

    // Crear notificación
    $stmt = $conexion->prepare("INSERT INTO notificaciones 
        (titulo, contenido, tipo_notificacion, remitente_id, fecha_creacion, prioridad, estado, requiere_confirmacion) 
        VALUES (?, ?, 'INDIVIDUAL', ?, NOW(), 'NORMAL', 'ACTIVA', 0)");
    $stmt->bind_param("ssi", $titulo, $contenido, $remitente_id);
    $stmt->execute();
    $notificacion_id = $stmt->insert_id;
    $stmt->close();

    // Relacionar con destinatario
    $stmt2 = $conexion->prepare("INSERT INTO notificaciones_destinatarios 
        (notificacion_id, destinatario_id, estado_lectura) 
        VALUES (?, ?, 'NO_LEIDA')");
    $stmt2->bind_param("ii", $notificacion_id, $destinatario_id);
    $stmt2->execute();
    $stmt2->close();

    $mensaje = "¡Notificación creada y enviada!";
}

// Conseguir lista de usuarios para el select
$usuarios = $conexion->query("SELECT id, nombre, apellido FROM usuarios ORDER BY apellido, nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Test Notificaciones</title>
  <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
  <form method="post" class="bg-white shadow-xl p-8 rounded-2xl flex flex-col gap-4 w-96">
    <h1 class="text-xl font-bold mb-2">Crear Notificación de Prueba</h1>
    <?php if($mensaje): ?>
      <div class="bg-green-100 text-green-700 rounded p-2"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    <label class="font-semibold">Destinatario:</label>
    <select name="destinatario_id" class="border rounded p-2" required>
      <option value="">Selecciona un usuario</option>
      <?php while($u = $usuarios->fetch_assoc()): ?>
        <option value="<?= $u['id'] ?>">
          <?= htmlspecialchars($u['apellido'] . ", " . $u['nombre']) ?>
        </option>
      <?php endwhile; ?>
    </select>
    <label class="font-semibold">Título:</label>
    <input type="text" name="titulo" class="border rounded p-2" value="Notificación de prueba" required>
    <label class="font-semibold">Contenido:</label>
    <textarea name="contenido" class="border rounded p-2" required>Este es el contenido de prueba.</textarea>
    <button type="submit" class="mt-4 bg-blue-600 text-white rounded-xl px-4 py-2 font-bold hover:bg-blue-700">Enviar</button>
  </form>
</body>
</html>
