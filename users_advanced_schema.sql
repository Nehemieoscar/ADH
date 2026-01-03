-- ============================================================================
-- TABLES POUR LA GESTION AVANCÉE DES UTILISATEURS ET ACCÈS
-- ============================================================================

-- 1. TABLE DES PROFILS UTILISATEURS (Détails avancés)
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNIQUE NOT NULL,
    bio TEXT,
    phone VARCHAR(20),
    adresse VARCHAR(255),
    ville VARCHAR(100),
    code_postal VARCHAR(10),
    pays VARCHAR(100),
    date_naissance DATE,
    genre ENUM('M', 'F', 'Autre') DEFAULT NULL,
    competences JSON,
    secteur_activite VARCHAR(100),
    entreprise VARCHAR(100),
    niveau_etudes ENUM('Primaire', 'Secondaire', 'Bac', 'Licence', 'Master', 'Doctorat', 'Autres') DEFAULT NULL,
    photo_profil VARCHAR(255),
    couverture_profil VARCHAR(255),
    resume_comportement_ia TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_utilisateur_id (utilisateur_id)
);

-- 2. TABLE DES RÔLES MODULABLES
CREATE TABLE IF NOT EXISTS user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    role ENUM('etudiant', 'professeur', 'formateur', 'superviseur', 'admin', 'moderateur') NOT NULL,
    statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
    date_debut DATE NOT NULL,
    date_fin DATE DEFAULT NULL,
    permission_level INT DEFAULT 1,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_period (utilisateur_id, role, date_debut),
    INDEX idx_utilisateur_role (utilisateur_id, role),
    INDEX idx_statut (statut)
);

-- 3. TABLE DE L'HISTORIQUE DES RÔLES
CREATE TABLE IF NOT EXISTS role_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    ancien_role VARCHAR(50),
    nouveau_role VARCHAR(50),
    date_changement DATETIME DEFAULT CURRENT_TIMESTAMP,
    raison VARCHAR(255),
    modifie_par INT,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (modifie_par) REFERENCES utilisateurs(id),
    INDEX idx_utilisateur_id (utilisateur_id),
    INDEX idx_date_changement (date_changement)
);

-- 4. TABLE DES CERTIFICATIONS ET BADGES
CREATE TABLE IF NOT EXISTS user_certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    date_obtention DATE NOT NULL,
    date_expiration DATE,
    emis_par VARCHAR(100),
    numero_certification VARCHAR(100) UNIQUE,
    lien_verification VARCHAR(500),
    image_badge VARCHAR(255),
    statut ENUM('actif', 'expire', 'revoque') DEFAULT 'actif',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_utilisateur_id (utilisateur_id),
    INDEX idx_statut (statut)
);

-- 5. TABLE DES ACTIVITÉS UTILISATEUR
CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    type_activite ENUM('connexion', 'deconnexion', 'message', 'fichier_upload', 'quiz_soumis', 'cours_commence', 'cours_termine', 'participation_forum', 'commentaire', 'module_complete', 'formation_inscrite', 'formation_abandonnee', 'formation_terminee', 'autre') NOT NULL,
    description TEXT,
    entite_type VARCHAR(50),
    entite_id INT,
    durée_session INT COMMENT 'en secondes pour les connexions',
    date_activite DATETIME DEFAULT CURRENT_TIMESTAMP,
    adresse_ip VARCHAR(45),
    user_agent TEXT,
    localisation VARCHAR(255),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_utilisateur_id (utilisateur_id),
    INDEX idx_type_activite (type_activite),
    INDEX idx_date_activite (date_activite),
    INDEX idx_utilisateur_date (utilisateur_id, date_activite)
);

