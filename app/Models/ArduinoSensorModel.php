<?php

/**
 * ArduinoSensorModel.php
 * Modelo para manejar la comunicación con sensores Arduino
 * Incluye soporte para WebSockets
 */

namespace App\Models;

use Exception;

/**
 * Clase para gestionar la comunicación con sensores Arduino
 * 
 * @package App\Models
 */
class ArduinoSensorModel
{
    /**
     * Puerto serial al que está conectado el Arduino
     * @var string
     */
    private static $puerto;

    /**
     * Velocidad de comunicación (baudrate)
     * @var int
     */
    private static $baudrate;

    /**
     * Timeout para la lectura del puerto en segundos
     * @var int
     */
    private static $timeout = 2;

    /**
     * URL del servidor WebSocket
     * @var string
     */
    private static $websocketUrl = 'ws://localhost:8080';

    /**
     * Bandera para modo simulación
     * @var bool
     */
    private static $modoSimulacion = false;

    /**
     * Última lectura recibida del WebSocket
     * @var array
     */
    private static $ultimaLectura = null;

    /**
     * Constructor de la clase
     * 
     * @param string $puerto Puerto COM (en Windows) o ruta al dispositivo (en Linux/Mac)
     * @param int $baudrate Velocidad de comunicación
     */
    public function __construct(string $puerto, int $baudrate = 9600)
    {
        self::$puerto = $puerto;
        self::$baudrate = $baudrate;
    }

    /**
     * Configura la URL del servidor WebSocket
     * 
     * @param string $url URL del servidor WebSocket
     * @return void
     */
    public static function configurarWebSocket(string $url): void
    {
        self::$websocketUrl = $url;
    }

    /**
     * Obtiene la URL del servidor WebSocket
     * 
     * @return string URL del servidor WebSocket
     */
    public static function getWebSocketUrl(): string
    {
        return self::$websocketUrl;
    }

