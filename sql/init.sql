CREATE TABLE IF NOT EXISTS mesas (
  id VARCHAR(64) PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  estado ENUM('abierta','cerrada') DEFAULT 'abierta',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO mesas (id, nombre, estado) VALUES
('mesaDungeonAndCyberpunk', 'Dungeon and Cyberpunk', 'abierta')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
-- Jugadores anónimos y PJs
CREATE TABLE IF NOT EXISTS jugadores (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  anon_uid CHAR(22) UNIQUE,
  first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_seen  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pjs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  jugador_id BIGINT NOT NULL,
  mesa_id VARCHAR(64) NOT NULL,
  nombre VARCHAR(80),
  profesion VARCHAR(80),
  avatar VARCHAR(120),
  dinero INT DEFAULT 20,
  atributos_json JSON,
  habilidades_json JSON,
  conocimientos_json JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (jugador_id) REFERENCES jugadores(id),
  FOREIGN KEY (mesa_id) REFERENCES mesas(id)
);

-- Catálogo simple para el wizard
CREATE TABLE IF NOT EXISTS profesiones (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(80) UNIQUE,
  bono_json JSON
);

INSERT IGNORE INTO profesiones (nombre, bono_json) VALUES
('Investigador', '{"mente":2,"voluntad":1}'),
('Matón',        '{"cuerpo":2,"voluntad":1}'),
('Médico',       '{"mente":1,"voluntad":2}');


ALTER TABLE personaje_jugador
  DROP FOREIGN KEY personaje_jugador_ibfk_1;

ALTER TABLE personaje_jugador
  DROP INDEX uq_personaje_jugador;

ALTER TABLE personaje_jugador
  DROP COLUMN jugador_id;


ALTER TABLE jugadores
  ADD COLUMN personaje_jugador_id BIGINT NULL,
  ADD CONSTRAINT fk_jugadores_personaje_jugador
    FOREIGN KEY (personaje_jugador_id)
    REFERENCES personaje_jugador(id);


-- Único por nombre dentro de la mesa (sirve para login y para upsert de pass)
ALTER TABLE personaje_jugador
  ADD CONSTRAINT uq_pj_mesa_nombre UNIQUE (mesa_id, nombre);
ALTER TABLE jugadores
  MODIFY COLUMN personaje_jugador_id BIGINT NOT NULL,
  ADD CONSTRAINT uq_jugadores_pj UNIQUE (personaje_jugador_id);
ALTER TABLE jugadores
  MODIFY COLUMN personaje_jugador_id BIGINT NULL;
-- si habías puesto UNIQUE sobre esa columna, lo podés dejar: múltiples NULL están permitidos
