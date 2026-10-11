-- =====================================================================
-- FA-Digital: Datenbankschema
-- Digitaler Fertigungsauftrag mit Zeiterfassung, Checklisten,
-- Werkszertifikat und Nachkalkulation
--
-- Datenbank: MariaDB 10.4 (XAMPP), Zeichensatz utf8mb4
-- Ausführen:  mysql -u root < 01_schema.sql
--             oder in phpMyAdmin unter „Importieren“
--
-- Aufbau in vier Bereichen:
--   1. Stammdaten      benutzer, arbeitsplatz, artikel, arbeitsplan_position,
--                      zertifikatsart, pruefmerkmal, unterbrechungsgrund
--   2. Auftragsdaten   fertigungsauftrag, auftrag_zertifikat, arbeitsgang, pruefpunkt
--   3. Rückmeldungen   zeitbuchung, unterbrechung
--   4. Historie        arbeitsplan_aenderung
-- =====================================================================

DROP DATABASE IF EXISTS fa_digital;
CREATE DATABASE fa_digital
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_german2_ci;

USE fa_digital;

-- ---------------------------------------------------------------------
-- 1. Stammdaten
-- ---------------------------------------------------------------------

-- Alle Personen, die sich anmelden. Die Rolle bestimmt die sichtbaren Seiten.
CREATE TABLE benutzer (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  personalnummer  VARCHAR(10)   NOT NULL,
  vorname         VARCHAR(50)   NOT NULL,
  nachname        VARCHAR(50)   NOT NULL,
  email           VARCHAR(100)  NOT NULL,
  passwort_hash   VARCHAR(255)  NOT NULL COMMENT 'Ergebnis von password_hash() in PHP, nie das Passwort selbst',
  rolle           ENUM('arbeitsvorbereitung', 'werker', 'meister', 'controlling', 'admin') NOT NULL,
  aktiv           TINYINT(1)    NOT NULL DEFAULT 1,
  angelegt_am     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_benutzer_personalnummer (personalnummer),
  UNIQUE KEY uq_benutzer_email (email)
) ENGINE = InnoDB;

-- Maschinen und Arbeitsplätze mit Stundensatz für die Kalkulation
CREATE TABLE arbeitsplatz (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  nummer          VARCHAR(10)   NOT NULL COMMENT 'z. B. AP-20',
  bezeichnung     VARCHAR(80)   NOT NULL,
  kostenstelle    VARCHAR(10)   NOT NULL,
  stundensatz     DECIMAL(8,2)  NOT NULL COMMENT 'Euro pro Stunde, enthält Maschinen- und Personalkosten',
  aktiv           TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_arbeitsplatz_nummer (nummer),
  CONSTRAINT ck_arbeitsplatz_stundensatz CHECK (stundensatz >= 0)
) ENGINE = InnoDB;

-- Gefertigte Teile
CREATE TABLE artikel (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  artikelnummer   VARCHAR(20)   NOT NULL,
  bezeichnung     VARCHAR(100)  NOT NULL,
  aktiv           TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_artikel_artikelnummer (artikelnummer)
) ENGINE = InnoDB;

-- Arbeitsplan: welche Arbeitsgänge ein Artikel durchläuft und wie lange sie dauern sollen
CREATE TABLE arbeitsplan_position (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  artikel_id      INT UNSIGNED  NOT NULL,
  ag_nummer       SMALLINT UNSIGNED NOT NULL COMMENT 'Reihenfolge, z. B. 10, 20, 30',
  bezeichnung     VARCHAR(80)   NOT NULL,
  arbeitsplatz_id INT UNSIGNED  NOT NULL,
  ruestzeit_min   DECIMAL(7,2)  NOT NULL DEFAULT 0 COMMENT 'Soll-Rüstzeit je Auftrag in Minuten',
  stueckzeit_min  DECIMAL(7,2)  NOT NULL COMMENT 'Soll-Bearbeitungszeit je Stück in Minuten',
  PRIMARY KEY (id),
  UNIQUE KEY uq_arbeitsplan_artikel_ag (artikel_id, ag_nummer),
  CONSTRAINT fk_arbeitsplan_artikel      FOREIGN KEY (artikel_id)      REFERENCES artikel (id),
  CONSTRAINT fk_arbeitsplan_arbeitsplatz FOREIGN KEY (arbeitsplatz_id) REFERENCES arbeitsplatz (id),
  CONSTRAINT ck_arbeitsplan_zeiten CHECK (ruestzeit_min >= 0 AND stueckzeit_min >= 0)
) ENGINE = InnoDB;

