<?php
include "../includes/db.php";

$query = "SELECT id, carrito, numero, numero_serie, fecha_adquisicion, estado, observaciones FROM netbooks ORDER BY carrito, numero";
$result = $conexion->query($query);

$estados = ['En uso', 'Dañada', 'Hurto', 'Obsoleta'];

if (!$result) {
  die("Error en la consulta SQL: " . $conexion->error);
}

session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: ../login.php");
  exit;
}
$u = $_SESSION['usuario'];

$sql_disponibles = "
  SELECT COUNT(*) AS cantidad
  FROM netbooks n
  WHERE n.estado = 'En uso'
  AND NOT EXISTS (
    SELECT 1 FROM prestamos p 
    WHERE p.Netbook_ID = CONCAT(n.carrito, n.numero)
    AND p.Fecha_Devolucion IS NULL
  )
";
$res_disponibles = $conexion->query($sql_disponibles);
$disponibles = $res_disponibles->fetch_assoc()['cantidad'] ?? 0;

$sql_no_disponibles = "
  SELECT COUNT(*) AS cantidad 
  FROM netbooks
  WHERE estado IN ('Hurto', 'Obsoleta', 'Dañada')
";
$res_no_disp = $conexion->query($sql_no_disponibles);
$no_disponibles = $res_no_disp->fetch_assoc()['cantidad'] ?? 0;

$sql_prestamos_curso = "
  SELECT COUNT(*) AS cantidad
  FROM prestamos
  WHERE Fecha_Devolucion IS NULL OR Fecha_Devolucion = ''
