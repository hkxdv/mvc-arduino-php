/**
 * Módulo para gestionar la comunicación WebSocket con Arduino
 * Proporciona funcionalidades para conectar, leer datos y mostrar información en tiempo real
 * @module arduino_websocket
 */

import appCache from "./cache.js";

// Configuración
const CONFIG = {
  WEBSOCKET_URL: "", // Se establecerá dinámicamente
  RECONNECT_TIMEOUT: 3000,
  MAX_RECONNECT_ATTEMPTS: 5,
  CHART_MAX_POINTS: 50,
  SELECTORS: {
    // Selectores para la vista de temperatura
    WS_STATUS: "#ws-status",
    WS_DATA_SOURCE: "#ws-data-source",
    TEMP_DISPLAY: "#temperature-display",
    TIMESTAMP_DISPLAY: "#timestamp-display",
    MONITOR_SERIAL: "#monitor-serial",
    AUTO_SCROLL: "#auto-scroll",
    BTN_RECONECTAR: "#btn-reconectar",
    BTN_CLEAR_MONITOR: "#btn-clear-monitor",
    TEMPERATURE_CHART: "#temperature-chart",

    // Selectores para la página de administración
    SERVER_STATUS: "#server-status",
    START_SERVER_BTN: "#iniciar-server",
    STOP_SERVER_BTN: "#detener-server",
    REFRESH_STATUS_BTN: "#refrescar-estado",
    LOGS_TABLE: "#logs-table",
    WS_URL: "#ws-url",
  },
};

// Variables del módulo
let socket = null;
let reconnectAttempts = 0;
let reconnectTimeout = null;
let temperatureChart = null;
let isAdminPage = false;

/**
 * Inicializa el módulo
 */
function init() {
  console.log("Inicializando módulo arduino_websocket.js");

  // Establecer la URL del WebSocket dinámicamente
  setupWebSocketUrl();

  // Determinar si estamos en la página de administración de WebSocket
  isAdminPage = window.location.href.includes("webserver");

  // Determinar en qué página estamos e inicializar componentes apropiados
  if (isAdminPage) {
    console.log("Inicializando página de administración del WebSocket");
    initAdminPage();
  } else if (window.location.href.includes("mostrar")) {
    console.log("Inicializando página de visualización de sensor");
    initSensorPage();
  } else if (window.location.href.includes("diagnostico")) {
    console.log("Inicializando página de diagnóstico");
    // No se requiere inicialización adicional para diagnóstico
  }
}

/**
 * Establece la URL del WebSocket dinámicamente
 */
function setupWebSocketUrl() {
  try {
    // Intentar obtener la URL del elemento #ws-url
    const wsUrlElement = document.querySelector(CONFIG.SELECTORS.WS_URL);
    if (wsUrlElement && wsUrlElement.textContent) {
      CONFIG.WEBSOCKET_URL = wsUrlElement.textContent.trim();
      console.log(
        `URL del WebSocket establecida desde elemento DOM: ${CONFIG.WEBSOCKET_URL}`
      );
      return;
    }

    // Si no se encuentra en el DOM, construirla dinámicamente
    const protocol = window.location.protocol === "https:" ? "wss:" : "ws:";
    const host = window.location.hostname;
    const port = "8080"; // Puerto por defecto para el WebSocket

    CONFIG.WEBSOCKET_URL = `${protocol}//${host}:${port}`;
    console.log(
      `URL del WebSocket construida dinámicamente: ${CONFIG.WEBSOCKET_URL}`
    );
  } catch (error) {
    // Si hay algún error, usar una URL por defecto
    CONFIG.WEBSOCKET_URL = "ws://localhost:8080";
    console.error(`Error al establecer URL del WebSocket: ${error.message}`);
    console.log(`Usando URL por defecto: ${CONFIG.WEBSOCKET_URL}`);
  }
}

/**
 * Inicializa la página de visualización del sensor
 */