-- Arten von Werkszertifikaten, die ein Kunde fordern kann (DIN EN 10204)
CREATE TABLE zertifikatsart (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  kuerzel         VARCHAR(10)   NOT NULL COMMENT 'z. B. 2.2',
  bezeichnung     VARCHAR(80)   NOT NULL,
  beschreibung    VARCHAR(255)  NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_zertifikatsart_kuerzel (kuerzel)
) ENGINE = InnoDB;

-- Vorlage der Checkliste: Prüfpunkte je Arbeitsgang im Arbeitsplan.
-- zertifikatsart_id leer = wird immer geprüft,
-- gesetzt = wird nur geprüft, wenn der Kunde diese Zertifikatsart fordert.
CREATE TABLE pruefmerkmal (
  id                      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  arbeitsplan_position_id INT UNSIGNED  NOT NULL,
  reihenfolge             SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  bezeichnung             VARCHAR(120)  NOT NULL,
  art                     ENUM('sichtpruefung', 'messwert') NOT NULL,
  sollwert                DECIMAL(10,3) NULL,
  untere_grenze           DECIMAL(10,3) NULL,
  obere_grenze            DECIMAL(10,3) NULL,
  einheit                 VARCHAR(10)   NULL,
  zertifikatsart_id       INT UNSIGNED  NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_pruefmerkmal_position       FOREIGN KEY (arbeitsplan_position_id) REFERENCES arbeitsplan_position (id) ON DELETE CASCADE,
  CONSTRAINT fk_pruefmerkmal_zertifikatsart FOREIGN KEY (zertifikatsart_id)       REFERENCES zertifikatsart (id),
  CONSTRAINT ck_pruefmerkmal_messwert CHECK (
    art = 'sichtpruefung'
    OR (untere_grenze IS NOT NULL AND obere_grenze IS NOT NULL AND untere_grenze <= obere_grenze)
  )
) ENGINE = InnoDB;

