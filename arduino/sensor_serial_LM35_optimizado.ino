const int T = A5;
const unsigned long I = 1000;
unsigned long L = 0;
void setup() {
  Serial.begin(9600);
  pinMode(T, INPUT);
  delay(100);
}
void loop() {
  unsigned long C = millis();
  if (C - L >= I) {
    L = C;
    float t = (analogRead(T) * 5.0 / 1023.0) * 100.0;
    Serial.println(t);
  }
}