";
$res_prestamos = $conexion->query($sql_prestamos_curso);
$prestamos_curso = $res_prestamos->fetch_assoc()['cantidad'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Netbooks</title>
  <link rel="stylesheet" href="../output.css">
  <link rel="icon" type="image/x-icon" href="../../images/et20png.png">
</head>

<body class="bg-gray-100 font-sans">
  <div class="relative min-h-screen flex flex-col md:flex-row">
    <div id="sidebar" class="md:relative absolute top-0 left-0 w-64 bg-blue-800 text-white min-h-screen z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300">
      <div class="flex justify-between items-center p-4 border-b border-blue-700">
        <div class="text-lg font-bold">Menú</div>
        <button id="closeSidebar" class="text-2xl leading-none hover:text-red-400 md:hidden">
          &times;
        </button>
      </div>
      <div class="p-6 text-center border-b border-blue-700">
        <img src="../images/blank-profile.png" alt="Foto" class="w-20 h-20 no-repeat rounded-full mx-auto mb-2 object-cover border-2 border-white">
        <h2 class="text-lg font-semibold"><?php echo $u['nombre'] . ' ' . $u['apellido']; ?></h2>
        <p class="text-sm text-blue-200">ATTP</p>
      </div>
      <div class="p-6 text-2xl font-bold text-center">ET20 Netbooks</div>
      <nav class="p-4 space-y-2">
        <a href="index.php" class="block py-2 px-4 hover:bg-blue-700 rounded">Página Principal</a>
        <a href="stock.php" class="block py-2 px-4 bg-blue-700 rounded">Stock</a>
        <a href="prestamos.php" class="block py-2 px-4 hover:bg-blue-700 rounded">Préstamos</a>
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

    <main id="mainContent" class="w-full p-4 md:p-8 transition-all duration-300">
      <div class="mb-4 md:hidden">
        <button id="toggleSidebar" class="text-2xl text-blue-800 bg-white p-2 rounded shadow">
          ☰
        </button>
      </div>

      <h1 class="text-3xl font-bold mb-6">Gestión de Netbooks</h1>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-4">
        <div class="bg-white rounded shadow p-6">
          <h2 class="text-xl font-semibold mb-2">Computadoras disponibles</h2>
          <p class="text-5xl font-bold text-green-600"><?= $disponibles ?></p>
        </div>
        <div class="bg-white rounded shadow p-6">
          <h2 class="text-xl font-semibold mb-2">Computadoras no disponibles</h2>
          <p class="text-5xl font-bold text-red-600"><?= $no_disponibles ?></p>
        </div>
        <div class="bg-white rounded shadow p-6">
          <h2 class="text-xl font-semibold mb-2">Préstamos en curso</h2>
          <p class="text-5xl font-bold text-yellow-600"><?= $prestamos_curso ?></p>
        </div>
      </div>

      <form action="actions/agregar_netbook.php" method="POST" class="bg-white p-4 rounded shadow mb-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <input name="carrito" required placeholder="Carrito (A-Z)" class="border p-2 rounded">
        <input name="numero" required placeholder="Número (01-30)" class="border p-2 rounded">
        <input name="numero_serie" required placeholder="Número de Serie" class="border p-2 rounded">
        <input name="fecha_adquisicion" placeholder="Fecha Adquisición (DD/MM/AAAA)" class="border p-2 rounded">
        <select name="estado" class="border p-2 rounded">
          <option value="En uso">En uso</option>
          <option value="Dañada">Dañada</option>
          <option value="Hurto">Hurto</option>
          <option value="Obsoleta">Obsoleta</option>
        </select>
        <textarea name="observaciones" placeholder="Observaciones" class="border p-2 rounded col-span-full"></textarea>
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Añadir Netbook</button>
      </form>

      <div class="mb-4">
        <input id="searchInput" type="text" placeholder="Buscar netbook por carrito, número, serie, estado..." class="w-full p-2 border rounded" onkeyup="filtrarTabla()" />
      </div>

      <div class="overflow-x-auto w-full">
        <table class="min-w-full bg-white shadow rounded text-sm">
          <thead class="sticky top-0 bg-gray-50 z-10">
            <tr>
              <th class="py-2 px-4 border-b">Carrito</th>
              <th class="py-2 px-4 border-b">Número</th>
              <th class="py-2 px-4 border-b">Número de Serie</th>
              <th class="py-2 px-4 border-b">Fecha Adquisición</th>
              <th class="py-2 px-4 border-b">Estado</th>
              <th class="py-2 px-4 border-b">Observaciones</th>
              <th class="py-2 px-4 border-b">Acción</th>
            </tr>
          </thead>
          <tbody id="tablaNetbooks">
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr class="hover:bg-gray-100">
                <td class="py-2 px-4 border-b text-center"><?= htmlspecialchars($row['carrito']) ?></td>
                <td class="py-2 px-4 border-b text-center"><?= htmlspecialchars($row['numero']) ?></td>
                <td class="py-2 px-4 border-b text-center"><?= htmlspecialchars($row['numero_serie']) ?></td>
                <td class="py-2 px-4 border-b text-center"><?= htmlspecialchars($row['fecha_adquisicion']) ?></td>
                <td class="py-2 px-4 border-b text-center">
                  <select onchange="actualizarEstado(this, <?= $row['id'] ?>)" class="border rounded px-2 py-1 text-center">
                    <?php foreach ($estados as $estado): ?>
                      <option value="<?= $estado ?>" <?= ($row['estado'] === $estado) ? 'selected' : '' ?>>
                        <?= $estado ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td class="py-2 px-4">
                  <form action="actions/editar_observacion.php" method="POST" class="flex">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <input name="observaciones" value="<?= htmlspecialchars($row['observaciones']) ?>" class="border p-1 rounded w-full text-sm">
                    <button class="ml-2 px-2 bg-yellow-500 text-white text-sm rounded">💾</button>
                  </form>
                </td>
                <td class="py-2 px-4">
                  <a href="actions/eliminar_netbook.php?id=<?= $row['id'] ?>" onclick="return confirm('¿Eliminar esta netbook?')" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm">Eliminar</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <script>
        function filtrarTabla() {
          const input = document.getElementById("searchInput");
          const filter = input.value.toLowerCase();
          const tabla = document.getElementById("tablaNetbooks");
          const filas = tabla.getElementsByTagName("tr");

          for (let i = 0; i < filas.length; i++) {
            const fila = filas[i];
            const celdas = fila.getElementsByTagName("td");
            let textoFila = "";

            for (let j = 0; j < celdas.length; j++) {
              textoFila += celdas[j].textContent.toLowerCase() + " ";
            }

            fila.style.display = textoFila.indexOf(filter) > -1 ? "" : "none";
          }
        }

        function actualizarEstado(selectElement, id) {
          const nuevoEstado = selectElement.value;
          fetch('./actions/update_estado.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: 'id=' + encodeURIComponent(id) + '&estado=' + encodeURIComponent(nuevoEstado)
            })
            .then(response => response.text())
            .then(data => {
              if (data !== 'OK') {
                alert('Error al actualizar el estado: ' + data);
              }
            })
            .catch(error => {
              alert('Error en la conexión: ' + error);
            });
        }
      </script>
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