-- Mögliche Gründe für eine Unterbrechung am Werker-Terminal
CREATE TABLE unterbrechungsgrund (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  bezeichnung     VARCHAR(50)   NOT NULL,
  geplant         TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = geplant (Pause), zählt nicht als Zeitverlust',
  aktiv           TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_unterbrechungsgrund_bezeichnung (bezeichnung)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 2. Auftragsdaten
-- ---------------------------------------------------------------------

CREATE TABLE fertigungsauftrag (
  id                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  auftragsnummer     VARCHAR(20)   NOT NULL COMMENT 'z. B. FA-1002',
  artikel_id         INT UNSIGNED  NOT NULL,
  menge              INT UNSIGNED  NOT NULL,
  kunde              VARCHAR(100)  NULL,
  liefertermin       DATE          NOT NULL,
  status             ENUM('angelegt', 'freigegeben', 'in_arbeit', 'fertig', 'nachkalkuliert', 'storniert') NOT NULL DEFAULT 'angelegt',
  bemerkung          VARCHAR(255)  NULL,
  angelegt_von       INT UNSIGNED  NOT NULL,
  angelegt_am        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  freigegeben_von    INT UNSIGNED  NULL,
  freigegeben_am     DATETIME      NULL,
  abgeschlossen_von  INT UNSIGNED  NULL,
  abgeschlossen_am   DATETIME      NULL,
  nachkalkuliert_am  DATETIME      NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_auftrag_nummer (auftragsnummer),
  KEY ix_auftrag_status (status),
  CONSTRAINT fk_auftrag_artikel        FOREIGN KEY (artikel_id)        REFERENCES artikel (id),
  CONSTRAINT fk_auftrag_angelegt_von   FOREIGN KEY (angelegt_von)      REFERENCES benutzer (id),
  CONSTRAINT fk_auftrag_freigegeben    FOREIGN KEY (freigegeben_von)   REFERENCES benutzer (id),
  CONSTRAINT fk_auftrag_abgeschlossen  FOREIGN KEY (abgeschlossen_von) REFERENCES benutzer (id),
  CONSTRAINT ck_auftrag_menge CHECK (menge > 0)
) ENGINE = InnoDB;

-- Welche Werkszertifikate der Kunde für diesen Auftrag fordert.
-- Wird schon bei der Auftragsanlage festgelegt (Rückmeldung vom 01.10.2026).
CREATE TABLE auftrag_zertifikat (
  id                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  fertigungsauftrag_id  INT UNSIGNED  NOT NULL,
  zertifikatsart_id     INT UNSIGNED  NOT NULL,
  status                ENUM('gefordert', 'erstellt', 'freigegeben') NOT NULL DEFAULT 'gefordert',
  erstellt_am           DATETIME      NULL,
  freigegeben_von       INT UNSIGNED  NULL,
  freigegeben_am        DATETIME      NULL,
  dateiname             VARCHAR(200)  NULL COMMENT 'abgelegtes PDF für den Kunden',
  PRIMARY KEY (id),
  UNIQUE KEY uq_auftrag_zertifikat (fertigungsauftrag_id, zertifikatsart_id),
  CONSTRAINT fk_zertifikat_auftrag      FOREIGN KEY (fertigungsauftrag_id) REFERENCES fertigungsauftrag (id),
  CONSTRAINT fk_zertifikat_art          FOREIGN KEY (zertifikatsart_id)    REFERENCES zertifikatsart (id),
  CONSTRAINT fk_zertifikat_freigegeben  FOREIGN KEY (freigegeben_von)      REFERENCES benutzer (id),
  CONSTRAINT ck_zertifikat_freigabe CHECK (status <> 'freigegeben' OR (freigegeben_von IS NOT NULL AND freigegeben_am IS NOT NULL))
) ENGINE = InnoDB;

-- Arbeitsgänge eines Auftrags. Beim Anlegen werden die Werte aus dem Arbeitsplan
-- kopiert. So bleiben Soll-Zeiten und Stundensatz eines Auftrags gleich, auch wenn
-- der Arbeitsplan später angepasst wird.
CREATE TABLE arbeitsgang (
  id                      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  fertigungsauftrag_id    INT UNSIGNED  NOT NULL,
  arbeitsplan_position_id INT UNSIGNED  NULL COMMENT 'Herkunft im Arbeitsplan',
  ag_nummer               SMALLINT UNSIGNED NOT NULL,
  bezeichnung             VARCHAR(80)   NOT NULL,
  arbeitsplatz_id         INT UNSIGNED  NOT NULL,
  soll_ruestzeit_min      DECIMAL(7,2)  NOT NULL,
  soll_stueckzeit_min     DECIMAL(7,2)  NOT NULL,
  stundensatz             DECIMAL(8,2)  NOT NULL COMMENT 'Stundensatz zum Zeitpunkt der Auftragsanlage',
  status                  ENUM('offen', 'laeuft', 'unterbrochen', 'erledigt') NOT NULL DEFAULT 'offen',
  gutmenge                INT UNSIGNED  NULL,
  ausschuss               INT UNSIGNED  NULL,
  fertig_gemeldet_von     INT UNSIGNED  NULL,
  fertig_gemeldet_am      DATETIME      NULL,
  bemerkung               VARCHAR(255)  NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_arbeitsgang_auftrag_ag (fertigungsauftrag_id, ag_nummer),
  KEY ix_arbeitsgang_platz_status (arbeitsplatz_id, status),
  CONSTRAINT fk_arbeitsgang_auftrag     FOREIGN KEY (fertigungsauftrag_id)    REFERENCES fertigungsauftrag (id),
  CONSTRAINT fk_arbeitsgang_position    FOREIGN KEY (arbeitsplan_position_id) REFERENCES arbeitsplan_position (id) ON DELETE SET NULL,
  CONSTRAINT fk_arbeitsgang_platz       FOREIGN KEY (arbeitsplatz_id)         REFERENCES arbeitsplatz (id),
  CONSTRAINT fk_arbeitsgang_gemeldet    FOREIGN KEY (fertig_gemeldet_von)     REFERENCES benutzer (id),
  CONSTRAINT ck_arbeitsgang_erledigt CHECK (status <> 'erledigt' OR (gutmenge IS NOT NULL AND ausschuss IS NOT NULL AND fertig_gemeldet_am IS NOT NULL))
) ENGINE = InnoDB;

-- Checkliste eines Arbeitsgangs: Kopie der Prüfmerkmale bei der Auftragsanlage
-- und das Ergebnis, das der Werker einträgt. Grundlage für das Werkszertifikat.
CREATE TABLE pruefpunkt (
  id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  arbeitsgang_id   INT UNSIGNED  NOT NULL,
  pruefmerkmal_id  INT UNSIGNED  NULL COMMENT 'Herkunft in der Vorlage',
  reihenfolge      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  bezeichnung      VARCHAR(120)  NOT NULL,
  art              ENUM('sichtpruefung', 'messwert') NOT NULL,
  sollwert         DECIMAL(10,3) NULL,
  untere_grenze    DECIMAL(10,3) NULL,
  obere_grenze     DECIMAL(10,3) NULL,
  einheit          VARCHAR(10)   NULL,
  istwert          DECIMAL(10,3) NULL,
  ergebnis         ENUM('offen', 'in_ordnung', 'nicht_in_ordnung') NOT NULL DEFAULT 'offen',
  bemerkung        VARCHAR(255)  NULL,
  geprueft_von     INT UNSIGNED  NULL,
  geprueft_am      DATETIME      NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_pruefpunkt_arbeitsgang FOREIGN KEY (arbeitsgang_id)  REFERENCES arbeitsgang (id),
  CONSTRAINT fk_pruefpunkt_merkmal     FOREIGN KEY (pruefmerkmal_id) REFERENCES pruefmerkmal (id) ON DELETE SET NULL,
  CONSTRAINT fk_pruefpunkt_geprueft    FOREIGN KEY (geprueft_von)    REFERENCES benutzer (id),
  CONSTRAINT ck_pruefpunkt_ergebnis CHECK (ergebnis = 'offen' OR (geprueft_von IS NOT NULL AND geprueft_am IS NOT NULL)),
  CONSTRAINT ck_pruefpunkt_messwert CHECK (art = 'sichtpruefung' OR ergebnis = 'offen' OR istwert IS NOT NULL)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 3. Rückmeldungen aus der Werkstatt
-- ---------------------------------------------------------------------

-- Zeitbuchungen für Rüsten und Bearbeiten. Buchungen werden nie gelöscht:
-- Eine Korrektur legt eine neue Buchung an und setzt die alte auf gueltig = 0.
CREATE TABLE zeitbuchung (
  id                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  arbeitsgang_id      INT UNSIGNED  NOT NULL,
  benutzer_id         INT UNSIGNED  NOT NULL COMMENT 'Werker, für den gebucht wird',
  taetigkeit          ENUM('ruesten', 'bearbeiten') NOT NULL,
  beginn              DATETIME      NOT NULL,
  ende                DATETIME      NULL COMMENT 'leer = Buchung läuft noch',
  quelle              ENUM('terminal', 'nachtrag', 'korrektur') NOT NULL DEFAULT 'terminal',
  gueltig             TINYINT(1)    NOT NULL DEFAULT 1,
  ersetzt_buchung_id  INT UNSIGNED  NULL COMMENT 'bei einer Korrektur: die ersetzte Buchung',
  begruendung         VARCHAR(255)  NULL,
  erfasst_von         INT UNSIGNED  NOT NULL,
  erfasst_am          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_zeitbuchung_arbeitsgang (arbeitsgang_id, gueltig),
  CONSTRAINT fk_zeitbuchung_arbeitsgang FOREIGN KEY (arbeitsgang_id)     REFERENCES arbeitsgang (id),
  CONSTRAINT fk_zeitbuchung_benutzer    FOREIGN KEY (benutzer_id)        REFERENCES benutzer (id),
  CONSTRAINT fk_zeitbuchung_ersetzt     FOREIGN KEY (ersetzt_buchung_id) REFERENCES zeitbuchung (id),
  CONSTRAINT fk_zeitbuchung_erfasst     FOREIGN KEY (erfasst_von)        REFERENCES benutzer (id),
  CONSTRAINT ck_zeitbuchung_zeitraum CHECK (ende IS NULL OR ende > beginn),
  -- Nachträge und Korrekturen brauchen immer eine Begründung
  CONSTRAINT ck_zeitbuchung_begruendung CHECK (quelle = 'terminal' OR begruendung IS NOT NULL)
) ENGINE = InnoDB;

-- Unterbrechungen mit Grund. Ihre Dauer zählt nicht zur Ist-Zeit des Arbeitsgangs.
CREATE TABLE unterbrechung (
  id                      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  arbeitsgang_id          INT UNSIGNED  NOT NULL,
  benutzer_id             INT UNSIGNED  NOT NULL,
  unterbrechungsgrund_id  INT UNSIGNED  NOT NULL,
  beginn                  DATETIME      NOT NULL,
  ende                    DATETIME      NULL COMMENT 'leer = Unterbrechung dauert an',
  bemerkung               VARCHAR(255)  NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_unterbrechung_arbeitsgang FOREIGN KEY (arbeitsgang_id)         REFERENCES arbeitsgang (id),
  CONSTRAINT fk_unterbrechung_benutzer    FOREIGN KEY (benutzer_id)            REFERENCES benutzer (id),
  CONSTRAINT fk_unterbrechung_grund       FOREIGN KEY (unterbrechungsgrund_id) REFERENCES unterbrechungsgrund (id),
  CONSTRAINT ck_unterbrechung_zeitraum CHECK (ende IS NULL OR ende > beginn)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 4. Historie
-- ---------------------------------------------------------------------

-- Übernahme von Abweichungen in die neue Kalkulation (Rückmeldung vom 01.10.2026):
-- Jede Änderung einer Soll-Zeit im Arbeitsplan wird mit altem und neuem Wert festgehalten.
CREATE TABLE arbeitsplan_aenderung (
  id                      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  arbeitsplan_position_id INT UNSIGNED  NOT NULL,
  feld                    ENUM('ruestzeit', 'stueckzeit') NOT NULL,
  alter_wert              DECIMAL(7,2)  NOT NULL,
  neuer_wert              DECIMAL(7,2)  NOT NULL,
  begruendung             VARCHAR(255)  NOT NULL,
  geaendert_von           INT UNSIGNED  NOT NULL,
  geaendert_am            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_aenderung_position FOREIGN KEY (arbeitsplan_position_id) REFERENCES arbeitsplan_position (id) ON DELETE CASCADE,
  CONSTRAINT fk_aenderung_benutzer FOREIGN KEY (geaendert_von)           REFERENCES benutzer (id)
) ENGINE = InnoDB;
