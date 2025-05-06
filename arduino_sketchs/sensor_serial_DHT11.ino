/*
 * sensor_serial_DHT11.ino
 * Sketch de Arduino para leer sensor DHT11 y enviar datos por puerto serial
 * 
 * Este programa lee los valores de temperatura de un sensor DHT11 y los envía
 * a través del puerto serial para que puedan ser leídos por la aplicación PHP.
 */

// Incluir las bibliotecas necesarias para el sensor DHT11
#include <DHT.h>
#include <DHT_U.h>

// Definir el pin y tipo del sensor DHT11
#define DHTPIN 2      // Sensor DHT11 conectado al pin digital 2
#define DHTTYPE DHT11 // Tipo de sensor DHT11

// Crear objeto DHT
DHT dht(DHTPIN, DHTTYPE);

// Intervalo para enviar datos (en milisegundos)
const unsigned long INTERVALO_ENVIO = 1000;  // Enviar datos cada 1 segundo

// Variable para almacenar el tiempo de la última lectura
unsigned long tiempoUltimaLectura = 0;

void setup() {
  // Inicializar comunicación serial a 9600 baudios
  Serial.begin(9600);
  
  // Inicializar sensor DHT11
  dht.begin();
  
  // Esperar a que se establezca la comunicación serial
  delay(100);
  
  // Enviar mensaje de inicio
  Serial.println("Arduino con DHT11 iniciado correctamente");
}

void loop() {
  // Verificar si ha pasado el intervalo de tiempo
  unsigned long tiempoActual = millis();
  
  if (tiempoActual - tiempoUltimaLectura >= INTERVALO_ENVIO) {
    // Actualizar tiempo de última lectura
    tiempoUltimaLectura = tiempoActual;
    
    // Leer temperatura del sensor DHT11
    float temperatura = dht.readTemperature();
    
    // Verificar si la lectura fue exitosa
    if (!isnan(temperatura)) {
      // Enviar dato con formato clave:valor
      Serial.print("temperatura:");
      Serial.println(temperatura);
      
      // Enviar una línea en blanco para indicar el final del dato
      Serial.println();
    }
  }
  
  // Pequeña pausa para estabilidad
  delay(10);
}

/*
 * Nota sobre el sensor DHT11:
 * 
 * - El DHT11 es un sensor digital de temperatura y humedad
 * - Rango de temperatura: 0°C a 50°C
 * - Precisión de temperatura: ±2°C
 * - Conexión típica:
 *   - Pin 1 (VCC): +5V
 *   - Pin 2 (DATA): Pin digital (con pull-up de 10kΩ)
 *   - Pin 3 (NC): No conectado
 *   - Pin 4 (GND): GND
 * 
 * Requisitos:
 * - Instalar las bibliotecas DHT y DHT_U desde el Gestor de Bibliotecas de Arduino
 * - Usar una resistencia pull-up de 10kΩ entre VCC y DATA
 * - No realizar lecturas más frecuentes que cada 1-2 segundos
 */ 