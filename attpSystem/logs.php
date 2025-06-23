<?php
include "../includes/db.php";

session_start();
$fecha_desde = trim($_GET['fecha_desde'] ?? '');
$fecha_hasta = trim($_GET['fecha_hasta'] ?? '');
if (!isset($_SESSION['usuario'])) {
  header("Location: ../login.php");
  exit;
}
$u = $_SESSION['usuario'];

?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <title>Logs de Préstamos</title>
  <link rel="stylesheet" href="../output.css">
  <link rel="icon" type="image/x-icon" href="../../images/et20png.png">
</head>

<body class="bg-gray-100 font-sans">
  <div class="relative min-h-screen flex">
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
        <a href="prestamos.php" class="block py-2 px-4 hover:bg-blue-700 rounded">Préstamos</a>
        <a href="logs.php" class="block py-2 px-4 bg-blue-700 rounded">Logs</a>
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
          ☰
        </button>
      </div>


      <h1 class="text-3xl font-bold mb-6">Logs de Préstamos</h1>

      <!-- Filtro de fecha -->
      <form method="GET" class="mb-6 flex flex-wrap gap-4 items-end">
        <div>
          <label class="block mb-1 font-semibold" for="fecha_desde">Fecha Desde (DD/MM/YYYY):</label>
          <input type="text" name="fecha_desde" id="fecha_desde" placeholder="01/06/2025" value="<?= htmlspecialchars($fecha_desde ?? '') ?>" class="border p-2 rounded">
        </div>
        <div>
          <label class="block mb-1 font-semibold" for="fecha_hasta">Fecha Hasta (DD/MM/YYYY):</label>
          <input type="text" name="fecha_hasta" id="fecha_hasta" placeholder="13/06/2025" value="<?= htmlspecialchars($fecha_hasta ?? '') ?>" class="border p-2 rounded">
        </div>
        <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded">Filtrar</button>
        <a href="logs.php" class="ml-2 bg-blue-700 text-white px-4 py-2 rounded">Limpiar filtro</a>
      </form>

      <!-- Tabla logs -->
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white shadow rounded-lg">
          <thead class="bg-blue-800 text-white">
            <tr>
              <th class="py-2 px-4">ID</th>
              <th class="py-2 px-4">Netbook</th>
              <th class="py-2 px-4">Alumno</th>
              <th class="py-2 px-4">Curso</th>
              <th class="py-2 px-4">Tutor</th>
              <th class="py-2 px-4">Fecha Préstamo</th>
              <th class="py-2 px-4">Hora Préstamo</th>
              <th class="py-2 px-4">Fecha Devolución</th>
              <th class="py-2 px-4">Hora Devolución</th>
            </tr>
          </thead>
          <tbody class="text-gray-700 divide-y">
            <?php
            function convertDateToSQL($fecha)
            {
              // Convierte DD/MM/YYYY a YYYY-MM-DD para comparación en SQL
              $parts = explode('/', $fecha);
              if (count($parts) == 3) {
                return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
              }
              return null;
            }

            $where = [];
            if (!empty($fecha_desde)) {
              $desde = convertDateToSQL($fecha_desde);
              if ($desde) {
                $where[] = "STR_TO_DATE(Fecha_Prestamo, '%d/%m/%Y') >= '$desde'";
              }
            }
            if (!empty($fecha_hasta)) {
              if ($hasta) {
                $where[] = "STR_TO_DATE(Fecha_Prestamo, '%d/%m/%Y') <= '$hasta'";
              }
            }

            $sql = "SELECT * FROM prestamos";
            if (count($where) > 0) {
              $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY STR_TO_DATE(Fecha_Prestamo, '%d/%m/%Y') DESC, Hora_Prestamo DESC";

            $result = $conexion->query($sql);

            while ($row = $result->fetch_assoc()):
            ?>
              <tr>
                <td class="py-2 px-4"><?= $row['Prestamo_ID'] ?></td>
                <td class="py-2 px-4"><?= $row['Netbook_ID'] ?></td>
                <td class="py-2 px-4"><?= $row['Alumno'] ?></td>
                <td class="py-2 px-4"><?= $row['Curso'] ?></td>
                <td class="py-2 px-4"><?= $row['Tutor'] ?></td>
                <td class="py-2 px-4"><?= $row['Fecha_Prestamo'] ?></td>
                <td class="py-2 px-4"><?= $row['Hora_Prestamo'] ?></td>
                <td class="py-2 px-4"><?= $row['Fecha_Devolucion'] ?? '-' ?></td>
                <td class="py-2 px-4"><?= $row['Hora_Devolucion'] ?? '-' ?></td>
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