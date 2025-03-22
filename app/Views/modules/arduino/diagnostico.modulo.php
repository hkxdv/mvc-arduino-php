<?php

/**
 * diagnostico.modulo.php
 * Módulo para diagnosticar problemas de conexión con Arduino a través del WebSocket
 */

// Detectar sistema operativo
$sistemaOperativo = 'Windows';
if (strtoupper(substr(PHP_OS, 0, 3)) === 'LIN') {
    $sistemaOperativo = 'Linux';
} elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'DAR') {
    $sistemaOperativo = 'Mac';
}

// Configuración actual
$puerto = $_ENV['ARDUINO_PORT'];
$baudrate = intval($_ENV['ARDUINO_BAUDRATE']);
$modoSimulacion = ($_ENV['ARDUINO_SIMULATE'] ?? 'false') === 'true';
$puertoWebSocket = $_ENV['WEBSOCKET_PORT'] ?? '8080';

// Verificar puertos disponibles
$puertosDisponibles = [];

if ($sistemaOperativo === 'Windows') {
    // En Windows, intentamos listar los puertos COM
    exec('mode', $salida);
    foreach ($salida as $linea) {
        if (strpos($linea, 'COM') !== false) {
            preg_match('/COM\d+/', $linea, $coincidencias);
            if (!empty($coincidencias)) {
                $puertosDisponibles[] = $coincidencias[0];
            }
        }
    }

    // Si no se encontraron puertos, intentar con otra alternativa
    if (empty($puertosDisponibles)) {
        // Lista de posibles puertos COM en Windows
        for ($i = 1; $i <= 20; $i++) {
            $puertosDisponibles[] = "COM{$i}";
        }
    }
} elseif ($sistemaOperativo === 'Linux') {
    // En Linux, buscamos en /dev
    exec('ls /dev/tty* | grep -E "ttyUSB|ttyACM"', $puertosDisponibles);
    if (empty($puertosDisponibles)) {
        $puertosDisponibles = glob('/dev/ttyUSB*');
        $puertosDisponibles = array_merge($puertosDisponibles, glob('/dev/ttyACM*'));
    }
} else {
    // En Mac, buscamos en /dev
    exec('ls /dev/cu.* | grep -E "usbmodem|usbserial"', $puertosDisponibles);
    if (empty($puertosDisponibles)) {
        $puertosDisponibles = glob('/dev/cu.usbmodem*');
        $puertosDisponibles = array_merge($puertosDisponibles, glob('/dev/cu.usbserial*'));
    }
}

// Verificar si el WebSocket está activo
$websocketActivo = \App\Models\ArduinoSensorModel::verificarServidorWebSocket();

// Verificar extensiones de PHP
$extensionesRecomendadas = [];
if (function_exists('dio_open')) {
    $extensionesRecomendadas[] = ['dio', true, 'Extensión Direct IO disponible'];
} else {
    $extensionesRecomendadas[] = ['dio', false, 'Se recomienda habilitar la extensión Direct IO en php.ini'];
}

if (function_exists('exec')) {
    $extensionesRecomendadas[] = ['exec', true, 'Función exec disponible'];
} else {
    $extensionesRecomendadas[] = ['exec', false, 'Se recomienda habilitar la función exec en php.ini'];
}

