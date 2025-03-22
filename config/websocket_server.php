<?php

/**
 * websocket_server.php
 * Servidor WebSocket para comunicación en tiempo real con Arduino
 * Creado con biblioteca Ratchet
 * 
 * Uso: php websocket_server.php
 * 
 * Este servidor:
 * 1. Conecta con el Arduino a través del puerto serial
 * 2. Lee datos continuamente del Arduino
 * 3. Transmite esos datos a todos los clientes WebSocket conectados
 */

// Importar clases de Ratchet
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

// Cargar el autoloader de Composer
require dirname(__DIR__) . '/vendor/autoload.php';

// Cargar configuración de entorno
if (file_exists(__DIR__ . "/../.env")) {
    Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
}

// Procesamiento de argumentos de línea de comandos
$puerto_arduino = $argv[1] ?? $_ENV['ARDUINO_PORT'];
$baudrate = intval($argv[2] ?? $_ENV['ARDUINO_BAUDRATE']);
$simular_datos = false;

// Determinar si debemos simular los datos
// 1. Verificar argumentos de línea de comandos
if (isset($argv[3]) && ($argv[3] === 'true' || $argv[3] === '1')) {
    $simular_datos = true;
}
// 2. Si no hay argumento explícito, verificar .env
else if (isset($_ENV['ARDUINO_SIMULATE']) && $_ENV['ARDUINO_SIMULATE'] === 'true') {
    $simular_datos = true;
}

// Crear archivo de log
$logFile = dirname(__DIR__) . '/logs/websocket.log';
$logDir = dirname($logFile);

// Asegurar que el directorio logs existe
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Intentar abrir el archivo de log con manejo de errores
$maxRetries = 3;
$retryDelay = 100000; // 100ms
$attempt = 0;

while ($attempt < $maxRetries) {
    $logHandle = @fopen($logFile, 'a');
    if ($logHandle !== false) {
        break;
    }
    $attempt++;
    usleep($retryDelay);
}

if ($logHandle === false) {
    error_log("Error: No se pudo abrir el archivo de log después de $maxRetries intentos");
    die("Error: No se pudo abrir el archivo de log");
}

// Función para escribir en el log
function writeLog($message)
{
    global $logHandle;
    if ($logHandle) {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] $message" . PHP_EOL;
        fwrite($logHandle, $formattedMessage);
        fflush($logHandle);
    }
}

writeLog("Iniciando servidor WebSocket...");
writeLog("Puerto Arduino: $puerto_arduino");
writeLog("Baudrate: $baudrate");
writeLog("Simular datos: " . ($simular_datos ? "Sí" : "No"));

// Clase que implementa el servidor WebSocket
class ArduinoServer implements MessageComponentInterface
{
    protected $clients;
    protected $serialHandle;
    protected $serialPort;
    protected $baudRate;
    protected $readInterval;
    protected $lastReadTime;
    protected $running;
    protected $loop;
    protected $simulateData;

    public function __construct($serialPort, $baudRate, $simulateData = false)
    {
        $this->clients = new \SplObjectStorage;
        $this->serialPort = $serialPort;
        $this->baudRate = $baudRate;
        $this->readInterval = 0.5; // Intervalo de lectura en segundos
        $this->lastReadTime = 0;
        $this->running = true;
        $this->simulateData = $simulateData;

        // Iniciar el proceso de lectura de datos
        if (!$this->simulateData) {
            writeLog("Iniciando conexión a Arduino en puerto $serialPort");
            try {
                $this->initializeSerialConnection();
            } catch (\Exception $e) {
                writeLog("Error en conexión serial: " . $e->getMessage());
                writeLog("Cambiando a modo de simulación de datos");
                $this->simulateData = true;
            }
        } else {
            writeLog("Modo de simulación de datos activado");
        }
    }

    /**
     * Establece la referencia al loop de eventos
     */
    public function setLoop($loop)
    {
        $this->loop = $loop;
        // Iniciar bucle de lectura
        $this->startReadingLoop();
    }

