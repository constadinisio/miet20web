<?php if (!empty($_SESSION['completar_datos'])): ?>
    <div id="popup-datos"
         class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 
                opacity-0 pointer-events-none transition-opacity duration-300 ease-out">
        <div class="bg-white rounded-xl p-6 shadow-2xl max-w-md w-full transform scale-95 opacity-0 
                    transition-all duration-300 ease-out">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2 text-yellow-600">
                ⚠️ Datos incompletos
            </h2>
            <p class="mb-4">Debes completar los siguientes datos para continuar:</p>

            <form id="form-datos" class="space-y-4">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

                <?php foreach ($_SESSION['completar_datos'] as $campo => $label): ?>
                    <div class="text-left">
                        <label class="block font-semibold mb-1"><?= htmlspecialchars($label) ?></label>
                        <?php
                            $type = "text";
                            if (stripos($campo, "fecha") !== false) $type = "date";
                            elseif (stripos($campo, "mail") !== false) $type = "email";
                            elseif (stripos($campo, "tel") !== false) $type = "tel";
                        ?>
                        <?php if ($campo === 'direccion'): ?>
                            <textarea name="<?= htmlspecialchars($campo) ?>"
                                class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-300 focus:outline-none"
                                placeholder="Ingresa <?= strtolower($label) ?>" required></textarea>
                        <?php else: ?>
                            <input type="<?= $type ?>" name="<?= htmlspecialchars($campo) ?>"
                                class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-300 focus:outline-none"
                                placeholder="Ingresa <?= strtolower($label) ?>" required>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit"
                    class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                    Guardar datos
                </button>
            </form>

            <p id="msg" class="mt-3 text-sm text-gray-600"></p>
        </div>
    </div>

    <style>
        body { overflow: hidden; }
    </style>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const overlay = document.getElementById("popup-datos");
        const modal = overlay.querySelector("div");

        // Forzar animación de entrada
        requestAnimationFrame(() => {
            overlay.classList.remove("opacity-0", "pointer-events-none");
            overlay.classList.add("opacity-100");
            modal.classList.remove("opacity-0", "scale-95");
            modal.classList.add("opacity-100", "scale-100");
        });
    });

    document.getElementById("form-datos").addEventListener("submit", async function(e) {
        e.preventDefault();
        const form = e.target;
        const data = new FormData(form);

        let res = await fetch("/includes/completar_datos_guardar.php", {
            method: "POST",
            body: data
        });
        let json = await res.json();

        const msg = document.getElementById("msg");

        if (json.ok) {
            msg.textContent = "✅ Datos guardados correctamente";
            msg.className = "mt-3 text-sm text-green-600";

            // Animación de salida
            const overlay = document.getElementById("popup-datos");
            const modal = overlay.querySelector("div");
            overlay.classList.remove("opacity-100");
            overlay.classList.add("opacity-0", "pointer-events-none");
            modal.classList.remove("opacity-100", "scale-100");
            modal.classList.add("opacity-0", "scale-95");

            setTimeout(() => {
                overlay.remove();
                document.body.style.overflow = "auto";
            }, 300);
        } else {
            msg.textContent = "⚠️ " + (json.mensaje || "Error al guardar");
            msg.className = "mt-3 text-sm text-red-600";
        }
    });
    </script>
<?php endif; ?>