if (function_exists('fopen') && function_exists('stream_set_timeout')) {
    $extensionesRecomendadas[] = ['stream', true, 'Funciones de stream disponibles'];
} else {
    $extensionesRecomendadas[] = ['stream', false, 'Se requieren fopen y stream_set_timeout para la comunicación serial'];
}

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Diagnóstico de Conexión Arduino</h1>
        <div class="flex space-x-3">
            <a href="index.php?option=arduino/configurar" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                <i class="fas fa-cog mr-2"></i>Configurar
            </a>
            <a href="index.php?option=arduino" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>
    </div>

    <!-- Tarjeta de información del sistema -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Información del Sistema</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-gray-700"><span class="font-medium">Sistema Operativo:</span> <span class="ml-2"><?php echo htmlspecialchars(PHP_OS); ?></span></p>
            </div>
            <div>
                <p class="text-gray-700"><span class="font-medium">Versión PHP:</span> <span class="ml-2"><?php echo htmlspecialchars(PHP_VERSION); ?></span></p>
            </div>
            <div>
                <p class="text-gray-700"><span class="font-medium">Servidor Web:</span> <span class="ml-2"><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido'); ?></span></p>
            </div>
            <div>
                <p class="text-gray-700"><span class="font-medium">Usuario:</span> <span class="ml-2"><?php echo htmlspecialchars(function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user()); ?></span></p>
            </div>
        </div>
    </div>

    <!-- Verificación de WebSocket -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                    <i class="fas fa-server text-blue-500"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Estado del Servidor WebSocket</h3>
            </div>

            <div class="flex items-center mb-4" id="ws-status-container">
                <?php if ($websocketActivo): ?>
                    <span class="inline-block w-4 h-4 mr-2 bg-green-500 rounded-full"></span>
                    <span class="text-green-700 font-medium">Servidor WebSocket activo</span>
                <?php else: ?>
                    <span class="inline-block w-4 h-4 mr-2 bg-red-500 rounded-full"></span>
                    <span class="text-red-700 font-medium">Servidor WebSocket inactivo</span>
                <?php endif; ?>
            </div>

            <div class="mt-4 bg-gray-50 p-4 rounded-lg">
                <h4 class="font-medium text-gray-800 mb-2">Información del WebSocket:</h4>
                <ul class="list-disc list-inside pl-4 text-gray-700 space-y-2">
                    <li>URL WebSocket: <code class="bg-gray-100 px-2 py-1 rounded font-mono"><?php echo htmlspecialchars(\App\Models\ArduinoSensorModel::getWebSocketUrl()); ?></code></li>
                    <li>Puerto configurado en servidor WebSocket: <code class="bg-gray-100 px-2 py-1 rounded font-mono"><?php echo htmlspecialchars($puerto); ?></code></li>
                    <li>Baudrate: <code class="bg-gray-100 px-2 py-1 rounded font-mono"><?php echo htmlspecialchars($baudrate); ?></code></li>
                    <li>Modo simulación: <code class="bg-gray-100 px-2 py-1 rounded font-mono"><?php echo $modoSimulacion ? 'Activado' : 'Desactivado'; ?></code></li>
                    <li>Puerto WebSocket: <code class="bg-gray-100 px-2 py-1 rounded font-mono"><?php echo htmlspecialchars($puertoWebSocket); ?></code></li>
                </ul>
            </div>

            <div class="mt-6 flex space-x-4">
                <button id="btn-start-ws" class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                    <i class="fas fa-play mr-2"></i>Iniciar Servidor WebSocket
                </button>
                <button id="btn-stop-ws" class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                    <i class="fas fa-stop mr-2"></i>Detener Servidor WebSocket
                </button>
                <button id="btn-test-ws" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                    <i class="fas fa-vial mr-2"></i>Probar Conexión
                </button>
            </div>
        </div>
    </div>

    <!-- Tarjeta de configuración Arduino -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-8">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                <i class="fas fa-microchip text-green-500"></i>
            </div>
            <h2 class="text-xl font-semibold text-gray-800">Configuración de Arduino</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <p class="text-gray-700"><span class="font-medium">Puerto configurado:</span> <span class="ml-2"><?php echo htmlspecialchars($puerto); ?></span></p>
            </div>
            <div>
                <p class="text-gray-700"><span class="font-medium">Baudrate:</span> <span class="ml-2"><?php echo htmlspecialchars($baudrate); ?> bps</span></p>
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

        <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700 rounded-r-md">
            <p class="font-bold">Nota importante</p>
            <p>El modelo de Arduino no se conecta directamente al puerto serial. La comunicación se realiza exclusivamente a través del servidor WebSocket que debe estar activo.</p>
            <?php if (!$websocketActivo): ?>
                <p class="mt-2 font-medium text-blue-800">El servidor WebSocket no está activo. Inicie el servidor para habilitar la comunicación.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tarjeta de puertos disponibles -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-8">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                <i class="fas fa-plug text-purple-500"></i>
            </div>
            <h2 class="text-xl font-semibold text-gray-800">Puertos Potencialmente Disponibles</h2>
        </div>

        <?php if (empty($puertosDisponibles)): ?>
            <p class="text-gray-500">No se encontraron puertos seriales en el sistema.</p>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                <?php foreach ($puertosDisponibles as $puertoDisponible): ?>
                    <div class="p-3 border rounded-md 
                        <?php echo $puertoDisponible === $puerto ? 'bg-blue-100 border-blue-500' : 'bg-gray-50 border-gray-200'; ?>">
                        <?php echo htmlspecialchars($puertoDisponible); ?>
                        <?php if ($puertoDisponible === $puerto): ?>
                            <span class="ml-2 inline-block w-2 h-2 bg-blue-500 rounded-full"></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="index.php?option=arduino/configurar" class="text-blue-600 hover:text-blue-800 transition-colors inline-flex items-center">
                <i class="fas fa-cog mr-2"></i>Cambiar configuración de puerto
            </a>
        </div>
    </div>

    <!-- Extensiones de PHP -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-8">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                <i class="fas fa-puzzle-piece text-yellow-500"></i>
            </div>
            <h2 class="text-xl font-semibold text-gray-800">Extensiones de PHP Recomendadas</h2>
        </div>

        <div class="space-y-3">
            <?php foreach ($extensionesRecomendadas as $extension): ?>
                <div class="flex items-center p-3 border rounded-md <?php echo $extension[1] ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200'; ?>">
                    <?php if ($extension[1]): ?>
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>
                    <?php endif; ?>
                    <div>
                        <p class="<?php echo $extension[1] ? 'text-green-800' : 'text-yellow-800'; ?> font-medium"><?php echo htmlspecialchars($extension[0]); ?></p>
                        <p class="text-sm <?php echo $extension[1] ? 'text-green-600' : 'text-yellow-600'; ?>"><?php echo htmlspecialchars($extension[2]); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Soluciones recomendadas -->
    <div class="bg-blue-50 rounded-lg p-6 mb-8">
        <h2 class="text-lg font-semibold text-blue-800 mb-4">Soluciones Recomendadas</h2>

        <?php if (!$websocketActivo): ?>
            <div class="space-y-4">
                <div class="bg-white p-4 rounded-md shadow-sm">
                    <h3 class="font-medium text-blue-700 mb-2">Iniciar el servidor WebSocket</h3>
                    <p class="text-gray-600 mb-2">El servidor WebSocket no está activo. Puede iniciarlo haciendo clic en el botón "Iniciar Servidor WebSocket" arriba.</p>
                    <div class="mt-2">
                        <button id="btn-start-ws-alt" class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded transition-colors flex items-center">
                            <i class="fas fa-play mr-2"></i>Iniciar Servidor WebSocket
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="space-y-4 mt-4">
            <div class="bg-white p-4 rounded-md shadow-sm">
                <h3 class="font-medium text-blue-700 mb-2">Verificar configuración de puerto</h3>
                <p class="text-gray-600 mb-2">Asegúrese de que el puerto configurado (<?php echo htmlspecialchars($puerto); ?>) es el correcto para su Arduino.</p>
                <div class="mt-2">
                    <a href="index.php?option=arduino/configurar" class="text-blue-600 hover:text-blue-800 transition-colors inline-flex items-center">
                        <i class="fas fa-cog mr-2"></i>Cambiar configuración
                    </a>
                </div>
            </div>

            <div class="bg-white p-4 rounded-md shadow-sm">
                <h3 class="font-medium text-blue-700 mb-2">Activar modo simulación</h3>
                <p class="text-gray-600 mb-2">Si no tiene un Arduino conectado, puede activar el modo simulación para probar la aplicación.</p>
                <div class="mt-2">
                    <a href="index.php?option=arduino/configurar" class="text-blue-600 hover:text-blue-800 transition-colors inline-flex items-center">
                        <i class="fas fa-cog mr-2"></i>Configurar modo simulación
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Registro de Errores -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-8">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
                <i class="fas fa-exclamation-circle text-red-500"></i>
            </div>
            <h2 class="text-xl font-semibold text-gray-800">Registro de Errores</h2>
        </div>

        <?php
        $logArduino = dirname(dirname(dirname(dirname(__DIR__)))) . '/logs/arduino_debug.log';
        $logWebSocket = dirname(dirname(dirname(dirname(__DIR__)))) . '/logs/websocket_debug.log';
        $hayErrores = false;
        ?>

        <div class="space-y-6">
            <!-- Log de Arduino -->
            <div>
                <h3 class="font-medium text-gray-800 mb-2">Log de Arduino:</h3>
                <?php if (file_exists($logArduino) && filesize($logArduino) > 0): ?>
                    <?php $hayErrores = true; ?>
                    <div class="bg-gray-100 p-3 rounded-md">
                        <pre class="text-sm font-mono text-gray-800 overflow-x-auto max-h-40"><?php
                                                                                                $contenido = file_get_contents($logArduino);
                                                                                                // Mostrar solo las últimas 20 líneas
                                                                                                $lineas = explode("\n", $contenido);
                                                                                                $lineas = array_slice($lineas, -20);
                                                                                                echo htmlspecialchars(implode("\n", $lineas));
                                                                                                ?></pre>
                    </div>
                    <div class="mt-2 flex justify-end">
                        <button id="btn-clear-arduino-log" class="text-sm text-red-600 hover:text-red-800 transition-colors">
                            Limpiar log
                        </button>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">No hay errores registrados en el log de Arduino.</p>
                <?php endif; ?>
            </div>

            <!-- Log de WebSocket -->
            <div>
                <h3 class="font-medium text-gray-800 mb-2">Log de WebSocket:</h3>
                <?php if (file_exists($logWebSocket) && filesize($logWebSocket) > 0): ?>
                    <?php $hayErrores = true; ?>
                    <div class="bg-gray-100 p-3 rounded-md">
                        <pre class="text-sm font-mono text-gray-800 overflow-x-auto max-h-40"><?php
                                                                                                $contenido = file_get_contents($logWebSocket);
                                                                                                // Mostrar solo las últimas 20 líneas
                                                                                                $lineas = explode("\n", $contenido);
                                                                                                $lineas = array_slice($lineas, -20);
                                                                                                echo htmlspecialchars(implode("\n", $lineas));
                                                                                                ?></pre>
                    </div>
                    <div class="mt-2 flex justify-end">
                        <button id="btn-clear-ws-log" class="text-sm text-red-600 hover:text-red-800 transition-colors">
                            Limpiar log
                        </button>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">No hay errores registrados en el log de WebSocket.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$hayErrores): ?>
            <div class="mt-4 bg-green-50 p-4 rounded-md text-green-700">
                <p><i class="fas fa-check-circle mr-2"></i>No se encontraron errores en los logs.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Referencias a los botones
        const btnStartWs = document.getElementById('btn-start-ws');
        const btnStopWs = document.getElementById('btn-stop-ws');
        const btnTestWs = document.getElementById('btn-test-ws');
        const wsStatusContainer = document.getElementById('ws-status-container');

        // Asegurar que la función mostrarNotificacion siempre exista
        if (typeof window.mostrarNotificacion !== 'function') {
            // Función para mostrar notificaciones
            window.mostrarNotificacion = function(mensaje, tipo) {
                // Crear elemento de notificación
                const notificacion = document.createElement('div');
                notificacion.className = 'fixed bottom-4 right-4 p-4 rounded-lg shadow-lg max-w-md z-50 ';

                // Aplicar estilo según tipo
                switch (tipo) {
                    case 'success':
                        notificacion.className += 'bg-green-100 border-l-4 border-green-500 text-green-700';
                        break;
                    case 'error':
                        notificacion.className += 'bg-red-100 border-l-4 border-red-500 text-red-700';
                        break;
                    case 'info':
                        notificacion.className += 'bg-blue-100 border-l-4 border-blue-500 text-blue-700';
                        break;
                    default:
                        notificacion.className += 'bg-gray-100 border-l-4 border-gray-500 text-gray-700';
                }

                // Contenido de la notificación
                notificacion.innerHTML = `
                <div class="flex items-center">
                    <div class="ml-3">
                        <p class="text-sm font-medium">${mensaje}</p>
                    </div>
                    <button class="ml-auto text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Cerrar</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                `;

                // Agregar a la página
                document.body.appendChild(notificacion);

                // Configurar cierre al hacer clic
                notificacion.querySelector('button').addEventListener('click', function() {
                    document.body.removeChild(notificacion);
                });

                // Eliminar automáticamente después de 5 segundos
                setTimeout(() => {
                    if (document.body.contains(notificacion)) {
                        document.body.removeChild(notificacion);
                    }
                }, 5000);
            };

            console.log("Función mostrarNotificacion ha sido creada");
        }

        // Función para actualizar el estado del WebSocket en la interfaz
        function actualizarEstadoWebSocket(activo) {
            wsStatusContainer.innerHTML = activo ?
                '<span class="inline-block w-4 h-4 mr-2 bg-green-500 rounded-full"></span>' +
                '<span class="text-green-700 font-medium">Servidor WebSocket activo</span>' :
                '<span class="inline-block w-4 h-4 mr-2 bg-red-500 rounded-full"></span>' +
                '<span class="text-red-700 font-medium">Servidor WebSocket inactivo</span>';
        }

        // Actualizar función de comprobación con fallback directo
        function comprobarEstadoWebSocket() {
            const wsStatusContainer = document.getElementById('ws-status-container');
            if (wsStatusContainer) {
                wsStatusContainer.innerHTML = '<span class="inline-block w-4 h-4 mr-2 bg-yellow-500 rounded-full"></span>' +
                    '<span class="text-yellow-700 font-medium">Verificando estado del WebSocket...</span>';
            }

            // Comprobar estado del WebSocket directamente (forma alternativa)
            function comprobarWebSocketDirectamente() {
                console.log("Comprobando WebSocket directamente...");
                try {
                    const tempSocket = new WebSocket('<?php echo htmlspecialchars(\App\Models\ArduinoSensorModel::getWebSocketUrl()); ?>');

                    tempSocket.onopen = function() {
                        console.log("Conexión directa exitosa");
                        actualizarEstadoWebSocket(true);
                        tempSocket.close();
                    };

                    tempSocket.onerror = function() {
                        console.log("Conexión directa fallida");
                        actualizarEstadoWebSocket(false);
                    };

                    // Establecer un timeout para cerrar la conexión si no se conecta en 5 segundos
                    setTimeout(function() {
                        if (tempSocket.readyState !== WebSocket.OPEN) {
                            console.log("Timeout de conexión directa");
                            tempSocket.close();
                            actualizarEstadoWebSocket(false);
                        }
                    }, 5000);
                } catch (error) {
                    console.error("Error en conexión directa:", error);
                    actualizarEstadoWebSocket(false);
                }
            }

            // Primero intentar con el método AJAX
            fetch('index.php?option=arduino/verificarWebSocketAjax&t=' + Date.now(), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    },
                    cache: 'no-store'
                })
                .then(response => {
                    console.log('Estado respuesta:', response.status, response.statusText);
                    console.log('Tipo contenido:', response.headers.get('content-type'));

                    // Verificar si la respuesta es correcta
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    // Verificar que es JSON antes de parsear
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('La respuesta no es JSON válido: ' + contentType);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('WebSocket status response:', data);
                    actualizarEstadoWebSocket(data.activo);
                })
                .catch(error => {
                    console.error('Error al verificar estado del WebSocket:', error);
                    // Si falla el método AJAX, intentar verificación directa
                    console.log('Fallando al método directo...');
                    comprobarWebSocketDirectamente();

                    // Log adicional para depuración
                    console.log('URL solicitada:', 'index.php?option=arduino/verificarWebSocketAjax');

                    if (wsStatusContainer) {
                        wsStatusContainer.innerHTML = '<span class="inline-block w-4 h-4 mr-2 bg-yellow-500 rounded-full"></span>' +
                            '<span class="text-yellow-700 font-medium">Verificando mediante conexión directa...</span>';
                    }
                });
        }

        // Función para iniciar el WebSocket
        btnStartWs.addEventListener('click', function() {
            btnStartWs.disabled = true;
            btnStartWs.innerHTML = '<span class="inline-block w-4 h-4 mr-2 border-t-2 border-white border-r-2 rounded-full animate-spin"></span>Iniciando...';

            // Intentar con AJAX primero
            fetch('index.php?option=arduino/iniciarWebSocketAjax&t=' + Date.now(), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    },
                    cache: 'no-store'
                })
                .then(response => {
                    // Verificar si la respuesta es correcta
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    // Verificar que es JSON antes de parsear
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('La respuesta no es JSON válido: ' + contentType);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('WebSocket start response:', data);
                    if (data.success) {
                        actualizarEstadoWebSocket(true);
                        mostrarNotificacion('Servidor WebSocket iniciado correctamente', 'success');
                    } else {
                        mostrarNotificacion('Error al iniciar el servidor WebSocket: ' + (data.message || 'Error desconocido'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error al iniciar WebSocket:', error);
                    mostrarNotificacion('Error al comunicarse con el servidor: ' + error.message, 'error');

                    // Intentar recarga de página como último recurso
                    if (confirm('Hubo un error al iniciar el WebSocket. ¿Desea recargar la página e intentar nuevamente?')) {
                        window.location.href = 'index.php?option=arduino/iniciarWebSocket';
                    }
                })
                .finally(() => {
                    btnStartWs.disabled = false;
                    btnStartWs.innerHTML = 'Iniciar Servidor WebSocket';
                    // Verificar estado después de un momento
                    setTimeout(comprobarEstadoWebSocket, 2000);
                });
        });

        // Función para detener el WebSocket
        btnStopWs.addEventListener('click', function() {
            btnStopWs.disabled = true;
            btnStopWs.innerHTML = '<span class="inline-block w-4 h-4 mr-2 border-t-2 border-white border-r-2 rounded-full animate-spin"></span>Deteniendo...';

            fetch('index.php?option=arduino/detenerWebSocketAjax&t=' + Date.now(), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    },
                    cache: 'no-store'
                })
                .then(response => {
                    // Verificar si la respuesta es correcta
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    // Verificar que es JSON antes de parsear
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('La respuesta no es JSON válido: ' + contentType);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('WebSocket stop response:', data);
                    if (data.success) {
                        actualizarEstadoWebSocket(false);
                        mostrarNotificacion('Servidor WebSocket detenido correctamente', 'success');
                    } else {
                        mostrarNotificacion('Error al detener el servidor WebSocket: ' + (data.message || 'Error desconocido'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error al detener WebSocket:', error);
                    mostrarNotificacion('Error al comunicarse con el servidor: ' + error.message, 'error');

                    // Intentar recarga de página como último recurso
                    if (confirm('Hubo un error al detener el WebSocket. ¿Desea recargar la página e intentar nuevamente?')) {
                        window.location.href = 'index.php?option=arduino/detenerWebSocket';
                    }
                })
                .finally(() => {
                    btnStopWs.disabled = false;
                    btnStopWs.innerHTML = 'Detener Servidor WebSocket';
                    // Verificar estado después de un momento
                    setTimeout(comprobarEstadoWebSocket, 2000);
                });
        });

        // Manejador para probar conexión WebSocket
        btnTestWs.addEventListener('click', function() {
            btnTestWs.disabled = true;
            btnTestWs.innerHTML = '<span class="inline-block w-4 h-4 mr-2 border-t-2 border-white border-r-2 rounded-full animate-spin"></span>Probando...';

            // URL del WebSocket
            const wsUrl = '<?php echo htmlspecialchars(\App\Models\ArduinoSensorModel::getWebSocketUrl()); ?>';

            // Probar conexión
            try {
                const socket = new WebSocket(wsUrl);

                socket.onopen = function() {
                    console.log('Conexión WebSocket establecida');
                    mostrarNotificacion('Conexión WebSocket establecida correctamente', 'success');
                    socket.send('ping');
                    setTimeout(() => socket.close(), 1000);
                };

                socket.onmessage = function(event) {
                    console.log('Mensaje recibido:', event.data);
                    mostrarNotificacion('Respuesta recibida: ' + event.data, 'info');
                };

                socket.onerror = function(error) {
                    console.error('Error en la conexión WebSocket', error);
                    mostrarNotificacion('Error al conectar con el servidor WebSocket', 'error');
                };

                socket.onclose = function() {
                    console.log('Conexión WebSocket cerrada');
                };
            } catch (error) {
                console.error('Error al crear WebSocket:', error);
                mostrarNotificacion('Error al crear la conexión WebSocket: ' + error.message, 'error');
            }

            setTimeout(() => {
                btnTestWs.disabled = false;
                btnTestWs.innerHTML = 'Probar Conexión';
            }, 2000);
        });

        // Comprobar estado al cargar la página
        setTimeout(comprobarEstadoWebSocket, 1000);

        // Comprobar periódicamente el estado
        setInterval(comprobarEstadoWebSocket, 30000);
    });
</script>