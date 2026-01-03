-- ============================================================
-- SCHÉMA DE BASE DE DONNÉES POUR LE SYSTÈME DE GESTION 
-- DE CONTENU (Leçons, Devoirs, Quiz)
-- ============================================================

-- Table: leçons (lessons)
CREATE TABLE IF NOT EXISTS lecons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  module_id INT NOT NULL,
  titre VARCHAR(255) NOT NULL,
  contenu LONGTEXT,
  ordre INT DEFAULT 1,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
  INDEX idx_module (module_id)
);

-- Table: devoirs (assignments)
CREATE TABLE IF NOT EXISTS devoirs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  module_id INT NOT NULL,
  titre VARCHAR(255) NOT NULL,
  description TEXT,
  fichier_pdf VARCHAR(255),
  type_remise ENUM('individuel','groupe') DEFAULT 'individuel',
  date_limite DATETIME,
  points_max INT DEFAULT 100,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
  INDEX idx_module (module_id)
);

-- Table: soumissions de devoirs (assignment submissions)
CREATE TABLE IF NOT EXISTS devoirs_soumissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  devoir_id INT NOT NULL,
  utilisateur_id INT NOT NULL,
  fichier_soumis VARCHAR(255),
  notes_etudiant TEXT,
  date_soumission DATETIME DEFAULT CURRENT_TIMESTAMP,
  statut ENUM('en attente','evaluee') DEFAULT 'en attente',
  note_obtenue INT,
  commentaire_professeur TEXT,
  date_evaluation DATETIME,
  FOREIGN KEY (devoir_id) REFERENCES devoirs(id) ON DELETE CASCADE,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  INDEX idx_devoir (devoir_id),
  INDEX idx_utilisateur (utilisateur_id)
);

-- Table: quiz
CREATE TABLE IF NOT EXISTS quiz (
  id INT AUTO_INCREMENT PRIMARY KEY,
  module_id INT NOT NULL,
  titre VARCHAR(255) NOT NULL,
  description TEXT,
  points_max INT DEFAULT 100,
  date_limite DATETIME,
  tentatives_permises INT DEFAULT 3,
  temps_limite_minutes INT,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
  INDEX idx_module (module_id)
);

-- Table: questions de quiz
CREATE TABLE IF NOT EXISTS quiz_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT NOT NULL,
  enonce TEXT NOT NULL,
  type ENUM('multiple_choice','true_false','short_answer') DEFAULT 'multiple_choice',
  points INT DEFAULT 10,
  ordre INT DEFAULT 1,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (quiz_id) REFERENCES quiz(id) ON DELETE CASCADE,
  INDEX idx_quiz (quiz_id)
);

-- Table: options de réponses (pour questions multiple choice)
CREATE TABLE IF NOT EXISTS quiz_reponses_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question_id INT NOT NULL,
  texte_option TEXT NOT NULL,
  est_correcte BOOLEAN DEFAULT FALSE,
  ordre INT DEFAULT 1,
  FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
  INDEX idx_question (question_id)
);

-- Table: soumissions de quiz par étudiants
CREATE TABLE IF NOT EXISTS quiz_soumissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT NOT NULL,
  utilisateur_id INT NOT NULL,
  date_soumission DATETIME DEFAULT CURRENT_TIMESTAMP,
  score_final INT,
  statut ENUM('en cours','completee') DEFAULT 'en cours',
  INDEX idx_quiz (quiz_id),
  INDEX idx_utilisateur (utilisateur_id),
  FOREIGN KEY (quiz_id) REFERENCES quiz(id) ON DELETE CASCADE,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- Table: réponses aux questions de quiz
CREATE TABLE IF NOT EXISTS quiz_reponses_etudiant (
  id INT AUTO_INCREMENT PRIMARY KEY,
  soumission_id INT NOT NULL,
  question_id INT NOT NULL,
  reponse_texte TEXT,
  reponse_option_id INT,
  points_obtenus INT DEFAULT 0,
  FOREIGN KEY (soumission_id) REFERENCES quiz_soumissions(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
  FOREIGN KEY (reponse_option_id) REFERENCES quiz_reponses_options(id) ON DELETE SET NULL,
  INDEX idx_soumission (soumission_id),
  INDEX idx_question (question_id)
);

-- Ajouter colonne 'ordre' à la table modules si elle n'existe pas
-- ALTER TABLE modules ADD COLUMN ordre INT DEFAULT 1;
