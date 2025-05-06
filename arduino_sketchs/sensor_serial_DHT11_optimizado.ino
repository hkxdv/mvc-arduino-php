#include <DHT.h>
#include <DHT_U.h>

#define DHTPIN 2
#define DHTTYPE DHT11

DHT dht(DHTPIN, DHTTYPE);
const unsigned long I = 1000;
unsigned long L = 0;

void setup() {
  Serial.begin(9600);
  dht.begin();
  delay(100);
}

void loop() {
  unsigned long C = millis();
  if (C - L >= I) {
    L = C;
    float t = dht.readTemperature();
    if (!isnan(t)) {
      Serial.println(t);
    }
  }
} 