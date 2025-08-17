<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . './db.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sesión expirada']);
    exit;
}

$in = $_POST;
$id = (int) $_SESSION['usuario']['id'];

// --- Seguridad CSRF ---
$csrf = $in['csrf'] ?? '';
if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
    echo json_encode(['ok' => false, 'mensaje' => 'CSRF inválido']);
    exit;
}

$updates = [];
$params  = [];
$tipos   = "";

// ================== DATOS PERSONALES ==================
if (!empty($in['contrasena_actual']) && !empty($in['contrasena_nueva']) && !empty($in['confirmar_contrasena'])) {
    if ($in['contrasena_nueva'] !== $in['confirmar_contrasena']) {
        echo json_encode(['ok' => false, 'mensaje' => 'Las contraseñas no coinciden']);
        exit;
    }

    $res = $conexion->prepare("SELECT contrasena FROM usuarios WHERE id=?");
    $res->bind_param("i", $id);
    $res->execute();
    $res->bind_result($hash);
    $res->fetch();
    $res->close();

    if (!password_verify($in['contrasena_actual'], $hash)) {
        echo json_encode(['ok' => false, 'mensaje' => 'Contraseña actual incorrecta']);
        exit;
    }

    $newHash = password_hash($in['contrasena_nueva'], PASSWORD_BCRYPT);
    $updates[] = "contrasena=?";
    $params[]  = $newHash;
    $tipos    .= "s";
}

if (!empty($updates)) {
    $sql = "UPDATE usuarios SET " . implode(", ", $updates) . " WHERE id=?";
    $params[] = $id;
    $tipos   .= "i";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($tipos, ...$params);
    $stmt->execute();
}

// ================== DATOS FAMILIARES ==================
$padre_nombre       = $in['padre_nombre'] ?? null;
$padre_tel          = $in['padre_tel'] ?? null;
$padre_mail         = $in['padre_mail'] ?? null;
$madre_nombre       = $in['madre_nombre'] ?? null;
$madre_tel          = $in['madre_tel'] ?? null;
$madre_mail         = $in['madre_mail'] ?? null;
$emergencia_nombre  = $in['emergencia_nombre'] ?? null;
$emergencia_tel     = $in['emergencia_tel'] ?? null;

// Verificar si ya existe registro en datos_familiares
$check = $conexion->prepare("SELECT id FROM datos_familiares WHERE usuario_id=?");
$check->bind_param("i", $id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // Actualizar
    $sqlFam = "UPDATE datos_familiares SET 
        padre_nombre=?, padre_tel=?, padre_mail=?,
        madre_nombre=?, madre_tel=?, madre_mail=?,
        emergencia_nombre=?, emergencia_tel=?,
        updated_at=NOW()
        WHERE usuario_id=?";
    $stmtFam = $conexion->prepare($sqlFam);
    $stmtFam->bind_param(
        "ssssssssi",
        $padre_nombre, $padre_tel, $padre_mail,
        $madre_nombre, $madre_tel, $madre_mail,
        $emergencia_nombre, $emergencia_tel,
        $id
    );
    $stmtFam->execute();
} else {
    // Insertar
    $sqlFam = "INSERT INTO datos_familiares
        (usuario_id, padre_nombre, padre_tel, padre_mail,
         madre_nombre, madre_tel, madre_mail,
         emergencia_nombre, emergencia_tel)
        VALUES (?,?,?,?,?,?,?,?,?)";
    $stmtFam = $conexion->prepare($sqlFam);
    $stmtFam->bind_param(
        "issssssss",
        $id,
        $padre_nombre, $padre_tel, $padre_mail,
        $madre_nombre, $madre_tel, $madre_mail,
        $emergencia_nombre, $emergencia_tel
    );
    $stmtFam->execute();
}
$check->close();

// 🔹 Refrescar datos personales en la sesión
$res = $conexion->prepare("SELECT * FROM usuarios WHERE id=?");
$res->bind_param("i", $id);
$res->execute();
$result = $res->get_result();
if ($row = $result->fetch_assoc()) {
    $_SESSION['usuario'] = $row;
}

echo json_encode(['ok' => true, 'mensaje' => 'Datos actualizados correctamente']);