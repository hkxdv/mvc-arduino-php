<?php

/**
 * configurar_arduino.modulo.php
 * Vista para configurar la conexión con el Arduino
 */

// Obtener valores actuales desde .env o valores por defecto
$puertoActual = $_ENV['ARDUINO_PORT'] ?? 'COM3';
$baudrateActual = intval($_ENV['ARDUINO_BAUDRATE'] ?? 9600);

// Detectar sistema operativo
$sistemaOperativo = 'Windows';
if (strtoupper(substr(PHP_OS, 0, 3)) === 'LIN') {
    $sistemaOperativo = 'Linux';
} elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'DAR') {
    $sistemaOperativo = 'Mac';
}

// Lista de puertos comunes según el sistema operativo
$puertosComunes = [
    'Windows' => ['COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6'],
    'Linux' => ['/dev/ttyACM0', '/dev/ttyACM1', '/dev/ttyUSB0', '/dev/ttyUSB1'],
    'Mac' => ['/dev/cu.usbmodem1411', '/dev/cu.usbmodem1421', '/dev/cu.usbserial-1410']
];

// Velocidades comunes
$velocidadesComunes = [
    300,
    1200,
    2400,
    4800,
    9600,
    14400,
    19200,
    38400,
    57600,
    115200
];

// Procesar formulario
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanear parámetros
    $puerto = filter_input(INPUT_POST, 'puerto', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $baudrate = filter_input(INPUT_POST, 'baudrate', FILTER_SANITIZE_NUMBER_INT);

    // Si se seleccionó "personalizado", usar el valor del campo personalizado
    if ($puerto === 'personalizado') {
        $puerto = filter_input(INPUT_POST, 'puertoPersonalizado', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    // Validar datos
    if (empty($puerto)) {
        $error = "El puerto no puede estar vacío";
    } else if (empty($baudrate) || !is_numeric($baudrate)) {
        $error = "El baudrate debe ser un valor numérico";
    } else {
        // Intentar actualizar el archivo .env
        try {
            $envFile = dirname(dirname(dirname(dirname(__DIR__)))) . '/.env';
            $envContent = file_get_contents($envFile);

            // Verificar si ya existe la configuración de Arduino
            if (strpos($envContent, 'ARDUINO_PORT') !== false) {
                // Actualizar valores existentes
                $envContent = preg_replace('/ARDUINO_PORT=.*(\r?\n|$)/', "ARDUINO_PORT=$puerto$1", $envContent);
                $envContent = preg_replace('/ARDUINO_BAUDRATE=.*(\r?\n|$)/', "ARDUINO_BAUDRATE=$baudrate$1", $envContent);
            } else {
                // Agregar configuración al final del archivo
                $envContent .= "\n# Configuración de Arduino\nARDUINO_PORT=$puerto\nARDUINO_BAUDRATE=$baudrate\n";
            }

            // Guardar cambios
            file_put_contents($envFile, $envContent);

            // Actualizar variables de entorno en tiempo de ejecución
            $_ENV['ARDUINO_PORT'] = $puerto;
            $_ENV['ARDUINO_BAUDRATE'] = $baudrate;

            // Redireccionar a la página principal con mensaje de éxito
            header('Location: index.php?option=arduino&msg=config_ok');
            exit;
        } catch (Exception $e) {
            $error = "Error al guardar la configuración: " . $e->getMessage();
        }
    }
}

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Configuración de Arduino</h1>
        <div class="flex space-x-3">
            <a href="index.php?option=arduino/diagnostico" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                <i class="fas fa-stethoscope mr-2"></i>Diagnóstico
            </a>
            <a href="index.php?option=arduino" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p class="font-bold">Error</p>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensaje)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p class="font-bold">Éxito</p>
            <p><?php echo htmlspecialchars($mensaje); ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded-lg p-6">
        <form action="index.php?option=arduino/configurar" method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Selección del puerto -->
                <div>
                    <label for="puerto" class="block text-sm font-medium text-gray-700 mb-2">Puerto Serial</label>

                    <!-- Puertos detectados -->
                    <?php if (!empty($puertosDetectados)): ?>
                        <div class="mb-2">
                            <p class="text-sm text-gray-500 mb-2">Puertos detectados:</p>
                            <div class="flex flex-wrap gap-2 mb-3">
                                <?php foreach ($puertosDetectados as $puertoDetectado): ?>
                                    <label class="inline-flex items-center px-3 py-2 rounded-md border 
                                        <?php echo $puertoDetectado === $puertoActual ? 'bg-blue-50 border-blue-300' : 'bg-gray-50 border-gray-200'; ?>">
                                        <input type="radio" name="puerto" value="<?php echo htmlspecialchars($puertoDetectado); ?>"
                                            <?php echo $puertoDetectado === $puertoActual ? 'checked' : ''; ?>
                                            class="text-blue-600 focus:ring-blue-500 h-4 w-4 mr-2">
                                        <?php echo htmlspecialchars($puertoDetectado); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Puertos comunes -->
                    <div class="mb-2">
                        <p class="text-sm text-gray-500 mb-2">Puertos comunes:</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                            <?php
                            // Mostrar los puertos comunes que no estén ya en los puertos detectados
                            foreach ($puertosComunes[$sistemaOperativo] ?? [] as $puertoComun):
                                if (!in_array($puertoComun, $puertosDetectados ?? [])):
                            ?>
                                    <label class="inline-flex items-center px-3 py-2 rounded-md border 
                                    <?php echo $puertoComun === $puertoActual ? 'bg-blue-50 border-blue-300' : 'bg-gray-50 border-gray-200'; ?>">
                                        <input type="radio" name="puerto" value="<?php echo htmlspecialchars($puertoComun); ?>"
                                            <?php echo $puertoComun === $puertoActual ? 'checked' : ''; ?>
                                            class="text-blue-600 focus:ring-blue-500 h-4 w-4 mr-2">
                                        <?php echo htmlspecialchars($puertoComun); ?>
                                    </label>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    </div>

                    <!-- Puerto personalizado -->
                    <div>
                        <label class="inline-flex items-center px-3 py-2 rounded-md border mb-3
                            <?php echo !in_array($puertoActual, array_merge($puertosDetectados ?? [], $puertosComunes[$sistemaOperativo] ?? [])) ? 'bg-blue-50 border-blue-300' : 'bg-gray-50 border-gray-200'; ?>">
                            <input type="radio" name="puerto" value="personalizado"
                                <?php echo !in_array($puertoActual, array_merge($puertosDetectados ?? [], $puertosComunes[$sistemaOperativo] ?? [])) ? 'checked' : ''; ?>
                                class="text-blue-600 focus:ring-blue-500 h-4 w-4 mr-2">
                            Puerto personalizado
                        </label>

                        <input type="text" name="puertoPersonalizado" value="<?php echo !in_array($puertoActual, array_merge($puertosDetectados ?? [], $puertosComunes[$sistemaOperativo] ?? [])) ? htmlspecialchars($puertoActual) : ''; ?>"
                            placeholder="Ej: COM6, /dev/ttyUSB2"
                            class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>

                <!-- Selección del baudrate -->
                <div>
                    <label for="baudrate" class="block text-sm font-medium text-gray-700 mb-2">Velocidad (Baudrate)</label>
                    <select name="baudrate" id="baudrate" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <?php
                        // Opciones comunes de baudrate
                        $baudrates = [9600, 19200, 38400, 57600, 115200];
                        foreach ($baudrates as $baud):
                        ?>
                            <option value="<?php echo $baud; ?>" <?php echo $baud === $baudrateActual ? 'selected' : ''; ?>>
                                <?php echo $baud; ?> bps
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-2 text-sm text-gray-500">
                        Asegúrate de que coincida con la velocidad configurada en el sketch de Arduino
                    </p>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded transition-colors">
                    Guardar Configuración
                </button>
            </div>
        </form>
    </div>

    <!-- Tarjeta de instrucciones -->
    <div class="bg-blue-50 rounded-lg p-6 mt-8">
        <h2 class="text-lg font-semibold text-blue-800 mb-4">Solución a problemas comunes</h2>

        <div class="space-y-4">
            <div>
                <h3 class="font-medium text-blue-800 mb-2">Si el WebSocket no puede conectarse al Arduino:</h3>
                <ul class="list-disc list-inside text-blue-700 space-y-1 ml-2">
                    <li>Cierra cualquier otro programa que pueda estar usando el puerto (Arduino IDE, monitor serial)</li>
                    <li>Reinicia el Arduino presionando el botón RESET</li>
                    <li>Comprueba en el Administrador de Dispositivos de Windows que el Arduino está usando el puerto configurado</li>
                    <li>Reinicia el servidor WebSocket desde el panel de administración</li>
                    <li>Usar la herramienta de <a href="index.php?option=arduino/diagnostico" class="text-blue-600 underline">Diagnóstico</a> para detectar problemas</li>
                </ul>
            </div>

            <div>
                <h3 class="font-medium text-blue-800 mb-2">Recomendaciones generales:</h3>
                <ul class="list-disc list-inside text-blue-700 space-y-1 ml-2">
                    <li>Asegúrate de que el sketch de Arduino está enviando datos en el formato correcto (valores numéricos o JSON)</li>
                    <li>Verifica que el baudrate coincide con el configurado en el sketch (normalmente 9600 bps)</li>
                    <li>Si nada funciona, activa el modo simulación en la configuración de WebSocket</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Opciones de WebSocket -->
    <div class="bg-white shadow-md rounded-lg p-6 mt-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Configuración de WebSocket</h2>

        <form action="index.php?option=arduino/configurar" method="POST" class="space-y-6">
            <input type="hidden" name="config_type" value="websocket">

            <div>
                <label for="simulate" class="block text-sm font-medium text-gray-700 mb-2">Modo de datos</label>
                <div class="mt-2 space-y-4">
                    <div class="flex items-center">
                        <input id="simulate_false" name="simulate" type="radio" value="false"
                            <?php echo ($_ENV['ARDUINO_SIMULATE'] ?? 'false') !== 'true' ? 'checked' : ''; ?>
                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                        <label for="simulate_false" class="ml-3 block text-sm font-medium text-gray-700">
                            Intentar conectar al Arduino <span class="text-xs text-gray-500">(o simular si falla)</span>
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input id="simulate_true" name="simulate" type="radio" value="true"
                            <?php echo ($_ENV['ARDUINO_SIMULATE'] ?? 'false') === 'true' ? 'checked' : ''; ?>
                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                        <label for="simulate_true" class="ml-3 block text-sm font-medium text-gray-700">
                            Siempre simular datos <span class="text-xs text-gray-500">(no intentar conexión real)</span>
                        </label>
                    </div>
                </div>
                <p class="mt-2 text-sm text-gray-500">
                    Si eliges simular, no se intentará conectar con el Arduino real y se generarán datos de temperatura aleatorios.
                </p>
            </div>

            <div>
                <label for="websocket_port" class="block text-sm font-medium text-gray-700 mb-2">Puerto WebSocket</label>
                <input type="number" name="websocket_port" id="websocket_port"
                    value="<?php echo $_ENV['WEBSOCKET_PORT'] ?? '8080'; ?>"
                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                <p class="mt-2 text-sm text-gray-500">
                    Puerto para el servidor WebSocket (por defecto 8080). Si lo cambias, deberás reiniciar el servidor WebSocket.
                </p>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Guardar Configuración WebSocket
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Comprobar primero si los elementos existen antes de agregar listeners
        const selectPuerto = document.querySelector('input[name="puerto"][value="personalizado"]');
        const inputPuertoPersonalizado = document.querySelector('input[name="puertoPersonalizado"]');

        // Solo configurar el evento si ambos elementos existen
        if (selectPuerto && inputPuertoPersonalizado) {
            selectPuerto.addEventListener('change', function() {
                if (this.checked) {
                    inputPuertoPersonalizado.focus();
                }
            });
        }
    });
</script>