<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../login.php?error=rol");
    exit;
}
require_once '../../../includes/db.php';

// --- RECIBE ID DE USUARIO POR GET ---
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID de usuario inválido.");
}

// TRAE USUARIO
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$usuario_editado = $res->fetch_assoc();
$stmt->close();
if (!$usuario_editado) die("Usuario no encontrado.");

// ROLES POSIBLES
$roles = [];
$res = $conexion->query("SELECT id, nombre FROM roles ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $roles[] = $row;
}

// ROLES ADICIONALES
$roles_adicionales = [];
$sql = "SELECT rol_id FROM usuario_roles WHERE usuario_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $roles_adicionales[] = $row['rol_id'];
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rol_principal = (int)$_POST['rol_principal'];
    $roles_secundarios = $_POST['roles_adicionales'] ?? [];

    // Actualizar rol principal
    $sql = "UPDATE usuarios SET rol = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $rol_principal, $id);
    $stmt->execute();
    $stmt->close();

    // Eliminar todos los adicionales y agregar solo los seleccionados
    $sql = "DELETE FROM usuario_roles WHERE usuario_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    foreach ($roles_secundarios as $rol_id) {
        $rol_id = (int)$rol_id;
        if ($rol_id !== $rol_principal) { // Evitar duplicado
            $sql = "INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $id, $rol_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Permisos especiales
    $permNoticia = isset($_POST['permNoticia']) ? 1 : 0;
    $permSubidaArch = isset($_POST['permSubidaArch']) ? 1 : 0;

    $sql = "UPDATE usuarios SET permNoticia = ?, permSubidaArch = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iii", $permNoticia, $permSubidaArch, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: ../usuarios.php?ok=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex">
    <nav class="w-60 bg-white shadow-lg px-6 py-4 flex flex-col gap-2">
        <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400">
            <img src="../../../images/et20ico.ico" class="block items-center h-28 w-28">
        </div>
        <div class="flex items-center mb-10 gap-2">
            <img src="<?php echo $_SESSION['usuario']['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $_SESSION['usuario']['nombre']; ?>" class="rounded-full w-14 h-14">
            <div class="flex flex-col pl-3">
                <div class="font-bold text-lg leading-tight"><?php echo $_SESSION['usuario']['nombre']; ?></div>
                <div class="font-bold text-lg leading-tight"><?php echo $_SESSION['usuario']['apellido']; ?></div>
                <div class="mt-2 text-xs text-gray-500">Administrador/a</div>
            </div>
        </div>
        <a href="../admin.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="../usuarios.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">👥 Usuarios</a>
        <a href="../cursos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">🏫 Cursos</a>
        <a href="../alumnos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">👤 Alumnos</a>
        <a href="../materias.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📚 Materias</a>
        <a href="../horarios.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">⏰ Horarios</a>
        <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
            <form method="post" action="../../../includes/cambiar_rol.php" class="mt-auto mb-3">
                <select name="rol" onchange="this.form.submit()" class="w-full px-3 py-2 border text-sm rounded-xl text-gray-700 bg-white">
                    <?php foreach ($_SESSION['roles_disponibles'] as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php if ($_SESSION['usuario']['rol'] == $r['id']) echo 'selected'; ?>>
                            Cambiar a: <?php echo ucfirst($r['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <button onclick="window.location='../../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6">Editar Usuario</h1>
        <form method="post" class="max-w-xl bg-white rounded-xl shadow p-6 space-y-4">
            <div>
                <label class="font-bold">Nombre:</label>
                <input type="text" value="<?= htmlspecialchars($usuario_editado['nombre']) ?>" class="border rounded px-3 py-2 w-full bg-gray-100" disabled>
            </div>
            <div>
                <label class="font-bold">Apellido:</label>
                <input type="text" value="<?= htmlspecialchars($usuario_editado['apellido']) ?>" class="border rounded px-3 py-2 w-full bg-gray-100" disabled>
            </div>
            <div>
                <label class="font-bold">Email:</label>
                <input type="text" value="<?= htmlspecialchars($usuario_editado['mail']) ?>" class="border rounded px-3 py-2 w-full bg-gray-100" disabled>
            </div>
            <div>
                <label class="font-bold">Rol principal:</label>
                <select name="rol_principal" required class="border rounded px-3 py-2 w-full">
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= $rol['id'] ?>"
                            <?= ($usuario_editado['rol'] == $rol['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rol['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="font-bold">Roles adicionales:</label><br>
                <?php foreach ($roles as $rol): ?>
                    <?php if ($rol['id'] != $usuario_editado['rol']): ?>
                        <label class="inline-flex items-center mr-4">
                            <input type="checkbox" name="roles_adicionales[]" value="<?= $rol['id'] ?>"
                                <?= in_array($rol['id'], $roles_adicionales) ? 'checked' : '' ?>>
                            <span class="ml-2"><?= htmlspecialchars($rol['nombre']) ?></span>
                        </label>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div>
                <label class="font-bold">Permisos Especiales:</label><br>
                <label class="inline-flex items-center mr-4">
                    <input type="checkbox" name="permNoticia" value="1" <?= !empty($usuario_editado['permNoticia']) ? 'checked' : '' ?>>
                    <span class="ml-2">Acceso a Panel de Noticias</span>
                </label>
                <label class="inline-flex items-center mr-4">
                    <input type="checkbox" name="permSubidaArch" value="1" <?= !empty($usuario_editado['permSubidaArch']) ? 'checked' : '' ?>>
                    <span class="ml-2">Acceso a Subida de Imágenes</span>
                </label>
            </div>
            <div class="flex gap-2 mt-6">
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-bold">
                    Guardar cambios
                </button>
                <a href="../usuarios.php" class="px-6 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Volver</a>
            </div>
        </form>
    </main>
</body>

</html>