-- 6. TABLE DES ALERTES ET NOTIFICATIONS
CREATE TABLE IF NOT EXISTS user_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    admin_id INT,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'error', 'success', 'alert', 'rappel') DEFAULT 'info',
    priorite ENUM('basse', 'normale', 'haute', 'urgente') DEFAULT 'normale',
    lu BOOLEAN DEFAULT FALSE,
    date_lecture DATETIME,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_expiration DATETIME,
    lien_action VARCHAR(255),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES utilisateurs(id),
    INDEX idx_utilisateur_id (utilisateur_id),
    INDEX idx_lu (lu),
    INDEX idx_date_creation (date_creation)
);

-- 7. TABLE DES ALERTES AUTOMATIQUES
CREATE TABLE IF NOT EXISTS alert_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    type_condition ENUM('inactivite', 'retard', 'progression_faible', 'absences', 'sans_message', 'non_conformite', 'quota_depot', 'autre') NOT NULL,
    condition_detail JSON,
    action_type ENUM('email', 'notification', 'sms', 'multi') DEFAULT 'notification',
    destinataires JSON,
    message_template TEXT,
    statut ENUM('active', 'inactive') DEFAULT 'active',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creee_par INT,
    FOREIGN KEY (creee_par) REFERENCES utilisateurs(id),
    INDEX idx_statut (statut)
);

-- 8. TABLE DE SYNCHRONISATION HORS-LIGNE
CREATE TABLE IF NOT EXISTS offline_sync_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    entite_type VARCHAR(50),
    entite_id INT,
    donnees_json JSON,
    statut ENUM('en_attente', 'en_cours', 'synchronise', 'erreur') DEFAULT 'en_attente',
    tentatives INT DEFAULT 0,
    message_erreur TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_synchronisation DATETIME,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_utilisateur_id (utilisateur_id),
    INDEX idx_statut (statut)
);

-- 9. TABLE DE PROGRESSION PAR FORMATION
CREATE TABLE IF NOT EXISTS formation_progression (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    formation_id INT NOT NULL,
    progression_global INT DEFAULT 0,
    modules_completes INT DEFAULT 0,
    total_modules INT DEFAULT 0,
    quiz_moyenne_score DECIMAL(5,2),
    devoirs_remis INT DEFAULT 0,
    devoirs_evalues INT DEFAULT 0,
    derniere_activite DATETIME,
    statut_inscription ENUM('en_cours', 'termine', 'abandonne', 'suspendu') DEFAULT 'en_cours',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_completion DATETIME,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_formation (utilisateur_id, formation_id),
    INDEX idx_utilisateur_id (utilisateur_id),
    INDEX idx_formation_id (formation_id),
    INDEX idx_statut (statut_inscription)
);

-- 10. TABLE DE PROGRESSION PAR COURS
CREATE TABLE IF NOT EXISTS cours_progression (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    cours_id INT NOT NULL,
    progression INT DEFAULT 0,
    modules_completes INT DEFAULT 0,
    lecons_vues INT DEFAULT 0,
    quiz_score INT,
    temps_suivi INT DEFAULT 0 COMMENT 'en minutes',
    derniere_activite DATETIME,
    statut_inscription ENUM('en_cours', 'termine', 'abandonne') DEFAULT 'en_cours',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_completion DATETIME,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (cours_id) REFERENCES cours(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_cours (utilisateur_id, cours_id),
    INDEX idx_utilisateur_id (utilisateur_id),
    INDEX idx_cours_id (cours_id),
    INDEX idx_statut (statut_inscription)
);

-- 11. TABLE DES PERMISSIONS PERSONNALISÉES
CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    permission VARCHAR(100) NOT NULL,
    statut ENUM('accordee', 'refusee') DEFAULT 'accordee',
    date_debut DATE,
    date_fin DATE,
    raison_refus TEXT,
    accordee_par INT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (accordee_par) REFERENCES utilisateurs(id),
    UNIQUE KEY unique_user_permission (utilisateur_id, permission),
    INDEX idx_utilisateur_id (utilisateur_id)
);