    /**
     * Inicializa la conexión serial con el Arduino
     */
    protected function initializeSerialConnection()
    {
        try {
            // Formatear puerto para Windows
            $originalPort = $this->serialPort;
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Asegurarse de que el formato es correcto para Windows
                $this->serialPort = str_replace('\\\\.\\', '', $this->serialPort); // Eliminar prefijo si ya existe
                $this->serialPort = '\\\\.\\' . $this->serialPort;  // Agregar prefijo adecuado
            }

            writeLog("Intentando abrir puerto serial: " . $this->serialPort . " (original: " . $originalPort . ")");

            // Verificar si el puerto existe (solo Windows)
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec('mode', $puertos);
                $puertoLimpio = str_replace('\\\\.\\', '', $this->serialPort);
                $puertoEncontrado = false;
                foreach ($puertos as $puerto) {
                    if (strpos($puerto, $puertoLimpio) !== false) {
                        $puertoEncontrado = true;
                        writeLog("Puerto detectado en sistema: " . $puertoLimpio);
                        break;
                    }
                }
                if (!$puertoEncontrado) {
                    writeLog("ADVERTENCIA: El puerto " . $puertoLimpio . " no se encontró entre los puertos disponibles");
                    
                    // Listar puertos disponibles
                    writeLog("Puertos disponibles:");
                    foreach ($puertos as $linea) {
                        if (strpos($linea, 'COM') !== false) {
                            writeLog($linea);
                        }
                    }
                    
                    throw new \Exception("El puerto " . $puertoLimpio . " no está disponible en el sistema");
                }
            }

            // Intentar abrir el puerto con reintentos
            $maxRetries = 3;
            $retryDelay = 1000000; // 1 segundo
            $attempt = 0;

            while ($attempt < $maxRetries) {
                writeLog("Intento " . ($attempt + 1) . " de abrir puerto " . $this->serialPort);
                $this->serialHandle = @fopen($this->serialPort, 'r+b');
                if ($this->serialHandle !== false) {
                    break;
                }
                $attempt++;
                writeLog("Intento $attempt de abrir puerto serial fallido, reintentando...");
                usleep($retryDelay);
            }

            if (!$this->serialHandle) {
                throw new \Exception("No se pudo abrir el puerto serial después de $maxRetries intentos");
            }

