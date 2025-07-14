<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_POST['rol'])) {
    header("Location: ../login.php?error=rol");
    exit;
}

$usuario = $_SESSION['usuario'];
$rol_id = (int)$_POST['rol'];

// Verificamos que el rol esté entre los disponibles
$roles_disponibles = $_SESSION['roles_disponibles'] ?? [];
$rol_valido = null;
foreach ($roles_disponibles as $r) {
    if ((int)$r['id'] === $rol_id) {
        $rol_valido = $r;
        break;
    }
}

if (!$rol_valido) {
    header("Location: ../login.php?error=rol");
    exit;
}

// ✅ Seteamos el rol activo
$_SESSION['usuario']['rol'] = $rol_valido['id'];
$_SESSION['usuario']['rol_nombre'] = $rol_valido['nombre'];
$_SESSION['rol_activo'] = $rol_valido['nombre'];

// 🔁 Redirigir según el nuevo rol
switch ($rol_id) {
    case 1: header("Location: ../users/admin/admin.php"); exit;
    case 2: header("Location: ../users/preceptor/preceptor.php"); exit;
    case 3: header("Location: ../users/profesor/profesor.php"); exit;
    case 4: header("Location: ../users/alumno/alumno.php"); exit;
    case 5: header("Location: ../attpSystem/index.php"); exit;
    default: header("Location: ../login.php?error=rol"); exit;
}