-- 12. TABLE D'ANALYSE DU COMPORTEMENT
CREATE TABLE IF NOT EXISTS user_behavior_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNIQUE NOT NULL,
    heures_pic_activite VARCHAR(255),
    jour_plus_actif VARCHAR(20),
    jour_moins_actif VARCHAR(20),
    temps_moyen_reponse INT COMMENT 'en minutes',
    taux_participation_forum DECIMAL(5,2),
    taux_completion_cours DECIMAL(5,2),
    pattern_comportement TEXT,
    score_engagement INT DEFAULT 0,
    score_assiduité INT DEFAULT 0,
    derniere_analyse DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_utilisateur_id (utilisateur_id)
);

-- 13. TABLE DE STATUT EN TEMPS RÉEL
CREATE TABLE IF NOT EXISTS user_online_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNIQUE NOT NULL,
    est_connecte BOOLEAN DEFAULT FALSE,
    derniere_activite DATETIME,
    duree_inactivite INT COMMENT 'en secondes',
    statut_personnel ENUM('en ligne', 'inactif', 'en session', 'indisponible', 'hors ligne') DEFAULT 'hors ligne',
    message_statut VARCHAR(255),
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_utilisateur_id (utilisateur_id)
);

-- 14. TABLE DE TAUX DE PARTICIPATION GLOBAL
CREATE TABLE IF NOT EXISTS participation_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    periode VARCHAR(20),
    annee INT,
    mois INT,
    taux_presence DECIMAL(5,2),
    nombre_sessions INT DEFAULT 0,
    nombre_messages INT DEFAULT 0,
    nombre_devoirs_remis INT DEFAULT 0,
    nombre_quiz_participations INT DEFAULT 0,
    nombre_absences INT DEFAULT 0,
    retards INT DEFAULT 0,
    demandes_en_attente INT DEFAULT 0,
    date_calcul DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_utilisateur_id (utilisateur_id),
    INDEX idx_periode (periode, utilisateur_id)
);

-- 15. TABLE DES GROUPES (à créer AVANT les modifications de utilisateurs)
CREATE TABLE IF NOT EXISTS groupes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('classe', 'promotion', 'projet', 'formation', 'autre') DEFAULT 'classe',
    responsable_id INT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (responsable_id) REFERENCES utilisateurs(id),
    INDEX idx_responsable_id (responsable_id)
);

-- ============================================================================
-- MODIFICATIONS DE LA TABLE UTILISATEURS
-- ============================================================================

-- Ajouter les colonnes (sans FOREIGN KEY d'abord)
ALTER TABLE utilisateurs 
ADD COLUMN IF NOT EXISTS statut_temps_reel ENUM('connecte', 'inactif', 'en_session', 'indisponible', 'hors_ligne') DEFAULT 'hors_ligne' AFTER statut,
ADD COLUMN IF NOT EXISTS derniere_connexion DATETIME DEFAULT NULL AFTER last_login,
ADD COLUMN IF NOT EXISTS groupe_id INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS superviseur_id INT DEFAULT NULL;

-- Ajouter les FOREIGN KEY séparément
ALTER TABLE utilisateurs 
ADD CONSTRAINT fk_utilisateurs_groupe FOREIGN KEY (groupe_id) REFERENCES groupes(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_utilisateurs_superviseur FOREIGN KEY (superviseur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL;

-- ============================================================================
-- INDEX POUR OPTIMISER LES REQUÊTES
-- ============================================================================
CREATE INDEX IF NOT EXISTS idx_user_created_at ON utilisateurs(created_at);
CREATE INDEX IF NOT EXISTS idx_user_role ON utilisateurs(role);
CREATE INDEX IF NOT EXISTS idx_user_statut ON utilisateurs(statut);
CREATE INDEX IF NOT EXISTS idx_user_statut_temps_reel ON utilisateurs(statut_temps_reel);
CREATE INDEX IF NOT EXISTS idx_user_email ON utilisateurs(email);
