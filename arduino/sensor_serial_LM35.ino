/*
 * sensor_serial.ino
 * Sketch de Arduino para leer sensor LM35 y enviar datos por puerto serial
 * 
 * Este programa lee los valores de un sensor de temperatura LM35 y los envía
 * a través del puerto serial para que puedan ser leídos por la aplicación PHP.
 */

// Definir el pin del sensor LM35
const int PIN_TEMPERATURA = A0;  // Sensor LM35 conectado al pin analógico A0

// Intervalo para enviar datos (en milisegundos)
const unsigned long INTERVALO_ENVIO = 1000;  // Enviar datos cada 1 segundo

// Variable para almacenar el tiempo de la última lectura
unsigned long tiempoUltimaLectura = 0;

void setup() {
  // Inicializar comunicación serial a 9600 baudios
  Serial.begin(9600);
  
  // Configurar pin del sensor
  pinMode(PIN_TEMPERATURA, INPUT);
  
  // Esperar a que se establezca la comunicación serial
  delay(100);
  
  // Enviar mensaje de inicio
  Serial.println("Arduino con LM35 iniciado correctamente");
}

void loop() {
  // Verificar si ha pasado el intervalo de tiempo
  unsigned long tiempoActual = millis();
  
  if (tiempoActual - tiempoUltimaLectura >= INTERVALO_ENVIO) {
    // Actualizar tiempo de última lectura
    tiempoUltimaLectura = tiempoActual;
    
    // Leer valor del sensor de temperatura
    int valorTemperatura = analogRead(PIN_TEMPERATURA);
    
    // Convertir lectura analógica a temperatura en grados Celsius
    // Para LM35: Temperatura(°C) = (Valor analógico * 5.0 / 1023.0) * 100.0
    float temperatura = (valorTemperatura * 5.0 / 1023.0) * 100.0;
    
    // Enviar dato con formato clave:valor
    Serial.print("temperatura:");
    Serial.println(temperatura);
    
    // Enviar una línea en blanco para indicar el final del dato
    Serial.println();
  }
  
  // Pequeña pausa para estabilidad
  delay(10);
}

/*
 * Nota sobre el sensor LM35:
 * 
 * - El LM35 es un sensor de temperatura de precisión con salida analógica
 * - La salida es lineal y proporcional a la temperatura: 10mV/°C
 * - Rango típico: -55°C a 150°C (el modelo básico: 0°C a 100°C)
 * - Conexión típica:
 *   - Pin izquierdo (mirando la parte plana): +5V
 *   - Pin central: Señal (conectar a pin analógico)
 *   - Pin derecho: GND
 */ 