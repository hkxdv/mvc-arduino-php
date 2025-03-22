<?php

/**
 * mostrar_sensor.modulo.php
 * Vista para mostrar datos del sensor de temperatura usando WebSockets
 */

// Cargar valores de configuración
$puerto = $_ENV['ARDUINO_PORT'];
$baudrate = intval($_ENV['ARDUINO_BAUDRATE']);

// Verificar estado del WebSocket
$websocketActivo = isset($websocketActivo) ? $websocketActivo : false;

// URL del servidor WebSocket
$websocketUrl = 'ws://localhost:8080';
if (isset($_SERVER['SERVER_NAME'])) {
    // Si estamos en un servidor web, usar el mismo dominio
    $websocketUrl = 'ws://' . $_SERVER['SERVER_NAME'] . ':8080';
}

// Establecer variable explícita para indicar que estamos utilizando WebSocket
$usingWebSocket = true;

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Datos de Temperatura</h1>
        <div class="flex space-x-3">
            <button id="btn-reconectar" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                <i class="fas fa-sync-alt mr-2"></i>Reconectar
            </button>
            <a href="index.php?option=arduino/webserver" class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                <i class="fas fa-server mr-2"></i>WebSocket
            </a>
            <a href="index.php?option=arduino" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>
    </div>

    <!-- Estado WebSocket -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6 p-4 flex justify-between items-center">
        <div class="flex items-center">
            <span class="font-medium mr-2">Estado WebSocket:</span>
            <span id="ws-status" class="text-yellow-500">Conectando...</span>
        </div>
        <div>
            <span class="text-sm text-gray-500 mr-4">
                Puerto en servidor: <?php echo htmlspecialchars($puerto); ?> - Baudrate: <?php echo htmlspecialchars($baudrate); ?>
            </span>
            <span class="text-xs px-2 py-1 rounded" id="ws-data-source">
                Origen: Esperando datos...
            </span>
        </div>
    </div>

    <!-- Tarjeta principal de temperatura -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Sensor de Temperatura LM35</h2>
                <span class="text-sm text-gray-500" id="timestamp-display"><?php echo date('d/m/Y H:i:s'); ?></span>
            </div>

            <div id="temperature-display" class="flex flex-col items-center justify-center py-8 bg-gray-50 rounded-lg">
                <div class="text-2xl text-gray-400 flex items-center">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Conectando con WebSocket...
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de temperatura -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Historial de Temperatura</h2>
            <div id="temperature-chart-container" class="w-full h-64">
                <canvas id="temperature-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Monitor Serial -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Monitor de Datos</h2>
                <div class="flex items-center">
                    <label for="auto-scroll" class="mr-2 text-sm text-gray-600">Auto-scroll</label>
                    <input type="checkbox" id="auto-scroll" class="form-checkbox h-4 w-4 text-blue-600" checked>
                </div>
            </div>
            <div class="relative">
                <pre id="monitor-serial" class="text-sm font-mono bg-gray-900 text-green-400 p-4 rounded-lg h-64 overflow-y-auto">Esperando conexión WebSocket...</pre>
                <div class="absolute bottom-2 right-2">
                    <button id="btn-clear-monitor" class="bg-gray-700 text-white text-xs py-1 px-2 rounded hover:bg-gray-600 transition-colors">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    // Este script será reemplazado por el módulo ES6 arduino_websocket.js
    document.addEventListener('DOMContentLoaded', function() {
        // Indicar que el módulo JavaScript manejará esta página
        console.log('Esperando módulo arduino_websocket.js para gestionar esta página...');

        // Establecer el atributo de datos para el cargador de módulos
        document.body.dataset.page = 'arduino/mostrar';

        // No inicializar el gráfico aquí, dejarlo para el módulo arduino_websocket.js
    });
</script>