function initSensorPage() {
  console.log("Inicializando vista de sensor de temperatura");
  try {
    // Elementos del DOM
    const temperatureDisplay = document.querySelector(
      CONFIG.SELECTORS.TEMP_DISPLAY
    );
    const monitorSerial = document.querySelector(
      CONFIG.SELECTORS.MONITOR_SERIAL
    );
    const btnReconectar = document.querySelector(
      CONFIG.SELECTORS.BTN_RECONECTAR
    );
    const btnClearMonitor = document.querySelector(
      CONFIG.SELECTORS.BTN_CLEAR_MONITOR
    );
    const autoScroll = document.querySelector(CONFIG.SELECTORS.AUTO_SCROLL);

    // Verificar que existan los elementos críticos
    if (!temperatureDisplay) {
      console.error(
        `Error: No se encontró el elemento '${CONFIG.SELECTORS.TEMP_DISPLAY}'`
      );
    }

    if (!monitorSerial) {
      console.warn(
        `Advertencia: No se encontró el monitor serial '${CONFIG.SELECTORS.MONITOR_SERIAL}'`
      );
    }

    // Inicializar el gráfico de temperatura
    // Esperar a que Chart.js esté cargado
    if (typeof Chart !== "undefined") {
      initTemperatureChart(CONFIG.SELECTORS.TEMPERATURE_CHART);
    } else {
      console.error(
        "Error: Chart.js no está cargado. El gráfico no se inicializará."
      );
    }

    // Eventos
    if (btnReconectar) {
      btnReconectar.addEventListener("click", reconnectWebSocket);
    } else {
      console.warn(
        `Advertencia: No se encontró el botón de reconexión '${CONFIG.SELECTORS.BTN_RECONECTAR}'`
      );
    }

    if (btnClearMonitor && monitorSerial) {
      btnClearMonitor.addEventListener("click", () => {
        monitorSerial.innerHTML = "Monitor limpiado\n";
      });
    }

    // Conectar al WebSocket
    connectWebSocket();
  } catch (error) {
    console.error("Error al inicializar la página del sensor:", error);
  }
}

/**
 * Inicializa la página de administración
 */
function initAdminPage() {
  // Elementos de la interfaz
  const statusDiv = document.querySelector(CONFIG.SELECTORS.SERVER_STATUS);
  const startBtn = document.querySelector(CONFIG.SELECTORS.START_SERVER_BTN);
  const stopBtn = document.querySelector(CONFIG.SELECTORS.STOP_SERVER_BTN);
  const refreshBtn = document.querySelector(
    CONFIG.SELECTORS.REFRESH_STATUS_BTN
  );

  if (!statusDiv) {
    console.error("No se encontró el elemento de estado del servidor");
    return;
  }

  // Verificar estado inicial
  checkServerStatus();

  // Cargar información de logs
  loadLogs();

  // Eventos de los botones
  if (startBtn) {
    startBtn.addEventListener("click", startServer);
  }

  if (stopBtn) {
    stopBtn.addEventListener("click", stopServer);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener("click", () => {
      checkServerStatus();
      loadLogs();
    });
  }

  // Actualizar estado cada 30 segundos
  setInterval(() => {
    checkServerStatus();
  }, 30000);
}

/**
 * Verifica el estado del servidor WebSocket
 */
