<?php
/**
 * test-api.php
 * Script de diagnóstico API
 * Permite probar diferentes endpoints JSON sin usar el enrutamiento MVC
 * 
 * Uso: /scripts/test-api.php?endpoint=listarLogs
 * Endpoints disponibles: listarLogs, verLog, verificarWebSocket, iniciarWebSocket, etc.
 */

// Incluir el autoloader (ajustado para ubicación en scripts/)
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Cargar variables de entorno
if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
}

// Limpiar cualquier salida previa
while (ob_get_level()) {
    ob_end_clean();
}

// Configurar cabeceras para JSON por defecto
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: *');

try {
    // Obtener el endpoint solicitado
    $endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
    
    if (empty($endpoint)) {
        // Mostrar lista de endpoints disponibles
        echo json_encode([
            'endpoints' => [
                'listarLogs' => '/scripts/test-api.php?endpoint=listarLogs',
                'verLog' => '/scripts/test-api.php?endpoint=verLog&file=websocket.log',
                'verificarWebSocket' => '/scripts/test-api.php?endpoint=verificarWebSocket',
                'iniciarWebSocket' => '/scripts/test-api.php?endpoint=iniciarWebSocket',
                'detenerWebSocket' => '/scripts/test-api.php?endpoint=detenerWebSocket',
                'info' => '/scripts/test-api.php?endpoint=info'
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Crear instancia del controlador
    $controller = new \App\Controllers\ArduinoController();
    
    switch ($endpoint) {
        case 'listarLogs':
            // Mostrar lista de logs
            $controller->listarLogs();
            break;
            
        case 'verLog':
            // Verificar parámetro de archivo
            if (!isset($_GET['file'])) {
                throw new Exception('Falta parámetro: file');
            }
            
            // Redirigir al endpoint correcto
            header('Content-Type: text/plain; charset=UTF-8');
            $controller->verLog();
            break;
            
        case 'verificarWebSocket':
            // Verificar estado del WebSocket
            $controller->verificarWebSocketAjax();
            break;
            
        case 'iniciarWebSocket':
            // Iniciar el WebSocket
            $controller->iniciarWebSocketAjax();
            break;
            
        case 'detenerWebSocket':
            // Detener el WebSocket
            $controller->detenerWebSocketAjax();
            break;
            
        case 'info':
            // Información del sistema
            echo json_encode([
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'extensions' => get_loaded_extensions(),
                'arduino_port' => $_ENV['ARDUINO_PORT'] ?? 'No configurado',
                'arduino_baudrate' => $_ENV['ARDUINO_BAUDRATE'] ?? 'No configurado',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        default:
            throw new Exception('Endpoint desconocido: ' . $endpoint);
    }
} catch (Exception $e) {
    // Devolver error como JSON
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} 