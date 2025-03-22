<?php

/**
 * listar_sensores.modulo.php
 * Vista que muestra el listado de sensores disponibles
 */

// Verificar si hay mensaje de éxito de configuración
$configOk = isset($_GET['msg']) && $_GET['msg'] === 'config_ok';

// Obtener configuración actual
$puerto = $_ENV['ARDUINO_PORT'] ?? 'No configurado';
$baudrate = intval($_ENV['ARDUINO_BAUDRATE']);

// Verificar estado del WebSocket
$websocketActivo = isset($websocketActivo) ? $websocketActivo : false;

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Monitoreo de Temperatura</h1>
        <div class="flex space-x-3">
            <a href="index.php?option=arduino/webserver" class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                <i class="fas fa-server mr-2"></i>WebSocket
            </a>
            <a href="index.php?option=arduino/configurar" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                <i class="fas fa-cog mr-2"></i>Configurar
            </a>
        </div>
    </div>

    <?php if ($configOk): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p>La configuración se ha guardado correctamente.</p>
        </div>
    <?php endif; ?>

    <!-- Información de conexión -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Información de Conexión</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-gray-700"><span class="font-medium">Puerto:</span> <span class="ml-2"><?php echo htmlspecialchars($puerto); ?></span></p>
            </div>
            <div>
                <p class="text-gray-700"><span class="font-medium">Velocidad:</span> <span class="ml-2"><?php echo htmlspecialchars($baudrate); ?> bps</span></p>
            </div>
            <div>
                <p class="text-gray-700">
                    <span class="font-medium">WebSocket:</span>
                    <?php if ($websocketActivo): ?>
                        <span class="ml-2 text-green-600">Activo</span>
                    <?php else: ?>
                        <span class="ml-2 text-yellow-600">Inactivo</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Tarjeta del sensor de temperatura -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
                    <i class="fas fa-thermometer-half text-red-500"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Sensor LM35</h3>
            </div>

            <p class="text-gray-600 mb-6">
                Sensor de temperatura de precisión con salida analógica lineal. Datos en tiempo real mediante WebSocket.
            </p>

            <div class="flex justify-end">
                <a href="index.php?option=arduino/mostrar" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                    <i class="fas fa-chart-line mr-2"></i>Ver Datos
                </a>
            </div>
        </div>
    </div>

    <!-- Información sobre WebSocket -->
    <div class="bg-blue-50 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-blue-800 mb-4">Información sobre WebSocket</h2>
        <p class="text-blue-700 mb-4">
            Este sistema utiliza WebSockets para la comunicación en tiempo real con el Arduino,
            proporcionando actualizaciones instantáneas de temperatura sin necesidad de recargar la página.
        </p>

        <?php if (!$websocketActivo): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mt-3 mb-4">
                <p class="font-semibold">El servidor WebSocket no está activo</p>
                <p>Se iniciará automáticamente al ver los datos del sensor. También puede administrarlo desde el panel de WebSocket.</p>
            </div>
        <?php endif; ?>

        <a href="index.php?option=arduino/webserver" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded transition-colors inline-flex items-center mt-2">
            <i class="fas fa-server mr-2"></i>Administrar WebSocket
        </a>
    </div>
</div>