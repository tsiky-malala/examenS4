CREATE DATABASE exam CHARACTER SET utf8mb4;

USE exam;

CREATE TABLE etablissement_fonds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    montant DOUBLE NOT NULL,
    date_ajout DATE NOT NULL
);

CREATE TABLE IF NOT EXISTS type_pret (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  libelle VARCHAR(100) NOT NULL,
  taux    DOUBLE       NOT NULL           -- % annuel
);


CREATE TABLE IF NOT EXISTS client (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nom    VARCHAR(100),
  prenom VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS pret (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  client_id     INT,
  type_pret_id  INT,
  montant       DOUBLE,
  date_debut    DATE,
  duree_mois    INT,                      -- nombre d’échéances
  mensualite    DOUBLE,                   -- annuité constante (hors assurance)
  FOREIGN KEY (client_id)    REFERENCES client(id),
  FOREIGN KEY (type_pret_id) REFERENCES type_pret(id)
);

CREATE TABLE IF NOT EXISTS remboursement (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  pret_id           INT,
  no_mois           INT,   -- 1 … n
  annee             INT,
  mois              INT,
  capital_restant   DOUBLE,
  amortissement     DOUBLE,
  interet           DOUBLE,
  mensualite_totale DOUBLE,
  FOREIGN KEY (pret_id) REFERENCES pret(id)
);

CREATE TABLE IF NOT EXISTS interet_mensuel (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  pret_id   INT,
  mois      INT,
  annee     INT,
  interet   DOUBLE,
  FOREIGN KEY (pret_id) REFERENCES pret(id)
);

CREATE OR REPLACE VIEW v_interet_client_mois AS
SELECT
    c.id            AS client_id,
    CONCAT(c.prenom, ' ', c.nom) AS client,
    im.annee,
    im.mois,
    SUM(im.interet) AS total_interet
FROM interet_mensuel im
JOIN pret p   ON im.pret_id = p.id
JOIN client c ON p.client_id = c.id
GROUP BY c.id, im.annee, im.mois;
