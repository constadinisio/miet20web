<?php
require_once 'db.php';
require_once 'config_campos.php';

if (!isset($_SESSION['usuario'])) return;

$usuario = $_SESSION['usuario'];
$campos_faltantes = [];

// Campos comunes para todos
foreach ($CAMPOS_OBLIGATORIOS_COMUNES as $campo => $label) {
    if (!isset($usuario[$campo]) || trim($usuario[$campo]) === '' || $usuario[$campo] === null) {
        $campos_faltantes[$campo] = $label;
    }
}

// Si no es alumno (rol != 4) → también ficha censal
if ((int)$usuario['rol'] !== 4) {
    foreach ($CAMPOS_OBLIGATORIOS_EXTRA as $campo => $label) {
        if (!isset($usuario[$campo]) || trim($usuario[$campo]) === '' || $usuario[$campo] === null) {
            $campos_faltantes[$campo] = $label;
        }
    }
}

if (!empty($campos_faltantes)) {
    $_SESSION['completar_datos'] = $campos_faltantes;
} else {
    unset($_SESSION['completar_datos']);
}