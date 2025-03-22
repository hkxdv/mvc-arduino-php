# Servidor WebSocket para Arduino

Este documento detalla el servidor WebSocket implementado para la comunicación en tiempo real con Arduino, explicando su arquitectura, configuración y uso.

> [!IMPORTANT]
> El servidor WebSocket es el componente central para la comunicación en tiempo real. Asegúrate de que está correctamente configurado antes de usar la aplicación.

## Visión General

El servidor WebSocket actúa como puente entre el hardware Arduino y la interfaz web de la aplicación, permitiendo:

- Lectura en tiempo real de datos del Arduino a través del puerto serial
- Transmisión de esos datos a todos los clientes web conectados
- Funcionalidad de simulación para desarrollo sin hardware

## Estructura del Servidor

El servidor está implementado utilizando la biblioteca Ratchet para WebSockets en PHP:

```
/
├── config/
│   └── websocket_server.php    # Implementación del servidor WebSocket
├── logs/
│   └── websocket.log           # Registro de la actividad del servidor
└── .env                        # Configuración del servidor (puerto, baudrate, simulación)
```

## Configuración

### Variables de Entorno

El servidor utiliza las siguientes variables configurables en el archivo `.env`:

| Variable         | Descripción                                    | Valor por defecto |
| ---------------- | ---------------------------------------------- | ----------------- |
| ARDUINO_PORT     | Puerto serial al que está conectado el Arduino | COM3 (Windows)    |
| ARDUINO_BAUDRATE | Velocidad de comunicación en baudios           | 9600              |
| ARDUINO_SIMULATE | Habilitar simulación de datos sin Arduino      | false             |

> [!NOTE]
> Si no tienes un Arduino físico conectado, puedes establecer `ARDUINO_SIMULATE=true` para generar datos simulados.

### Modo de Simulación

El modo de simulación permite ejecutar el servidor sin un Arduino físico conectado, generando datos aleatorios para pruebas y desarrollo:

- Establecer `ARDUINO_SIMULATE=true` en el archivo `.env` para activar
- El servidor generará valores de temperatura aleatorios entre 20°C y 30°C
- Los datos simulados se identifican con el campo `simulated: true` en el JSON enviado

> [!TIP]
> El modo de simulación es ideal para desarrollo y pruebas sin necesidad de hardware.

## Uso del Servidor

### Iniciar el Servidor

```bash
php config/websocket_server.php
```

Opcionalmente, se pueden especificar el puerto y baudrate como parámetros:

```bash
php config/websocket_server.php COM4 115200
```

### Estructura del Mensaje WebSocket

Los datos enviados por el servidor siguen este formato JSON:

```json
{
  "temperature": 25.4,     // Valor de temperatura (si está disponible)
  "raw": "temperatura: 25.4", // Línea de datos original
  "timestamp": "2025-03-21 01:23:10", // Marca de tiempo
  "simulated": true        // Solo presente si son datos simulados
}
```

### Comandos del Cliente

El servidor acepta los siguientes comandos desde los clientes conectados:

| Comando | Formato JSON           | Descripción                                    |
| ------- | ---------------------- | ---------------------------------------------- |
| ping    | `{"command": "ping"}`  | Comprueba la conexión, devuelve "pong"         |
| stop    | `{"command": "stop"}`  | Detiene las lecturas (requiere autenticación)  |
| start   | `{"command": "start"}` | Reinicia las lecturas (requiere autenticación) |

## Manejo de Errores

> [!WARNING]
> El servidor implementa manejo automático de errores, pero ciertos problemas podrían requerir intervención manual.

El servidor implementa las siguientes estrategias de manejo de errores:

1. **Error de conexión serial**: Si no puede conectarse al puerto Arduino, cambia automáticamente al modo de simulación
2. **Error de lectura**: Registra el error en el archivo de logs y continúa intentando
3. **Desconexión de cliente**: Detecta y gestiona correctamente las desconexiones

Los mensajes de error se registran en `logs/websocket.log` con marca de tiempo.

## Integración con el Frontend

### Conectar desde JavaScript

```javascript
const ws = new WebSocket('ws://localhost:8080');

ws.onopen = () => {
  console.log('Conectado al servidor WebSocket');
};

ws.onmessage = (event) => {
  const data = JSON.parse(event.data);
  
  // Verificar si son datos simulados
  if (data.simulated) {
    console.log('Datos simulados:', data.temperature);
  } else {
    console.log('Temperatura real:', data.temperature);
  }
};
```

## Consideraciones de Seguridad

> [!CAUTION]
> Por defecto, el servidor WebSocket no implementa autenticación. Implementa medidas de seguridad adicionales para entornos de producción.

- El servidor acepta conexiones únicamente desde localhost por defecto
- Los comandos administrativos (stop/start) deberían implementar autenticación
- En producción, considerar la implementación de WebSockets seguros (WSS) 