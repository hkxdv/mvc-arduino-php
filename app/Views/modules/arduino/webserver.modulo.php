<?php

/**
 * webserver.modulo.php
 * Módulo para administrar el servidor WebSocket para Arduino
 * Permite iniciar, detener y ver el estado del servidor WebSocket
 */

// Verificar si el WebSocket está activo
$websocketActivo = \App\Models\ArduinoSensorModel::verificarServidorWebSocket();

// Obtener configuración actual
$puerto = $_ENV['ARDUINO_PORT'] ?? 'No configurado';
$baudrate = intval($_ENV['ARDUINO_BAUDRATE'] ?? 9600);
$modoSimulacion = ($_ENV['ARDUINO_SIMULATE'] ?? 'false') === 'true';
$puertoWebSocket = $_ENV['WEBSOCKET_PORT'] ?? '8080';

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Administrador de Servidor WebSocket</h1>
        <div class="flex space-x-3">
            <a href="index.php?option=arduino/diagnostico" class="bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                <i class="fas fa-stethoscope mr-2"></i>Diagnóstico
            </a>
            <a href="index.php?option=arduino" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>
    </div>

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
                    <span id="ws-status-badge" class="ml-2 <?php echo $websocketActivo ? 'text-green-600' : 'text-yellow-600'; ?>">
                        <?php echo $websocketActivo ? 'Activo' : 'Inactivo'; ?>
                    </span>
                </p>
            </div>
            <div>
                <p class="text-gray-700"><span class="font-medium">URL WebSocket:</span> <code class="bg-gray-100 px-2 py-1 rounded font-mono text-sm"><?php echo htmlspecialchars(\App\Models\ArduinoSensorModel::getWebSocketUrl()); ?></code></p>
            </div>
            <div>
                <p class="text-gray-700"><span class="font-medium">Puerto WebSocket:</span> <span class="ml-2"><?php echo htmlspecialchars($puertoWebSocket); ?></span></p>
            </div>
            <div>
                <p class="text-gray-700"><span class="font-medium">Modo simulación:</span>
                    <?php if ($modoSimulacion): ?>
                        <span class="ml-2 bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">Activado</span>
                    <?php else: ?>
                        <span class="ml-2 bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium">Desactivado</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Estado del servidor y controles -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                    <i class="fas fa-server text-blue-500"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Estado del Servidor WebSocket</h3>
            </div>

            <div class="flex items-center mb-4" id="server-status">
                <?php if ($websocketActivo): ?>
                    <span class="inline-block w-4 h-4 mr-2 bg-green-500 rounded-full"></span>
                    <span class="text-green-700 font-medium">Servidor WebSocket activo</span>
                <?php else: ?>
                    <span class="inline-block w-4 h-4 mr-2 bg-red-500 rounded-full"></span>
                    <span class="text-red-700 font-medium">Servidor WebSocket inactivo</span>
                <?php endif; ?>
            </div>

            <div class="mt-6 flex space-x-4">
                <button id="iniciar-server" class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                    <i class="fas fa-play mr-2"></i>Iniciar Servidor WebSocket
                </button>
                <button id="detener-server" class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                    <i class="fas fa-stop mr-2"></i>Detener Servidor WebSocket
                </button>
                <button id="refrescar-estado" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar Estado
                </button>
            </div>
        </div>
    </div>

    <!-- Registros del servidor -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                    <i class="fas fa-list-alt text-purple-500"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Registros del Servidor</h3>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-400 text-blue-700 p-4 rounded-r-md mb-4">
                <p><i class="fas fa-info-circle mr-2"></i>Los registros del servidor se almacenan en el directorio <code class="bg-blue-100 px-2 py-0.5 rounded text-sm font-mono">logs/</code></p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Archivo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamaño</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Última modificación</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="logs-table" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                <span class="inline-block w-4 h-4 mr-2 border-t-2 border-blue-500 border-r-2 rounded-full animate-spin"></span>
                                Cargando registros...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Instrucciones -->
    <div class="bg-blue-50 rounded-lg p-6 mb-8">
        <h2 class="text-lg font-semibold text-blue-800 mb-4">Instrucciones para usar el servidor WebSocket</h2>

        <div class="space-y-4">
            <div class="bg-white p-4 rounded-md shadow-sm">
                <h3 class="font-medium text-blue-700 mb-2">Iniciar el servidor WebSocket</h3>
                <p class="text-gray-600 mb-2">El servidor WebSocket es necesario para la comunicación en tiempo real con el Arduino. Asegúrate de que el puerto configurado sea correcto.</p>
                <ol class="list-decimal list-inside space-y-2 text-gray-700 ml-2">
                    <li>Verifica que tu Arduino esté conectado al puerto <strong><?php echo htmlspecialchars($puerto); ?></strong></li>
                    <li>Haz clic en el botón "Iniciar Servidor WebSocket" para comenzar la comunicación</li>
                    <li>Cuando el servidor esté activo, el indicador cambiará a color verde</li>
                </ol>
            </div>

            <div class="bg-white p-4 rounded-md shadow-sm">
                <h3 class="font-medium text-blue-700 mb-2">Monitoreo y visualización de datos</h3>
                <p class="text-gray-600 mb-2">Una vez que el servidor esté activo, puedes acceder a la página de sensores para ver los datos en tiempo real.</p>
                <div class="mt-2">
                    <a href="index.php?option=arduino/mostrar" class="text-blue-600 hover:text-blue-800 transition-colors inline-flex items-center">
                        <i class="fas fa-chart-line mr-2"></i>Ver página de monitoreo de temperatura
                    </a>
                </div>
            </div>

            <?php if (!$websocketActivo): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mt-3">
                    <p class="font-semibold"><i class="fas fa-exclamation-triangle mr-2"></i>El servidor WebSocket no está activo</p>
                    <p>Para ver datos en tiempo real, es necesario iniciar el servidor WebSocket.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Establecer el atributo de datos para el cargador de módulos
        document.body.dataset.page = 'arduino/webserver';
    });
</script>