function checkServerStatus() {
  const statusDiv = document.querySelector(CONFIG.SELECTORS.SERVER_STATUS);
  if (!statusDiv) return;

  statusDiv.innerHTML =
    '<span class="inline-block w-4 h-4 mr-3 border-t-2 border-blue-500 border-r-2 rounded-full animate-spin"></span><span class="text-gray-700">Verificando estado del servidor...</span>';
  statusDiv.className = "p-4 flex items-center";

  // Usar fetch para verificar el estado del servidor
  fetch("index.php?option=arduino/estadoWebSocket", {
    method: "GET",
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "Cache-Control": "no-cache",
      Pragma: "no-cache",
    },
    cache: "no-store",
  })
    .then((response) => {
      console.log("Estado de respuesta:", response.status);
      console.log("Tipo de contenido:", response.headers.get("content-type"));

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      // Verificar si es JSON
      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        console.warn("La respuesta no es JSON:", contentType);
        // Clonar la respuesta para poder inspeccionarla
        return response
          .clone()
          .text()
          .then((text) => {
            console.error("Contenido de respuesta no-JSON:", text);
            throw new Error("La respuesta no es JSON válido");
          });
      }

      return response.json();
    })
    .then((data) => {
      console.log("Datos del servidor:", data);

      // Actualizar la interfaz según el estado
      const startBtn = document.querySelector(
        CONFIG.SELECTORS.START_SERVER_BTN
      );
      const stopBtn = document.querySelector(CONFIG.SELECTORS.STOP_SERVER_BTN);

      if (data.activo) {
        statusDiv.className =
          "p-4 flex items-center bg-green-100 text-green-800";
        statusDiv.innerHTML =
          '<i class="fas fa-check-circle mr-2"></i> Servidor WebSocket activo' +
          (data.simulacion ? " (Modo simulación)" : "");

        // Habilitar/deshabilitar botones según corresponda
        if (startBtn) startBtn.disabled = true;
        if (stopBtn) stopBtn.disabled = false;
      } else {
        statusDiv.className = "p-4 flex items-center bg-red-100 text-red-800";
        statusDiv.innerHTML =
          '<i class="fas fa-times-circle mr-2"></i> Servidor WebSocket detenido';

        // Habilitar/deshabilitar botones según corresponda
        if (startBtn) startBtn.disabled = false;
        if (stopBtn) stopBtn.disabled = true;
      }
    })
    .catch((error) => {
      console.error("Error al verificar estado:", error);
      statusDiv.className =
        "p-4 flex items-center bg-yellow-100 text-yellow-800";
      statusDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle mr-2"></i> 
        Error al verificar estado: ${error.message}
        <button id="retry-status-check" class="ml-3 px-2 py-1 text-xs bg-yellow-200 hover:bg-yellow-300 rounded">
          Reintentar
        </button>
      `;

      // Agregar listener para reintentar
      document
        .getElementById("retry-status-check")
        ?.addEventListener("click", (e) => {
          e.preventDefault();
          checkServerStatus();
        });
    });
}

/**
 * Inicia el servidor WebSocket
 */
function startServer() {
  const statusDiv = document.querySelector(CONFIG.SELECTORS.SERVER_STATUS);
  if (!statusDiv) return;

  statusDiv.className = "p-4 flex items-center bg-blue-100 text-blue-800";
  statusDiv.innerHTML =
    '<span class="inline-block w-4 h-4 mr-3 border-t-2 border-blue-500 border-r-2 rounded-full animate-spin"></span> Iniciando servidor WebSocket...';

  console.log("Solicitando inicio del servidor WebSocket...");

  fetch("index.php?option=arduino/iniciarWebSocket", {
    method: "GET",
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "Cache-Control": "no-cache",
      Pragma: "no-cache",
    },
    cache: "no-store",
  })
    .then((response) => {
      console.log("Estado de respuesta:", response.status);
      console.log("Tipo de contenido:", response.headers.get("content-type"));

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      // Verificar si es JSON
      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        console.warn("La respuesta no es JSON:", contentType);
        // Clonar la respuesta para poder inspeccionarla
        return response
          .clone()
          .text()
          .then((text) => {
            console.error("Contenido de respuesta no-JSON:", text);
            throw new Error("La respuesta no es JSON válido");
          });
      }

      return response.json();
    })
    .then((data) => {
      console.log("Respuesta del servidor:", data);

      if (data && data.success) {
        console.log("Servidor iniciado correctamente");
        statusDiv.className =
          "p-4 flex items-center bg-green-100 text-green-800";
        statusDiv.innerHTML =
          '<i class="fas fa-check-circle mr-2"></i> Iniciando servidor WebSocket... Por favor espere.';

        // Esperar un tiempo para que el servidor inicie completamente
        setTimeout(() => {
          checkServerStatus();
          loadLogs();
        }, 3000);
      } else {
        throw new Error(data.message || "No se pudo iniciar el servidor");
      }
    })
    .catch((error) => {
      console.error("Error al iniciar servidor:", error);
      statusDiv.className = "p-4 flex items-center bg-red-100 text-red-800";
      statusDiv.innerHTML = `
        <i class="fas fa-times-circle mr-2"></i> Error al iniciar el servidor: ${error.message}
        <button id="retry-start" class="ml-3 px-2 py-1 text-xs bg-red-200 hover:bg-red-300 rounded">
          Reintentar
        </button>
        <button id="reload-page" class="ml-2 px-2 py-1 text-xs bg-blue-200 hover:bg-blue-300 rounded">
          Recargar página
        </button>
      `;

      // Agregar listeners a los botones
      document.getElementById("retry-start")?.addEventListener("click", (e) => {
        e.preventDefault();
        startServer();
      });

      document.getElementById("reload-page")?.addEventListener("click", (e) => {
        e.preventDefault();
        window.location.reload();
      });
    });
}

/**
 * Detiene el servidor WebSocket
 */
function stopServer() {
  const statusDiv = document.querySelector(CONFIG.SELECTORS.SERVER_STATUS);
  if (!statusDiv) return;

  statusDiv.className = "p-4 flex items-center bg-blue-100 text-blue-800";
  statusDiv.innerHTML =
    '<span class="inline-block w-4 h-4 mr-3 border-t-2 border-blue-500 border-r-2 rounded-full animate-spin"></span> Deteniendo servidor WebSocket...';

  console.log("Solicitando detener el servidor WebSocket...");

  fetch("index.php?option=arduino/detenerWebSocket", {
    method: "GET",
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "Cache-Control": "no-cache",
      Pragma: "no-cache",
    },
    cache: "no-store",
  })
    .then((response) => {
      console.log("Estado de respuesta:", response.status);
      console.log("Tipo de contenido:", response.headers.get("content-type"));

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      // Verificar si es JSON
      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        console.warn("La respuesta no es JSON:", contentType);
        // Clonar la respuesta para poder inspeccionarla
        return response
          .clone()
          .text()
          .then((text) => {
            console.error("Contenido de respuesta no-JSON:", text);
            throw new Error("La respuesta no es JSON válido");
          });
      }

      return response.json();
    })
    .then((data) => {
      console.log("Respuesta del servidor:", data);

      if (data && data.success) {
        console.log("Servidor detenido correctamente");
        statusDiv.className =
          "p-4 flex items-center bg-green-100 text-green-800";
        statusDiv.innerHTML =
          '<i class="fas fa-check-circle mr-2"></i> Deteniendo servidor WebSocket... Por favor espere.';

        // Esperar un tiempo para verificar que el servidor se ha detenido
        setTimeout(() => {
          checkServerStatus();
        }, 2000);
      } else {
        throw new Error(data.message || "No se pudo detener el servidor");
      }
    })
    .catch((error) => {
      console.error("Error al detener servidor:", error);
      statusDiv.className = "p-4 flex items-center bg-red-100 text-red-800";
      statusDiv.innerHTML = `
        <i class="fas fa-times-circle mr-2"></i> Error al detener el servidor: ${error.message}
        <button id="retry-stop" class="ml-3 px-2 py-1 text-xs bg-red-200 hover:bg-red-300 rounded">
          Reintentar
        </button>
        <button id="reload-page" class="ml-2 px-2 py-1 text-xs bg-blue-200 hover:bg-blue-300 rounded">
          Recargar página
        </button>
      `;

      // Agregar listeners a los botones
      document.getElementById("retry-stop")?.addEventListener("click", (e) => {
        e.preventDefault();
        stopServer();
      });

      document.getElementById("reload-page")?.addEventListener("click", (e) => {
        e.preventDefault();
        window.location.reload();
      });
    });
}

/**
 * Carga información de los logs
 */
function loadLogs() {
  const logsTable = document.querySelector(CONFIG.SELECTORS.LOGS_TABLE);
  if (!logsTable) return;

  logsTable.innerHTML =
    '<tr><td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500"><span class="inline-block w-4 h-4 mr-2 border-t-2 border-blue-500 border-r-2 rounded-full animate-spin"></span>Cargando registros...</td></tr>';

  // URL para cargar los logs
  const url = "index.php?option=arduino/listarLogs";
  console.log("Solicitando logs desde URL:", url);

  // Variable para trackear si estamos usando el método alternativo
  let usandoMetodoAlternativo = false;

  // Función para cargar logs desde una URL
  function fetchLogsFromUrl(fetchUrl, fallbackAvailable = true) {
    return fetch(fetchUrl, {
      method: "GET",
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "Cache-Control": "no-cache, no-store, must-revalidate",
        Pragma: "no-cache",
      },
      cache: "no-store",
    })
      .then((response) => {
        console.log(`[${fetchUrl}] Estado respuesta:`, response.status);
        console.log(
          `[${fetchUrl}] Tipo contenido:`,
          response.headers.get("content-type")
        );

        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }

        // Verificar que es JSON antes de parsear
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
          console.warn(`[${fetchUrl}] La respuesta no es JSON:`, contentType);

          // Clonar respuesta para poder examinarla
          return response
            .clone()
            .text()
            .then((text) => {
              console.error(
                `[${fetchUrl}] Contenido no-JSON recibido:`,
                text.substring(0, 500)
              );
              throw new Error("La respuesta no es JSON válido");
            });
        }

        // Clonar respuesta para manejar cualquier error de parsing
        const clonedResponse = response.clone();

        return response.json().catch((error) => {
          // Si hay error al parsear como JSON, examinar el contenido
          return clonedResponse.text().then((text) => {
            console.error(`[${fetchUrl}] Error al parsear JSON:`, error);
            console.error(
              `[${fetchUrl}] Contenido de la respuesta:`,
              text.substring(0, 500)
            );
            throw new Error("Error al parsear JSON: " + error.message);
          });
        });
      })
      .then((data) => {
        // Si llegamos aquí, tenemos datos JSON válidos
        console.log(`[${fetchUrl}] Datos recibidos:`, data);

        if (!data || !Array.isArray(data.logs)) {
          console.error(`[${fetchUrl}] Formato de respuesta incorrecto:`, data);
          throw new Error("Formato de respuesta inválido");
        }

        // Procesar los datos recibidos
        processLogData(data);
        return data;
      })
      .catch((error) => {
        console.error(`[${fetchUrl}] Error:`, error);

        // Si hay un fallback disponible, intentarlo
        if (fallbackAvailable) {
          const fallbackUrl = "scripts/logs_debug.php";

          if (!usandoMetodoAlternativo) {
            usandoMetodoAlternativo = true;
            console.log(`Intentando método alternativo: ${fallbackUrl}`);

            // Actualizar el mensaje en la tabla
            logsTable.innerHTML =
              '<tr><td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">El método principal falló. Intentando método alternativo...</td></tr>';

            // Intentar con el fallback
            return fetchLogsFromUrl(fallbackUrl, false);
          } else {
            // Si ya estamos usando el método alternativo, intentar con test-api
            const lastFallbackUrl = "scripts/test-api.php?endpoint=listarLogs";
            console.log(
              `Intentando último método alternativo: ${lastFallbackUrl}`
            );

            // Actualizar el mensaje en la tabla
            logsTable.innerHTML =
              '<tr><td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">El método alternativo falló. Intentando último recurso...</td></tr>';

            // Intentar con el último fallback
            return fetchLogsFromUrl(lastFallbackUrl, false);
          }
        }

        // Si no hay fallback o todos fallaron, mostrar el error
        logsTable.innerHTML = `
        <tr>
          <td colspan="4" class="px-6 py-4 text-center text-sm text-red-600">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Error al cargar logs: ${error.message}
            <button id="retry-logs" class="ml-3 px-2 py-1 text-xs bg-red-200 hover:bg-red-300 rounded">
              Reintentar
            </button>
          </td>
        </tr>
      `;

        // Agregar listener para reintentar
        document
          .getElementById("retry-logs")
          ?.addEventListener("click", (e) => {
            e.preventDefault();
            loadLogs();
          });

        // Re-lanzar el error para mantener la cadena de promesas en estado de error
        throw error;
      });
  }

  // Función para procesar los datos de logs
  function processLogData(data) {
    // Verificar si hay logs
    if (data.logs.length === 0) {
      logsTable.innerHTML =
        '<tr><td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No hay registros disponibles</td></tr>';
      return;
    }

    // Crear filas de la tabla
    let html = "";
    data.logs.forEach((log) => {
      const fechaFormateada = log.fecha
        ? new Date(log.fecha).toLocaleString()
        : "N/A";
      html += `
        <tr class="bg-white border-b hover:bg-gray-50">
          <td class="px-6 py-4 font-medium text-gray-900">${fechaFormateada}</td>
          <td class="px-6 py-4">${log.tipo || "N/A"}</td>
          <td class="px-6 py-4 truncate max-w-xs">${log.mensaje || "N/A"}</td>
          <td class="px-6 py-4 text-right">
            <button data-log="${
              log.archivo
            }" class="text-blue-600 hover:underline ver-log">Ver detalles</button>
          </td>
        </tr>
      `;
    });
    logsTable.innerHTML = html;

    // Agregar eventos a los botones de ver log
    document.querySelectorAll(".ver-log").forEach((btn) => {
      btn.addEventListener("click", function () {
        const logFile = this.getAttribute("data-log");
        if (logFile) {
          window.open(
            `index.php?option=arduino/verLog&archivo=${encodeURIComponent(
              logFile
            )}`,
            "_blank"
          );
        }
      });
    });
  }

  // Iniciar la carga desde la URL principal
  fetchLogsFromUrl(url);
}

/**
 * Conecta al servidor WebSocket
 */
function connectWebSocket() {
  if (socket) {
    socket.close();
  }

  try {
    console.log("Conectando a WebSocket:", CONFIG.WEBSOCKET_URL);
    socket = new WebSocket(CONFIG.WEBSOCKET_URL);

    socket.onopen = handleSocketOpen;
    socket.onmessage = handleSocketMessage;
    socket.onclose = handleSocketClose;
    socket.onerror = handleSocketError;

    // Actualizar estado de conexión en la UI
    const wsStatus = document.querySelector(CONFIG.SELECTORS.WS_STATUS);
    if (wsStatus) {
      wsStatus.textContent = "Conectando...";
      wsStatus.className = "text-yellow-500";
    }
  } catch (error) {
    console.error("Error al crear WebSocket:", error);
    const wsStatus = document.querySelector(CONFIG.SELECTORS.WS_STATUS);
    if (wsStatus) {
      wsStatus.textContent = "Error";
      wsStatus.className = "text-red-500";
    }
  }
}

/**
 * Maneja la apertura de la conexión WebSocket
 */
function handleSocketOpen() {
  console.log("Conexión WebSocket establecida");

  // Actualizar estado en la UI
  const wsStatus = document.querySelector(CONFIG.SELECTORS.WS_STATUS);
  if (wsStatus) {
    wsStatus.textContent = "Conectado";
    wsStatus.className = "text-green-500";
  }

  // Agregar mensaje al monitor
  const monitorSerial = document.querySelector(CONFIG.SELECTORS.MONITOR_SERIAL);
  if (monitorSerial) {
    appendToMonitor("Conexión establecida con el servidor WebSocket");
  }

  // Reiniciar contador de intentos
  reconnectAttempts = 0;

  // Limpiar timeouts de reconexión
  if (reconnectTimeout) {
    clearTimeout(reconnectTimeout);
    reconnectTimeout = null;
  }
}

/**
 * Maneja los mensajes recibidos del WebSocket
 * @param {MessageEvent} event - Evento de mensaje
 */
function handleSocketMessage(event) {
  try {
    // Parsear el mensaje JSON
    const data = JSON.parse(event.data);

    // Enviar los datos al modelo a través del método setDatosWebSocket
    // Esto permite que el modelo mantenga los datos actualizados
    fetch("index.php?option=arduino/setDatosWebSocket", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
    }).catch((error) => {
      console.error("Error al enviar datos al modelo:", error);
    });

    // Continuar con el procesamiento normal para la interfaz de usuario
    if (data && data.temperature !== undefined) {
      console.log("Datos recibidos del WebSocket:", data);

      // Extraer información
      const temp = data.temperature;
      const isSimulated = data.simulated || false;
      const timestamp = data.timestamp || new Date().toISOString();

      // Actualizar interfaz
      updateTemperatureDisplay(temp, isSimulated, timestamp);

      // Actualizar gráfico
      if (temperatureChart) {
        addTemperatureDataPoint(temp);
      }

      // Añadir al monitor
      const message = `[${new Date().toLocaleTimeString()}] Temperatura: ${temp.toFixed(
        1
      )}°C ${isSimulated ? "(simulado)" : ""}`;
      appendToMonitor(message);

      // Almacenar en caché para uso offline
      appCache.set("lastTemperature", {
        value: temp,
        timestamp: timestamp,
        simulated: isSimulated,
      });
    } else {
      // Es un mensaje de otro tipo (bienvenida, ping, etc.)
      console.log("Mensaje WebSocket recibido:", data);
      if (data.type === "welcome") {
        appendToMonitor(
          `[${new Date().toLocaleTimeString()}] Conectado al servidor: ${
            data.message
          }`
        );
      } else if (data.type === "pong") {
        appendToMonitor(
          `[${new Date().toLocaleTimeString()}] Ping-Pong: latencia OK`
        );
      }
    }
  } catch (error) {
    console.error(
      "Error al procesar mensaje del WebSocket:",
      error,
      event.data
    );
    appendToMonitor(`[ERROR] No se pudo procesar mensaje: ${error.message}`);
  }
}

/**
 * Maneja el cierre de la conexión WebSocket
 * @param {CloseEvent} event - Evento de cierre
 */
function handleSocketClose(event) {
  console.log(`Conexión WebSocket cerrada: ${event.code} ${event.reason}`);

  // Actualizar estado en la UI
  const wsStatus = document.querySelector(CONFIG.SELECTORS.WS_STATUS);
  if (wsStatus) {
    wsStatus.textContent = "Desconectado";
    wsStatus.className = "text-red-500";
  }

  // Agregar mensaje al monitor
  appendToMonitor("Conexión cerrada. Reintentando...");

  // Reconectar automáticamente después de un tiempo
  reconnectAttempts++;
  const delay = Math.min(30000, 1000 * reconnectAttempts); // máximo 30 segundos

  clearTimeout(reconnectTimeout);
  reconnectTimeout = setTimeout(connectWebSocket, delay);
}

/**
 * Maneja errores en la conexión WebSocket
 * @param {Event} error - Evento de error
 */
function handleSocketError(error) {
  console.error("Error en WebSocket:", error);

  // Actualizar estado en la UI
  const wsStatus = document.querySelector(CONFIG.SELECTORS.WS_STATUS);
  if (wsStatus) {
    wsStatus.textContent = "Error";
    wsStatus.className = "text-red-500";
  }

  appendToMonitor("Error de conexión WebSocket");
}

/**
 * Agrega un mensaje al monitor serial
 * @param {string} message - Mensaje a agregar
 */
function appendToMonitor(message) {
  const monitorSerial = document.querySelector(CONFIG.SELECTORS.MONITOR_SERIAL);
  const autoScroll = document.querySelector(CONFIG.SELECTORS.AUTO_SCROLL);

  if (monitorSerial) {
    const timestamp = new Date().toLocaleTimeString();
    monitorSerial.innerHTML += `[${timestamp}] ${message}\n`;

    // Auto-scroll
    if (!autoScroll || autoScroll.checked) {
      monitorSerial.scrollTop = monitorSerial.scrollHeight;
    }
  }
}

/**
 * Reconecta al servidor WebSocket
 */
function reconnectWebSocket() {
  if (socket) {
    socket.close();
  }

  const wsStatus = document.querySelector(CONFIG.SELECTORS.WS_STATUS);
  if (wsStatus) {
    wsStatus.textContent = "Reconectando...";
    wsStatus.className = "text-yellow-500";
  }

  appendToMonitor("Reconectando manualmente...");
  connectWebSocket();
}

/**
 * Actualiza el valor de temperatura en la interfaz
 * @param {number} temperature - Temperatura en grados
 * @param {boolean} isSimulated - Si los datos son simulados
 * @param {string} timestamp - Marca de tiempo
 */
function updateTemperatureDisplay(temperature, isSimulated, timestamp) {
  const temp = parseFloat(temperature).toFixed(1);
  const color = getTemperatureColor(temp);

  const tempDisplay = document.querySelector(CONFIG.SELECTORS.TEMP_DISPLAY);
  const timestampDisplay = document.querySelector(
    CONFIG.SELECTORS.TIMESTAMP_DISPLAY
  );

  if (tempDisplay) {
    tempDisplay.innerHTML = `
      <div class="text-center">
        <div class="text-6xl font-bold mb-2" style="color: ${color}">
          ${temp}°C
        </div>
        <div class="text-sm text-gray-500">
          ${isSimulated ? "Datos simulados" : "Datos reales"}
        </div>
      </div>
    `;
  }

  // Actualizar timestamp
  if (timestampDisplay) {
    if (timestamp) {
      const date = new Date(timestamp);
      timestampDisplay.textContent = date.toLocaleString();
    } else {
      timestampDisplay.textContent = new Date().toLocaleString();
    }
  }
}

/**
 * Obtiene el color en función de la temperatura
 * @param {number} temp - Temperatura
 * @returns {string} - Código de color hexadecimal
 */
function getTemperatureColor(temp) {
  temp = parseFloat(temp);
  if (temp >= 30) return "#e53e3e"; // Rojo (caliente)
  if (temp >= 25) return "#dd6b20"; // Naranja (cálido)
  if (temp >= 18) return "#38a169"; // Verde (normal)
  if (temp >= 10) return "#3182ce"; // Azul (fresco)
  return "#2c5282"; // Azul oscuro (frío)
}

/**
 * Inicializa el gráfico de temperatura
 * @param {string} selector - Selector del elemento canvas
 */
function initTemperatureChart(selector) {
  try {
    // Verificar que el elemento exista
    const chartCanvas = document.querySelector(selector);
    if (!chartCanvas) {
      console.error(
        `Error: No se encontró el elemento '${selector}' para el gráfico`
      );
      return;
    }

    // Verificar que sea un elemento canvas
    if (chartCanvas.tagName.toLowerCase() !== "canvas") {
      console.error(`Error: El elemento '${selector}' no es un canvas válido`);
      return;
    }

    // Obtener el contexto 2D
    const ctx = chartCanvas.getContext("2d");
    if (!ctx) {
      console.error(
        `Error: No se pudo obtener el contexto 2D del canvas '${selector}'`
      );
      return;
    }

    // Crear el gráfico
    temperatureChart = new Chart(ctx, {
      type: "line",
      data: {
        labels: [],
        datasets: [
          {
            label: "Temperatura (°C)",
            data: [],
            borderColor: "#3182ce",
            tension: 0.4,
            fill: false,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: false,
            grid: {
              color: "#e2e8f0",
            },
          },
          x: {
            grid: {
              display: false,
            },
          },
        },
        plugins: {
          legend: {
            display: false,
          },
        },
      },
    });

    console.log("Gráfico de temperatura inicializado correctamente");
  } catch (error) {
    console.error("Error al inicializar el gráfico de temperatura:", error);
  }
}

/**
 * Agrega un nuevo punto de datos al gráfico
 * @param {number} temperature - Temperatura en grados
 */
function addTemperatureDataPoint(temperature) {
  if (!temperatureChart) return;

  const now = new Date();
  const timeStr =
    now.getHours().toString().padStart(2, "0") +
    ":" +
    now.getMinutes().toString().padStart(2, "0") +
    ":" +
    now.getSeconds().toString().padStart(2, "0");

  // Añadir datos al gráfico
  temperatureChart.data.labels.push(timeStr);
  temperatureChart.data.datasets[0].data.push(temperature);

  // Limitar a 20 puntos de datos para no sobrecargar el gráfico
  if (temperatureChart.data.labels.length > 20) {
    temperatureChart.data.labels.shift();
    temperatureChart.data.datasets[0].data.shift();
  }

  temperatureChart.update();
}

/**
 * Envía un comando al servidor WebSocket
 * @param {string} command - Comando a enviar
 */
function sendCommand(command) {
  if (socket && socket.readyState === WebSocket.OPEN) {
    socket.send(command);
    return true;
  }
  return false;
}

/**
 * Verifica si el WebSocket está conectado
 * @returns {boolean} - True si está conectado
 */
function isConnected() {
  return socket && socket.readyState === WebSocket.OPEN;
}

// Exportar funciones públicas
export { init, sendCommand, isConnected, reconnectWebSocket };
