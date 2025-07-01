<?php
include "../includes/db.php";

session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: ../login.php");
  exit;
}
$u = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATTP - Prestamos</title>
  <link rel="stylesheet" href="../output.css">
  <link rel="icon" type="image/x-icon" href="../images/et20png.png">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }

    img {
      width: 50px;
      height: 50px;
    }
  </style>
</head>

<body class="bg-gray-100">

  <div class="flex flex-col md:flex-row min-h-screen">
    <!-- Sidebar -->
    <div id="sidebar" class="md:relative absolute top-0 left-0 w-64 bg-blue-800 text-white min-h-screen z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300">
      <div class="flex justify-between items-center p-4 border-b border-blue-700">
        <a href="#" class="flex items-center text-xl font-bold">
          <img src="../images/et20ico.ico" class="mr-2">
          Sistema ATTP
        </a>
      </div>

      <!-- Perfil del usuario -->
      <div class="p-6 text-center border-b border-blue-700">
        <img src="../images/blank-profile.webp" alt="Foto" class="w-20 h-20 no-repeat rounded-full mx-auto mb-2 object-cover border-2 border-white">
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
      <div class="p-6 mt-10 text-center text-gray-400"><a href="#">Créditos</a></div>
    </div>

    <!-- Contenido -->
    <main id="mainContent" class="w-full p-4 md:p-8 transition-all duration-300">
      <!-- Botón hamburguesa -->
      <div class="mb-4 md">
        <button id="toggleSidebar" class="text-2xl text-blue-800 bg-white p-2 rounded shadow">
          ☰
        </button>
      </div>

      <h1 class="text-3xl font-bold mb-6">Gestión de Préstamos</h1>

      <!-- Formulario para agregar préstamo -->
      <form action="actions/agregar_prestamo.php" method="POST" class="bg-white p-4 rounded shadow mb-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
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
        <table class="min-w-full bg-white shadow rounded-lg text-sm">
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
                <td class="py-2 px-4 text-center"><?= $row['Prestamo_ID'] ?></td>
                <td class="py-2 px-4 text-center"><?= $row['Netbook_ID'] ?></td>
                <td class="py-2 px-4 text-center"><?= $row['Alumno'] ?></td>
                <td class="py-2 px-4 text-center"><?= $row['Curso'] ?></td>
                <td class="py-2 px-4 text-center"><?= $row['Fecha_Prestamo'] ?></td>
                <td class="py-2 px-4 text-center"><?= $row['Hora_Prestamo'] ?></td>
                <td class="py-2 px-4 text-center"><?= $row['Tutor'] ?></td>
                <td class="py-2 px-4 text-center space-y-1 md:space-x-2 md:space-y-0 flex flex-col md:flex-row justify-center">
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