
CREATE DATABASE IF NOT EXISTS cv_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE cv_db;

CREATE TABLE IF NOT EXISTS utilisateur (
  email     VARCHAR(100) PRIMARY KEY,
  nom       VARCHAR(50)  NOT NULL,
  prenom    VARCHAR(50)  NOT NULL,
  poste     VARCHAR(100),
  telephone VARCHAR(20),
  adresse   VARCHAR(255),
  linkedin  VARCHAR(255),
  profil    TEXT,
  photo     VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS formation (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(100) NOT NULL,
  intitule      VARCHAR(150) NOT NULL,
  etablissement VARCHAR(150),
  date_debut    DATE,
  date_fin      DATE,
  FOREIGN KEY (email) REFERENCES utilisateur(email)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stage (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  email       VARCHAR(100) NOT NULL,
  entreprise  VARCHAR(100) NOT NULL,
  poste       VARCHAR(100),
  lieu        VARCHAR(100),
  date_debut  DATE,
  date_fin    DATE,
  description TEXT,
  FOREIGN KEY (email) REFERENCES utilisateur(email)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS competence (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  email   VARCHAR(100) NOT NULL,
  libelle VARCHAR(100) NOT NULL,
  niveau  VARCHAR(30),
  FOREIGN KEY (email) REFERENCES utilisateur(email)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS langue (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  email  VARCHAR(100) NOT NULL,
  langue VARCHAR(50) NOT NULL,
  niveau VARCHAR(30),
  FOREIGN KEY (email) REFERENCES utilisateur(email)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS centre_interet (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  email   VARCHAR(100) NOT NULL,
  libelle VARCHAR(100) NOT NULL,
  FOREIGN KEY (email) REFERENCES utilisateur(email)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projet (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  email       VARCHAR(100) NOT NULL,
  titre       VARCHAR(150) NOT NULL,
  description TEXT,
  FOREIGN KEY (email) REFERENCES utilisateur(email)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS soft_skill (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  email   VARCHAR(100) NOT NULL,
  libelle VARCHAR(100) NOT NULL,
  FOREIGN KEY (email) REFERENCES utilisateur(email)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Partie facultative : gestion des candidatures de stage (ATS simplifié)
-- À coller à la FIN de shema.sql

CREATE TABLE IF NOT EXISTS offre (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  entreprise  VARCHAR(100) NOT NULL,
  intitule    VARCHAR(150) NOT NULL,
  description TEXT,
  date_limite DATE
) ENGINE=InnoDB;

-- compétences demandées par une offre
CREATE TABLE IF NOT EXISTS offre_competence (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  offre_id INT NOT NULL,
  libelle  VARCHAR(100) NOT NULL,
  FOREIGN KEY (offre_id) REFERENCES offre(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- un étudiant (email) postule à une offre ; une seule candidature par offre
CREATE TABLE IF NOT EXISTS candidature (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  offre_id   INT NOT NULL,
  email      VARCHAR(100) NOT NULL,
  date_envoi DATE,
  statut     VARCHAR(30) DEFAULT 'reçue',
  UNIQUE (offre_id, email),
  FOREIGN KEY (offre_id) REFERENCES offre(id) ON DELETE CASCADE,
  FOREIGN KEY (email) REFERENCES utilisateur(email)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;