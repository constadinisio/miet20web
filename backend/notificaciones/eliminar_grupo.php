<?php
session_start();
require_once __DIR__ . '/../../backend/includes/db.php';

if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) exit;
if ($_POST['csrf'] !== $_SESSION['csrf']) exit;

$grupo_id = (int)$_POST['grupo_id'];

$conexion->query("DELETE FROM grupos_notificacion_miembros WHERE grupo_id = $grupo_id");
$conexion->query("DELETE FROM grupos_notificacion_personalizados WHERE id = $grupo_id");

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;