    /**
     * Verifica si el servidor WebSocket está activo
     * 
     * @return bool True si el servidor está activo
     */
    public static function verificarServidorWebSocket(): bool
    {
        try {
            // Verificar de múltiples formas

            // 1. Comprobar archivo PID
            $pidFile = dirname(dirname(__DIR__)) . '/logs/websocket_server.pid';
            if (!file_exists($pidFile)) {
                return false;
            }

            $pid = trim(file_get_contents($pidFile));
            if (empty($pid)) {
                return false;
            }

            // 2. En Windows, verificar proceso por título de ventana
            if (PHP_OS_FAMILY === 'Windows') {
                $output = [];
                // Verificamos tanto el PID como el título de la ventana
                exec("tasklist /FI \"PID eq $pid\" /NH", $output);
                $pidExists = count($output) > 0 && strpos($output[0], $pid) !== false;

                // También verificar por título de ventana
                $windowOutput = [];
                exec('tasklist /FI "WINDOWTITLE eq websocket_server" /NH', $windowOutput);
                $windowExists = count($windowOutput) > 0;

                // Como último recurso, verificar actividad de puerto 8080
                $portOutput = [];
                exec('netstat -an | findstr ":8080"', $portOutput);
                $portActive = count($portOutput) > 0;

                return $pidExists || $windowExists || $portActive;
            } else {
                // 3. En Linux/Unix verificar proceso
                if (file_exists("/proc/$pid")) {
                    return true;
                }

                // Verificar puerto 8080 como alternativa
                $output = [];
                exec("lsof -i:8080 -t", $output);
                return count($output) > 0;
            }

            return false;
        } catch (Exception $e) {
            self::registrarError("Error al verificar estado de servidor WebSocket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lee datos actuales del WebSocket usando WebSocket Client
     * 
     * @return array Datos leídos del WebSocket o array vacío si no hay datos
     */
    public function leerDatos(): array
    {
        // Si el WebSocket no está activo, devolver array vacío
        if (!self::verificarServidorWebSocket()) {
            self::registrarError("WebSocket no está activo. No se pueden leer datos.");
            return [];
        }

        // Si hay una última lectura, devolverla
        if (self::$ultimaLectura !== null) {
            return self::$ultimaLectura;
        }

        // Si no hay datos previos, devolver array vacío
        return [];
    }

    /**
     * Establece la última lectura recibida desde el WebSocket
     * 
     * @param float $temperatura Temperatura recibida
     * @param bool $simulado Indica si el dato es simulado
     * @return void
     */
    public static function setUltimaLectura(float $temperatura, bool $simulado = false): void
    {
        self::$ultimaLectura = [$temperatura];
        self::$modoSimulacion = $simulado;
    }

    /**
     * Indica si el modo simulación está activo
     * 
     * @return bool True si está en modo simulación
     */
    public static function getModoSimulacion(): bool
    {
        return self::$modoSimulacion;
    }

    /**
     * Verifica si el modo simulación está activado
     * 
     * @return bool True si está en modo simulación, false en caso contrario
     */
    public static function isModoSimulacion(): bool
    {
        return isset($_ENV['ARDUINO_SIMULATE']) &&
            (strtolower($_ENV['ARDUINO_SIMULATE']) === 'true' || $_ENV['ARDUINO_SIMULATE'] === '1');
    }

    /**
     * Registra un error en el archivo de registro
     * 
     * @param string $mensaje Mensaje del error
     * @param array $contexto Contexto del error
     * @return void
     */
    private static function registrarError(string $mensaje, array $contexto = []): void
    {
        $logFile = dirname(dirname(__DIR__)) . '/logs/arduino_debug.log';
        $timestamp = date('Y-m-d H:i:s');

        // Agregar información del sistema al contexto
        $contexto['sistemaOperativo'] = PHP_OS;
        $contexto['phpVersion'] = PHP_VERSION;
        $contexto['userRunning'] = get_current_user();
        $contexto['serverSoftware'] = $_SERVER['SERVER_SOFTWARE'] ?? 'CLI';
        $contexto['phpExtensions'] = implode(', ', get_loaded_extensions());
        $contexto['phpUid'] = getmyuid();
        $contexto['phpGid'] = getmygid();

        $logMessage = "[$timestamp] [ARDUINO] $mensaje\n";
        $logMessage .= "Contexto: " . json_encode($contexto, JSON_UNESCAPED_SLASHES) . "\n";
        $logMessage .= "----------------------------------------\n";

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Detiene el servidor WebSocket
     * 
     * @return bool True si se detuvo correctamente
     */
    public static function detenerServidorWebSocket(): bool
    {
        // Verificar si el servidor está activo
        if (!self::verificarServidorWebSocket()) {
            return false;
        }

        try {
            // En Windows, buscar el proceso y terminarlo
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec('taskkill /F /FI "WINDOWTITLE eq websocket_server" /T', $output, $resultCode);
                return $resultCode === 0;
            } else {
                // En sistemas Unix (Linux/Mac)
                $pidFile = dirname(dirname(__DIR__)) . '/logs/websocket.pid';

                if (file_exists($pidFile)) {
                    $pid = trim(file_get_contents($pidFile));

                    if (!empty($pid)) {
                        // Enviar señal de terminación (SIGTERM)
                        exec("kill -15 {$pid}", $output, $resultCode);

                        // Eliminar archivo PID
                        @unlink($pidFile);

                        return $resultCode === 0;
                    }
                }
            }

            return false;
        } catch (Exception $e) {
            self::registrarError("Error al detener servidor WebSocket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Inicia el servidor WebSocket
     * 
     * @return bool True si se inició correctamente
     */
    public static function iniciarServidorWebSocket(): bool
    {
        try {
            // Si ya está activo
            if (self::verificarServidorWebSocket()) {
                return true;
            }

            $scriptPath = dirname(dirname(__DIR__)) . '/config/websocket_server.php';
            $logFile = dirname(dirname(__DIR__)) . '/logs/websocket.log';
            $pidFile = dirname(dirname(__DIR__)) . '/logs/websocket.pid';

            // Preparar entorno para la ejecución
            $puertoArduino = $_ENV['ARDUINO_PORT'] ?? 'COM6';
            $baudrate = $_ENV['ARDUINO_BAUDRATE'] ?? 9600;

            // Dejar que el WebSocket decida si necesita simular
            $modoSimulacion = "false";

            // Registrar información de inicio
            self::registrarError("Iniciando servidor WebSocket con puerto: {$puertoArduino}, baudrate: {$baudrate}", [
                'script_path' => $scriptPath,
                'log_file' => $logFile
            ]);

            // Obtener ruta a PHP
            $phpPath = PHP_BINARY;

            // Crear comando dependiendo del sistema operativo
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows (usando start /B para ejecutar en segundo plano)
                $comando = "start /B \"websocket_server\" \"$phpPath\" \"$scriptPath\" \"$puertoArduino\" $baudrate $modoSimulacion";

                // Ejecutar en segundo plano
                self::registrarError("Ejecutando comando: {$comando}");
                pclose(popen($comando, 'r'));

                // Crear archivo PID en Windows (no es automático)
                $pidFilePath = dirname(dirname(__DIR__)) . '/logs/websocket_server.pid';
                file_put_contents($pidFilePath, getmypid());
            } else {
                // Linux/Mac (usando nohup para ejecutar en segundo plano)
                $comando = "ARDUINO_SIMULATE=$modoSimulacion nohup \"$phpPath\" \"$scriptPath\" \"$puertoArduino\" $baudrate > \"$logFile\" 2>&1 & echo $! > \"$pidFile\"";
                exec($comando, $output, $resultCode);
            }

            // Esperar un momento para que el servidor se inicie
            sleep(1);

            // Verificar que el servidor esté activo
            $activo = self::verificarServidorWebSocket();
            if (!$activo) {
                self::registrarError("No se pudo iniciar el servidor WebSocket", [
                    'comando' => $comando
                ]);

                self::$modoSimulacion = true;
                return false;
            }

            return true;
        } catch (Exception $e) {
            self::registrarError("Error al iniciar servidor WebSocket: " . $e->getMessage());
            self::$modoSimulacion = true;
            return false;
        }
    }

    /**
     * Procesa y formatea los datos recibidos del Arduino
     * 
     * @param array $datosLectura Datos leídos del Arduino
     * @return array Datos procesados y formateados
     */
    public static function procesarDatos(array $datosLectura): array
    {
        $datosProcesados = [];

        foreach ($datosLectura as $valor) {
            if (is_numeric($valor)) {
                $datosProcesados['temperatura'] = floatval($valor);
            }
        }

        return $datosProcesados;
    }

    /**
     * Inicializa la configuración para la conexión con Arduino
     * 
     * @param string $puerto Puerto COM o ruta del dispositivo
     * @param int $baudrate Velocidad de transmisión
     * @return void
     */
    public static function inicializar(string $puerto, int $baudrate): void
    {
        self::$puerto = $puerto;
        self::$baudrate = $baudrate;
    }
}
