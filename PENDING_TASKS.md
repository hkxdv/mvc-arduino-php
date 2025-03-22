# Tareas Pendientes del Proyecto

---

## 1. Plan de Modularización para arduino_websocket.js

El archivo `arduino_websocket.js` actual integra muchas funcionalidades diferentes y necesita una estructura modular. Deberá dividirse en los siguientes módulos:

1. **arduino-core.js**

   - Configuración básica y variables compartidas
   - Exportación de constantes
   - Funciones de utilidad comunes

2. **arduino-server.js**

   - Funciones para verificar estado (`checkServerStatus`)
   - Funciones para iniciar servidor (`startServer`)
   - Funciones para detener servidor (`stopServer`)

3. **arduino-logs.js**

   - Funciones para cargar logs (`loadLogs`)
   - Funciones para mostrar logs
   - Manejo de errores específicos de logs

4. **arduino-socket.js**

   - Funciones de conexión WebSocket (`connectWebSocket`)
   - Manejadores de eventos WebSocket (`handleSocketOpen`, `handleSocketMessage`, etc.)
   - Reconexión automática

5. **arduino-ui.js**

   - Funciones para actualizar interfaz (`updateTemperatureDisplay`)
   - Manejo del gráfico de temperatura
   - Actualización del monitor

6. **arduino-init.js**
   - Punto de entrada (`init`)
   - Inicialización de páginas (`initSensorPage`, `initAdminPage`)
   - Configuración de URL WebSocket

### Configuración en cargador.js

```javascript
const MODULOS_POR_PAGINA = {
  arduino: [
    "arduino-init.js",
    "arduino-core.js",
    "arduino-ui.js",
    "arduino-socket.js",
  ],
  "arduino/mostrar": [
    "arduino-init.js",
    "arduino-core.js",
    "arduino-ui.js",
    "arduino-socket.js",
  ],
  "arduino/configurar": ["arduino-init.js", "arduino-core.js", "arduino-ui.js"],
  "arduino/diagnostico": [
    "arduino-init.js",
    "arduino-core.js",
    "arduino-server.js",
    "arduino-logs.js",
  ],
  "arduino/webserver": [
    "arduino-init.js",
    "arduino-core.js",
    "arduino-server.js",
    "arduino-logs.js",
  ],
};
```

### Implementación

Para implementar esta estructura, será necesario:

- Crear cada archivo módulo con sus exportaciones específicas
- Actualizar `cargador.js` para incluir todos los módulos necesarios
- Asegurar que las interdependencias entre módulos se manejen correctamente mediante importaciones

Esto mejorará la mantenibilidad del código, reducirá la carga inicial en páginas que no necesitan todas las funcionalidades, y facilitará el desarrollo futuro.

---

## 2. Sistema de persistencia de datos de sensores Arduino

Implementar un sistema para guardar los datos recibidos del WebSocket Arduino en la base de datos PostgreSQL, permitiendo su consulta posterior.

- Capturar datos de temperatura recibidos del WebSocket en tiempo real
- Almacenar estos datos en la base de datos PostgreSQL con una frecuencia controlada
- Implementar consultas para recuperar datos históricos

### Consideraciones técnicas

1. **No modificar archivos base**:

   - El servidor WebSocket (`config/websocket_server.php`) no debe ser modificado
   - Mantener la integridad de los modelos existentes

2. **Crear o extender modelos**:

   - Extender `ArduinoSensorModel` para añadir funcionalidad de persistencia
   - Implementar método `guardarLectura()` para registrar datos del sensor
   - Implementar funciones de consulta como `obtenerLecturasRecientes()` y `obtenerEstadisticas()`

3. **Estructura de datos**:

   - Crear esquema de base de datos en `app/Models/sql/esquemas_pg.sql` siguiendo los estándares PostgreSQL
   - Definir tablas para almacenar datos de temperatura, timestamp y metadatos
   - Incluir índices para consultas eficientes

4. **Frecuencia de registro**:
   - Para evitar saturación de la base de datos, NO registrar datos en tiempo real
   - Implementar un sistema tipo "cron job" que guarde lecturas cada 1-5 minutos
   - Considerar guardar promedios o valores representativos en cada intervalo

### Implementación recomendada para persistencia de datos

Para implementar la persistencia de datos sin modificar el modelo `ArduinoSensorModel` existente, se recomienda:

