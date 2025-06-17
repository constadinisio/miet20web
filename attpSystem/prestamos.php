<?php
include "includes/conexion.php";

session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}
$u = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Gestión de Préstamos</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/x-icon" href="../../images/et20png.png">
</head>

<body class="bg-gray-100 font-sans">

  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <div id="sidebar" class="absolute top-0 left-0 w-64 bg-blue-800 text-white min-h-screen z-50 transform -translate-x-full transition-transform duration-300">
      <div class="flex justify-between items-center p-4 border-b border-blue-700">
        <div class="text-lg font-bold">Menú</div>
        <button id="closeSidebar" class="text-2xl leading-none hover:text-red-400">
          &times;
        </button>
      </div>

      <!-- Resto del contenido del sidebar acá -->

      <!-- Perfil del usuario -->
      <div class="p-6 text-center border-b border-blue-700">
        <img src="../images/blank-profile.png" alt="Foto" class="w-20 h-20 no-repeat rounded-full mx-auto mb-2 object-cover border-2 border-white">
        <h2 class="text-lg font-semibold"><?php echo $u['nombre'] . ' ' . $u['apellido']; ?></h2>
        <p class="text-sm text-blue-200">ATTP</p>
      </div>

      <!-- Menú -->
      <div class="p-6 text-2xl font-bold text-center">ET20 Netbooks</div>
      <nav class="p-4 space-y-2">
        <a href="index.php" class="block py-2 px-4 hover:bg-blue-700 rounded">Página Principal</a>
        <a href="stock.php" class="block py-2 px-4 hover:bg-blue-700 rounded">Stock</a>
        <a href="prestamos.php" class="block py-2 px-4 bg-blue-700 rounded">Préstamos</a>
        <a href="logs.php" class="block py-2 px-4 hover:bg-blue-700 rounded">Logs</a>
      </nav>
      <div class="p-4 border-t border-blue-700">
        <form action="./actions/logout.php" method="POST">
          <button type="submit" class="w-full py-2 px-4 bg-red-600 hover:bg-red-700 text-white rounded text-center">
            Cerrar sesión
          </button>
        </form>
      </div>
    </div>

    <!-- Contenido -->
    <main id="mainContent" class="w-full p-8 transition-all duration-300">
      <!-- Botón hamburguesa -->
      <div class="mb-4">
        <button id="toggleSidebar" class="text-2xl text-blue-800 bg-white p-2 rounded shadow">
          ☰ Menú
        </button>
      </div>

      <h1 class="text-3xl font-bold mb-6">Gestión de Préstamos</h1>

      <!-- Formulario para agregar préstamo -->
      <form action="actions/agregar_prestamo.php" method="POST" class="bg-white p-4 rounded shadow mb-6 grid grid-cols-2 md:grid-cols-3 gap-4">
        <input name="Netbook_ID" required placeholder="Netbook ID (ej: A1)" class="border p-2 rounded">
        <input name="Alumno" required placeholder="Alumno" class="border p-2 rounded">
        <input name="Curso" required placeholder="Curso (ej: 3°4°)" class="border p-2 rounded">
        <input name="Tutor" required placeholder="Tutor" class="border p-2 rounded">
        <input name="Fecha_Prestamo" required placeholder="Fecha (DD/MM/AAAA)" class="border p-2 rounded">
        <input name="Hora_Prestamo" required placeholder="Hora (HH:MM)" class="border p-2 rounded">
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Registrar Préstamo</button>
      </form>

      <!-- Tabla de préstamos -->
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white shadow rounded-lg">
          <thead class="bg-blue-800 text-white">
            <tr>
              <th class="py-2 px-4 text-left">ID</th>
              <th class="py-2 px-4">Netbook</th>
              <th class="py-2 px-4">Alumno</th>
              <th class="py-2 px-4">Curso</th>
              <th class="py-2 px-4">Fecha</th>
              <th class="py-2 px-4">Hora</th>
              <th class="py-2 px-4">Tutor</th>
              <th class="py-2 px-4">Acciones</th>
            </tr>
          </thead>
          <tbody class="text-gray-700 divide-y">
            <?php
            $result = $conexion->query("SELECT * FROM prestamos WHERE Fecha_Devolucion IS NULL ORDER BY Prestamo_ID DESC");
            while ($row = $result->fetch_assoc()):
            ?>
              <tr>
                <td class="py-2 px-4"><?= $row['Prestamo_ID'] ?></td>
                <td class="py-2 px-4"><?= $row['Netbook_ID'] ?></td>
                <td class="py-2 px-4"><?= $row['Alumno'] ?></td>
                <td class="py-2 px-4"><?= $row['Curso'] ?></td>
                <td class="py-2 px-4"><?= $row['Fecha_Prestamo'] ?></td>
                <td class="py-2 px-4"><?= $row['Hora_Prestamo'] ?></td>
                <td class="py-2 px-4"><?= $row['Tutor'] ?></td>
                <td class="py-2 px-4 space-x-2">
                  <a href="actions/devolver_prestamo.php?id=<?= $row['Prestamo_ID'] ?>" class="bg-green-600 text-white px-2 py-1 rounded text-sm">Devolver</a>
                  <a href="actions/eliminar_prestamo.php?id=<?= $row['Prestamo_ID'] ?>" onclick="return confirm('¿Eliminar préstamo?')" class="bg-red-600 text-white px-2 py-1 rounded text-sm">Eliminar</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <script>
  const sidebar = document.getElementById("sidebar");
  const toggleSidebar = document.getElementById("toggleSidebar");
  const closeSidebar = document.getElementById("closeSidebar");
  const mainContent = document.getElementById("mainContent");

  function toggleSidebarVisible() {
    const visible = !sidebar.classList.contains("-translate-x-full");
    if (visible) {
      sidebar.classList.add("-translate-x-full");
      mainContent.classList.remove("ml-64");
    } else {
      sidebar.classList.remove("-translate-x-full");
      mainContent.classList.add("ml-64");
    }
  }

  toggleSidebar.addEventListener("click", toggleSidebarVisible);
  closeSidebar.addEventListener("click", toggleSidebarVisible);
  </script>
</body>

</html>