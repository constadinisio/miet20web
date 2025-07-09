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
  <title>ATTP - Logs</title>
  <link rel="stylesheet" href="../output.css">
  <link rel="icon" type="image/x-icon" href="../images/et20png.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
  <div class="relative min-h-screen flex">
    <!-- Sidebar -->
    <div id="sidebar" class="absolute top-0 left-0 w-64 bg-blue-800 text-white min-h-screen z-50 transform -translate-x-full transition-transform duration-300">
      <div class="flex justify-between items-center p-4 border-b border-blue-700">
        <a href="#" class="flex items-center text-xl font-bold">
          <img src="../images/et20ico.ico" class="mr-2">
          Sistema ATTP
        </a>
      </div>

      <!-- Perfil del usuario -->
      <div class="p-6 text-center border-b border-blue-700">
        <img src="<?php echo $u['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $u['nombre']; ?>" class="block mx-auto rounded-full w-14 h-14">        <h2 class="text-lg font-semibold"><?php echo $u['nombre'] . ' ' . $u['apellido']; ?></h2>
        <p class="text-sm text-blue-200">ATTP</p>
      </div>

      <!-- Menú -->
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
      <div class="p-6 mt-10 text-center text-gray-400"><button onclick="mostrarCreditos()">Créditos</button></div>
    </div>

    <!-- Contenido -->
    <main id="mainContent" class="w-full px-4 py-8 transition-all duration-300 container mx-auto">
      <!-- Botón hamburguesa -->
      <div class="mb-4">
        <button id="toggleSidebar" class="text-2xl text-blue-800 bg-white p-2 rounded shadow">
          ☰</button>
      </div>

      <h1 class="text-3xl font-bold mb-6">Logs de Préstamos</h1>

      <!-- Filtro de fecha -->
      <form method="GET" class="mb-6 flex flex-col sm:flex-row flex-wrap gap-4 items-end">
        <div class="w-full sm:w-auto">
          <label class="block mb-1 font-semibold" for="fecha_desde">Fecha Desde (DD/MM/YYYY):</label>
          <input type="text" name="fecha_desde" id="fecha_desde" placeholder="01/06/2025" value="<?= htmlspecialchars($fecha_desde ?? '') ?>" class="border p-2 rounded w-full">
        </div>
        <div class="w-full sm:w-auto">
          <label class="block mb-1 font-semibold" for="fecha_hasta">Fecha Hasta (DD/MM/YYYY):</label>
          <input type="text" name="fecha_hasta" id="fecha_hasta" placeholder="13/06/2025" value="<?= htmlspecialchars($fecha_hasta ?? '') ?>" class="border p-2 rounded w-full">
        </div>
        <div class="flex gap-2">
          <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded">Filtrar</button>
          <a href="logs.php" class="bg-blue-700 text-white px-4 py-2 rounded">Limpiar filtro</a>
        </div>
      </form>

      <!-- Tabla logs -->
      <div class="overflow-x-auto rounded shadow bg-white">
        <table class="min-w-full">
          <thead class="bg-blue-800 text-white text-sm">
            <tr>
              <th class="py-2 px-4 whitespace-nowrap">ID</th>
              <th class="py-2 px-4 whitespace-nowrap">Netbook</th>
              <th class="py-2 px-4 whitespace-nowrap">Alumno</th>
              <th class="py-2 px-4 whitespace-nowrap">Curso</th>
              <th class="py-2 px-4 whitespace-nowrap">Tutor</th>
              <th class="py-2 px-4 whitespace-nowrap">Fecha Préstamo</th>
              <th class="py-2 px-4 whitespace-nowrap">Hora Préstamo</th>
              <th class="py-2 px-4 whitespace-nowrap">Fecha Devolución</th>
              <th class="py-2 px-4 whitespace-nowrap">Hora Devolución</th>
            </tr>
          </thead>
          <tbody class="text-gray-700 divide-y text-sm">
            <?php
            function convertDateToSQL($fecha)
            {
              $parts = explode('/', $fecha);
              return count($parts) === 3 ? "$parts[2]-$parts[1]-$parts[0]" : null;
            }

            $where = [];
            if (!empty($fecha_desde)) {
              $desde = convertDateToSQL($fecha_desde);
              if ($desde) $where[] = "STR_TO_DATE(Fecha_Prestamo, '%d/%m/%Y') >= '$desde'";
            }
            if (!empty($fecha_hasta)) {
              $hasta = convertDateToSQL($fecha_hasta);
              if ($hasta) $where[] = "STR_TO_DATE(Fecha_Prestamo, '%d/%m/%Y') <= '$hasta'";
            }

            $sql = "SELECT * FROM prestamos";
            if (count($where) > 0) $sql .= " WHERE " . implode(' AND ', $where);
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

  <!-- Modal Créditos -->
  <div id="popupCreditos" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden transition-shadow 0.3s">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md text-center relative">
      <h2 class="text-2xl font-bold mb-4">Créditos</h2>
      <p class="text-gray-700 mb-4">
        Panel de Noticias desarrollado por el equipo de ATTP:<br>
        👨‍💻 Desarrolladores:
        <br>- Liz Vera
        - Uma Perez
        - Michael Martinez
        - Jaco Alfaro
        - Kevin Mamani
        - Brenda Huanca
        <br>🤗 Colaboradores:
        <br>- Fabricio Toscano
        <br>👨‍🏫 Profesores:
        <br>- Nicolas Bogarin
      </p>
      <button onclick="cerrarCreditos()" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
        Cerrar
      </button>
    </div>
  </div>
  <!-- Función de Mostrar/Ocultar el Popup de Créditos -->
  <script>
    function mostrarCreditos() {
      document.getElementById('popupCreditos').classList.remove('hidden');
    }

    function cerrarCreditos() {
      document.getElementById('popupCreditos').classList.add('hidden');
    }
  </script>
</body>

</html>