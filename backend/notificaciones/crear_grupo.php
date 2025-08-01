<?php
session_start();
require_once __DIR__ . '/../../backend/includes/db.php';

if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    die("⛔ Usuario no autorizado.");
}
if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
    die("⚠️ Token CSRF inválido.");
}

// Activar modo debug si querés ver resultados en pantalla
$debug = false;

$nombre = trim($_POST['nombre_grupo']);
$descripcion = trim($_POST['descripcion_grupo']);
$miembros = $_POST['miembros'] ?? [];
$creador_id = $_SESSION['usuario']['id'];

// Paso 1: Insertar en grupos_notificacion (tabla base)
$stmt0 = $conexion->prepare("INSERT INTO grupos_notificacion (nombre, creador_id) VALUES (?, ?)");
if (!$stmt0) die("❌ Error en prepare (grupos_notificacion): " . $conexion->error);
$stmt0->bind_param("si", $nombre, $creador_id);
if (!$stmt0->execute()) die("❌ Error al ejecutar INSERT en grupos_notificacion: " . $stmt0->error);
$grupo_id = $stmt0->insert_id;
$stmt0->close();

if ($debug) {
    echo "<pre>";
    echo "✅ ID generado en grupos_notificacion: $grupo_id" . PHP_EOL;
    echo "Miembros seleccionados: " . implode(', ', $miembros) . PHP_EOL;
}

// Paso 2: Insertar en grupos_notificacion_personalizados con ese ID
$stmt = $conexion->prepare("INSERT INTO grupos_notificacion_personalizados (id, nombre, descripcion, creador_id) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    die("❌ Error en prepare (grupo_personalizado): " . $conexion->error);
}
$stmt->bind_param("issi", $grupo_id, $nombre, $descripcion, $creador_id);
if (!$stmt->execute()) {
    die("❌ Error al ejecutar INSERT grupo_personalizado: " . $stmt->error);
}
$stmt->close();

// Paso 3: Insertar miembros (si hay)
if (!empty($miembros)) {
    $stmt2 = $conexion->prepare("INSERT INTO grupos_notificacion_miembros (grupo_id, usuario_id, activo) VALUES (?, ?, 1)");
    if (!$stmt2) {
        die("❌ Error en prepare (miembros): " . $conexion->error);
    }

    foreach ($miembros as $uid) {
        $g = (int)$grupo_id;
        $u = (int)$uid;
        $stmt2->bind_param("ii", $g, $u);
        if (!$stmt2->execute()) {
            if ($debug) echo "❌ Error al insertar miembro ID $u: " . $stmt2->error . PHP_EOL;
        } elseif ($debug) {
            echo "✅ Miembro agregado: ID $u" . PHP_EOL;
        }
    }

    $stmt2->close();
}

if ($debug) {
    echo "🎉 Todo finalizado correctamente.";
    exit;
}

// Redirección final (modo normal)
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;