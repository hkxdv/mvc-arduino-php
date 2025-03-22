-- Tabla de enlaces para el sistema de navegación
CREATE TABLE 
enlaces (
    pk_enlaces SERIAL PRIMARY KEY NOT NULL,
    nombre VARCHAR(55) NOT NULL,
    ruta VARCHAR(100) NOT NULL,
    hora TIME NOT NULL DEFAULT CURRENT_TIME,
    fecha DATE NOT NULL DEFAULT CURRENT_DATE,
    estado SMALLINT NOT NULL DEFAULT 1
);

-- Insertar enlaces para los modulos de Arduino
INSERT INTO enlaces (nombre, ruta) VALUES 
('arduino', 'app/Views/modules/arduino/listar_sensores.modulo.php'),
('arduino/mostrar', 'app/Views/modules/arduino/mostrar_sensor.modulo.php'),
('arduino/configurar', 'app/Views/modules/arduino/configurar_arduino.modulo.php');

INSERT INTO enlaces (nombre, ruta) VALUES 
('arduino/diagnostico', 'app/Views/modules/arduino/diagnostico.modulo.php'),
('arduino/webserver', 'app/Views/modules/arduino/webserver.modulo.php');