            // Configurar puerto serial en sistemas Linux/Mac
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $comando = "stty -F " . $this->serialPort . " " . $this->baudRate . " cs8 -cstopb -parenb -echo";
                exec($comando);
            }

            // Configurar timeout para lectura
            stream_set_timeout($this->serialHandle, 0, 100000); // 100ms timeout
            stream_set_blocking($this->serialHandle, false); // Modo no bloqueante

            writeLog("Conexión serial establecida correctamente en " . $this->serialPort . " a " . $this->baudRate . " baudios");
            return true;
        } catch (\Exception $e) {
            writeLog("Error al inicializar conexión serial: " . $e->getMessage());
            if ($this->serialHandle) {
                fclose($this->serialHandle);
                $this->serialHandle = null;
            }
            $this->simulateData = true; // Activar modo simulación automáticamente
            writeLog("Cambiando a modo de simulación debido a error de conexión");
            throw $e;
        }
    }

    /**
     * Inicia el bucle de lectura de datos de Arduino
     */
    protected function startReadingLoop()
    {
        // Crear timer periódico para leer datos
        if ($this->loop) {
            $this->loop->addPeriodicTimer($this->readInterval, function () {
                if ($this->simulateData) {
                    $this->simulateArduinoData();
                } else {
                    $this->readFromArduino();
                }
            });
            writeLog("Bucle de lectura iniciado (intervalo: $this->readInterval segundos)");
        } else {
            writeLog("Error: No se pudo iniciar el bucle de lectura (loop no configurado)");
        }
    }

    /**
     * Genera datos simulados como si vinieran del Arduino
     */
    protected function simulateArduinoData()
    {
        if (!$this->running) {
            return;
        }

        try {
            // Generar temperatura aleatoria entre 20 y 30 grados
            $temperature = round(rand(200, 300) / 10, 1);

            // Crear datos simulados
            $data = [
                'temperature' => $temperature,
                'raw' => "temperatura: $temperature",
                'timestamp' => date('Y-m-d H:i:s'),
                'simulated' => true
            ];

            // Convertir a JSON
            $json = json_encode($data);

            // Enviar a todos los clientes
            foreach ($this->clients as $client) {
                $client->send($json);
            }

            writeLog("Datos simulados enviados: temperatura: $temperature");
        } catch (\Exception $e) {
            writeLog("Error al simular datos: " . $e->getMessage());
        }
    }

    /**
     * Lee datos del Arduino y los envía a todos los clientes
     */
    public function readFromArduino()
    {
        if (!$this->running || !$this->serialHandle) {
            return;
        }

        try {
            $line = fgets($this->serialHandle);

            if ($line !== false) {
                $line = trim($line);

                if (!empty($line) && is_numeric($line)) {
                    $temperature = floatval($line);
                    $data = [
                        'temperature' => $temperature,
                        'timestamp' => date('Y-m-d H:i:s')
                    ];

                    $json = json_encode($data);

                    foreach ($this->clients as $client) {
                        $client->send($json);
                    }

                    writeLog("Datos leídos y enviados: temperatura: $temperature");
                }
            }
        } catch (\Exception $e) {
            writeLog("Error al leer datos de Arduino: " . $e->getMessage());
        }
    }

    /**
     * Maneja nueva conexión de cliente
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        writeLog("Nueva conexión: {$conn->resourceId}");

        // Enviar mensaje de bienvenida
        $conn->send(json_encode([
            'type' => 'welcome',
            'message' => 'Conectado al servidor Arduino WebSocket',
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }

    /**
     * Maneja mensajes recibidos de clientes
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        writeLog("Mensaje recibido de {$from->resourceId}: $msg");

        // Procesar comandos
        try {
            $data = json_decode($msg, true);

            if (is_array($data) && isset($data['command'])) {
                $command = $data['command'];

                switch ($command) {
                    case 'ping':
                        $from->send(json_encode([
                            'type' => 'pong',
                            'timestamp' => date('Y-m-d H:i:s')
                        ]));
                        break;

                    case 'stop':
                        // Solo para administradores (podría implementarse autenticación)
                        $this->running = false;
                        writeLog("Comando STOP recibido. Deteniendo lecturas.");
                        break;

                    case 'start':
                        // Solo para administradores
                        $this->running = true;
                        writeLog("Comando START recibido. Reiniciando lecturas.");
                        break;

                    default:
                        writeLog("Comando desconocido: $command");
                }
            }
        } catch (\Exception $e) {
            writeLog("Error al procesar mensaje: " . $e->getMessage());
        }
    }

    /**
     * Maneja desconexión de clientes
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        writeLog("Conexión cerrada: {$conn->resourceId}");
    }

    /**
     * Maneja errores de conexión
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        writeLog("Error: {$e->getMessage()}");
        $conn->close();
    }
}

// Liberar servidor de bloqueo de tiempo de ejecución
set_time_limit(0);

try {
    writeLog("Iniciando servidor WebSocket");
    
    // Crear instancia del servidor (con el modo de simulación especificado)
    $server = new ArduinoServer($puerto_arduino, $baudrate, $simular_datos);

    // Crear servidor Ratchet
    $wsServer = new WsServer($server);
    $httpServer = new HttpServer($wsServer);
    $ioServer = IoServer::factory(
        $httpServer,
        8080  // Puerto WebSocket
    );

    // Establecer referencia al loop para el servidor
    $server->setLoop($ioServer->loop);

    writeLog("Servidor WebSocket iniciado en puerto 8080");

    // Guardar PID para poder detener el servidor más tarde
    $pidFile = dirname(__DIR__) . '/logs/websocket_server.pid';
    file_put_contents($pidFile, getmypid());
    writeLog("PID guardado en archivo: " . $pidFile . " - PID: " . getmypid());

    // Iniciar servidor
    $ioServer->run();
} catch (\Exception $e) {
    writeLog("Error fatal: " . $e->getMessage());
    exit(1);
} finally {
    // Cerrar archivo de log al finalizar
    if (isset($logHandle) && $logHandle) {
        fclose($logHandle);
    }

    // Limpiar archivo PID si existe
    $pidFile = dirname(__DIR__) . '/logs/websocket_server.pid';
    if (file_exists($pidFile)) {
        @unlink($pidFile);
    }
}
