-- Création et sélection de la base de données
CREATE DATABASE IF NOT EXISTS adh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Sélection explicite de la base
USE adh;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    -- Champs additionnels pour OAuth et compatibilité
    username VARCHAR(100) DEFAULT NULL,
    password VARCHAR(255) DEFAULT NULL,
    oauth_provider VARCHAR(50) DEFAULT NULL,
    oauth_uid VARCHAR(255) DEFAULT NULL,
    oauth_token TEXT DEFAULT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    role ENUM('etudiant', 'professeur', 'admin') DEFAULT 'etudiant',
    avatar VARCHAR(255) DEFAULT 'default.png',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    email_verifie BOOLEAN DEFAULT FALSE,
    token_verification VARCHAR(255),
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    mode_sombre BOOLEAN DEFAULT FALSE
);

-- Table des cours
CREATE TABLE IF NOT EXISTS cours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    image_cours VARCHAR(255),
    niveau ENUM('debutant', 'intermediaire', 'avance') DEFAULT 'debutant',
    duree INT,
    prix DECIMAL(10,2) DEFAULT 0.00,
    formateur_id INT,
    type ENUM('presentiel', 'en_ligne') DEFAULT 'en_ligne',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('brouillon', 'publie', 'archive') DEFAULT 'brouillon',
    FOREIGN KEY (formateur_id) REFERENCES utilisateurs(id)
);

-- Table des inscriptions
CREATE TABLE IF NOT EXISTS inscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT,
    cours_id INT,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    progression INT DEFAULT 0,
    date_completion DATETIME NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (cours_id) REFERENCES cours(id)
);

-- 1. CRÉER LA TABLE FORMATIONS
CREATE TABLE IF NOT EXISTS formations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    statut ENUM('brouillon', 'en_cours', 'termine') DEFAULT 'brouillon',
    date_disponibilite DATE NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_statut (statut),
    INDEX idx_date_creation (date_creation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des modules
CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cours_id INT,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    ordre INT NOT NULL,
    duree_estimee INT,
    FOREIGN KEY (cours_id) REFERENCES cours(id)
);



-- 2. AJOUTER LES COLONNES MANQUANTES À LA TABLE COURS
ALTER TABLE cours 
ADD COLUMN IF NOT EXISTS formation_id INT NULL AFTER id,
ADD COLUMN IF NOT EXISTS ordre INT DEFAULT 1 AFTER formation_id;

-- Ajouter la clé étrangère vers formations
ALTER TABLE cours 
ADD CONSTRAINT fk_cours_formation 
FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE;

-- Index pour améliorer les performances
CREATE INDEX idx_cours_formation ON cours(formation_id, ordre);

-- 3. AJOUTER LES COLONNES MANQUANTES À LA TABLE LECONS
ALTER TABLE lecons
ADD COLUMN IF NOT EXISTS fichier_devoir VARCHAR(255) NULL 
    COMMENT 'Chemin vers le PDF du devoir' AFTER fichier_joint,
ADD COLUMN IF NOT EXISTS quiz_data JSON NULL 
    COMMENT 'Données JSON du quiz' AFTER fichier_devoir;

-- Index pour améliorer les performances
CREATE INDEX idx_lecons_type ON lecons(type_contenu);

-- 4. AJOUTER LA COLONNE formation_id À LA TABLE INSCRIPTIONS
ALTER TABLE inscriptions
ADD COLUMN IF NOT EXISTS formation_id INT NULL AFTER id;

-- Ajouter la clé étrangère vers formations
ALTER TABLE inscriptions
ADD CONSTRAINT fk_inscriptions_formation 
FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE;

-- Index pour améliorer les performances
CREATE INDEX idx_formation_statut ON formations(statut, date_disponibilite);



-- Table des forums (catégories)
CREATE TABLE IF NOT EXISTS forum_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    ordre INT DEFAULT 0
);

-- Table des sujets de forum
CREATE TABLE IF NOT EXISTS forum_sujets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT,
    utilisateur_id INT,
    titre VARCHAR(200) NOT NULL,
    contenu TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_dernier_message DATETIME,
    statut ENUM('ouvert', 'ferme', 'epingle') DEFAULT 'ouvert',
    FOREIGN KEY (categorie_id) REFERENCES forum_categories(id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

-- Table des messages du forum
CREATE TABLE IF NOT EXISTS forum_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sujet_id INT,
    utilisateur_id INT,
    contenu TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME,
    FOREIGN KEY (sujet_id) REFERENCES forum_sujets(id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

-- Table des projets de coworking
CREATE TABLE IF NOT EXISTS projets_coworking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    createur_id INT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('actif', 'termine', 'suspendu') DEFAULT 'actif',
    FOREIGN KEY (createur_id) REFERENCES utilisateurs(id)
);

-- Table des membres des projets
CREATE TABLE IF NOT EXISTS projet_membres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projet_id INT,
    utilisateur_id INT,
    role ENUM('membre', 'admin') DEFAULT 'membre',
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (projet_id) REFERENCES projets_coworking(id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

-- Table des événements
CREATE TABLE IF NOT EXISTS evenements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME,
    lieu VARCHAR(200),
    type ENUM('conference', 'workshop', 'bootcamp', 'hackathon') DEFAULT 'workshop',
    image VARCHAR(255),
    prix DECIMAL(10,2) DEFAULT 0.00,
    places_max INT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);