1. **Crear un nuevo modelo `SensorPersistenciaModel`** que utilice el modelo base:

```php
<?php
// app/Models/SensorPersistenciaModel.php

namespace App\Models;

use DateTime;
use Exception;
use PDO;

/**
 * Modelo para gestionar la persistencia de datos de sensores Arduino
 *
 * Este modelo se encarga específicamente de guardar las lecturas
 * de sensores a intervalos regulares utilizando ArduinoSensorModel
 * sin modificar su implementación original
 *
 * @package App\Models
 */
class SensorPersistenciaModel
{
    /**
     * Intervalo mínimo entre registros en minutos
     * @var int
     */
    private static $intervaloMinimo = 5;

    /**
     * Ruta del archivo de registro
     * @var string
     */
    private static $logFile = null;

    /**
     * Configura el intervalo mínimo entre registros
     *
     * @param int $minutos Minutos entre registros
     * @return void
     */
    public static function configurarIntervalo(int $minutos): void
    {
        if ($minutos >= 1 && $minutos <= 60) {
            self::$intervaloMinimo = $minutos;
        }
    }

    /**
     * Configura el archivo de registro
     *
     * @param string $rutaArchivo Ruta al archivo de log
     * @return bool True si se pudo configurar
     */
    public static function configurarLog(string $rutaArchivo): bool
    {
        if (!is_dir(dirname($rutaArchivo))) {
            mkdir(dirname($rutaArchivo), 0777, true);
        }

        if (is_writable(dirname($rutaArchivo)) || (file_exists($rutaArchivo) && is_writable($rutaArchivo))) {
            self::$logFile = $rutaArchivo;
            return true;
        }

        return false;
    }

    /**
     * Registra un mensaje en el archivo de log
     *
     * @param string $mensaje Mensaje a registrar
     * @return void
     */
    private static function registrarLog(string $mensaje): void
    {
        if (self::$logFile === null) {
            $logDir = dirname(dirname(__DIR__)) . '/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
            self::$logFile = $logDir . '/sensor_persistencia.log';
        }

        $timestamp = date('Y-m-d H:i:s');
        $mensajeFormateado = "[$timestamp] $mensaje" . PHP_EOL;
        file_put_contents(self::$logFile, $mensajeFormateado, FILE_APPEND);
    }

    /**
     * Verifica si ha pasado suficiente tiempo desde el último registro
     *
     * @return bool True si debe realizarse un nuevo registro
     */
    private static function debeRegistrar(): bool
    {
        try {
            // Obtener el último registro de la base de datos
            $conexion = ConexionModel::conectar();
            $sql = "SELECT timestamp FROM sensor_lecturas ORDER BY timestamp DESC LIMIT 1";
            $stmt = $conexion->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado) {
                $ultimoRegistro = new DateTime($resultado['timestamp']);
                $ahora = new DateTime();

                // Calcular diferencia en minutos
                $diff = $ahora->diff($ultimoRegistro);
                $minutos = $diff->days * 24 * 60 + $diff->h * 60 + $diff->i;

                // Si no ha pasado el intervalo mínimo, no registrar
                if ($minutos < self::$intervaloMinimo) {
                    self::registrarLog("No han pasado " . self::$intervaloMinimo . " minutos desde el último registro ($minutos minutos)");
                    return false;
                }
            }

            return true;

        } catch (Exception $e) {
            self::registrarLog("Error al verificar intervalo de registro: " . $e->getMessage());
            return true; // Ante la duda, intentar registrar
        }
    }

    /**
     * Registra la lectura actual del sensor en la base de datos
     * si ha pasado suficiente tiempo desde el último registro
     *
     * @return bool True si se realizó un nuevo registro
     */
    public static function registrarLecturaPeriodica(): bool
    {
        self::registrarLog("Iniciando verificación para registro periódico");

        // Verificar si el servidor WebSocket está activo
        if (!ArduinoSensorModel::verificarServidorWebSocket()) {
            self::registrarLog("Servidor WebSocket no está activo");
            return false;
        }

        // Crear instancia temporal de ArduinoSensorModel para leer datos
        $arduino = new ArduinoSensorModel('COM1'); // El puerto no importa para leer datos del WebSocket
        $datos = $arduino->leerDatos();

        if (empty($datos)) {
            self::registrarLog("No hay datos disponibles para registrar");
            return false;
        }

        // Verificar si debemos registrar
        if (!self::debeRegistrar()) {
            return false;
        }

        // Extraer temperatura y estado de simulación
        $temperatura = $datos[0];
        $simulado = ArduinoSensorModel::getModoSimulacion();

        try {
            // Guardar lectura utilizando nuestro método guardarLectura
            $resultado = self::guardarLectura($temperatura, $simulado);

            if ($resultado) {
                self::registrarLog("Lectura registrada correctamente: $temperatura°C (Simulado: " .
                    ($simulado ? 'Sí' : 'No') . ")");
            } else {
                self::registrarLog("Error al registrar lectura");
            }

            return $resultado;
        } catch (Exception $e) {
            self::registrarLog("Excepción al guardar lectura: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Guarda los datos de lectura del sensor en la base de datos
     *
     * @param float $temperatura Temperatura recibida
     * @param bool $simulado Indica si el dato es simulado
     * @return bool True si se guardó correctamente
     */
    public static function guardarLectura(float $temperatura, bool $simulado = false): bool
    {
        try {
            // Obtener conexión a la base de datos
            $conexion = ConexionModel::conectar();

            // Preparar consulta
            $sql = "INSERT INTO sensor_lecturas (temperatura, es_simulado) VALUES (?, ?)";
            $stmt = $conexion->prepare($sql);

            // Ejecutar consulta
            return $stmt->execute([$temperatura, $simulado]);
        } catch (Exception $e) {
            self::registrarLog("Error al guardar lectura en base de datos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecuta la tarea programada (cron) para registrar datos
     * Este método puede llamarse directamente desde un script
     *
     * @return void
     */
    public static function ejecutarTareaProgramada(): void
    {
        // Registrar inicio de tarea
        self::registrarLog("Iniciando tarea programada de registro de temperatura");

        // Intentar registrar lectura
        $resultado = self::registrarLecturaPeriodica();

        // Registrar resultado
        self::registrarLog("Resultado de tarea programada: " .
            ($resultado ? "Registro guardado correctamente" : "No fue necesario guardar"));
    }
}
```

2. **Crear un script ejecutable para el cron job**:

```php
<?php
// scripts/registrar_temperatura.php

// Cargar autoloader y dependencias
require_once __DIR__ . '/../bootstrap.php';

// Usar el modelo de persistencia
use App\Models\SensorPersistenciaModel;

// Configurar intervalo (opcional)
SensorPersistenciaModel::configurarIntervalo(5); // 5 minutos

// Ejecutar tarea programada
SensorPersistenciaModel::ejecutarTareaProgramada();
```

3. **Configurar cron job en el servidor**:

```
# Ejecutar cada 5 minutos
*/5 * * * * /usr/bin/php /ruta/al/proyecto/scripts/registrar_temperatura.php
```

---

## 3. Visualización de datos históricos de sensores

Desarrollar un módulo de vista para visualizar los datos históricos almacenados en la base de datos, permitiendo a los usuarios analizar tendencias y estadísticas.

- Crear una interfaz para consultar y visualizar datos históricos de temperatura
- Implementar gráficos interactivos para análisis de tendencias
- Ofrecer funcionalidades de exportación de datos
- Desarrollar un panel con estadísticas básicas

### Consideraciones técnicas

1. **Implementación de vistas**:

   - Utilizar TailwindCSS para mantener consistencia con el resto de la aplicación
   - Seguir el mismo diseño o similar a los módulos existentes
   - Asegurar que los componentes sean responsivos y accesibles

2. **Componentes principales**:

   - Filtros de fecha con selección de rangos personalizados
   - Gráficos de líneas para visualizar tendencias
   - Tablas de datos con paginación
   - Panel de estadísticas con valores mínimos, máximos y promedios

3. **Librerías recomendadas**:
   - Chart.js para gráficos (compatible con TailwindCSS)
   - Flatpickr para selectores de fecha
   - DataTables para tablas de datos con búsqueda y paginación

### Implementación

1. **Crear nuevo controlador para datos históricos**:

```php
<?php
// app/Controllers/SensorHistoricoController.php

namespace App\Controllers;

use App\Models\SensorPersistenciaModel;

class SensorHistoricoController
{
    
}
```

2. **Crear vistas para la visualización**:

```php
// app/Views/modules/arduino/historico.module.php

// ...
```

3. **JavaScript para cargar y visualizar datos**:

```javascript
// assets/js/modules/sensor_historico.js

// ...
```
