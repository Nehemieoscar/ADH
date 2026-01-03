<?php
/**
 * Service d'analyse du comportement utilisateur avec IA
 * Génère des résumés automatiques du comportement des utilisateurs
 */

class BehaviorAnalyzer {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Analyse le comportement d'un utilisateur et génère un résumé
     */
    public function analyze_user_behavior($utilisateur_id, $days = 30) {
        try {
            $date_debut = date('Y-m-d', strtotime("-$days days"));
            
            // 1. Heures de pic d'activité
            $heures_pic = $this->get_peak_hours($utilisateur_id, $date_debut);
            
            // 2. Jour le plus actif
            $jour_actif = $this->get_most_active_day($utilisateur_id, $date_debut);
            
            // 3. Temps moyen de réponse
            $temps_reponse = $this->get_average_response_time($utilisateur_id, $date_debut);
            
            // 4. Taux de participation au forum
            $taux_forum = $this->get_forum_participation_rate($utilisateur_id, $date_debut);
            
            // 5. Taux de complétion des cours
            $taux_completion = $this->get_course_completion_rate($utilisateur_id);
            
            // 6. Analyse des patterns
            $patterns = $this->analyze_patterns($utilisateur_id, $heures_pic, $jour_actif, $taux_forum, $taux_completion);
            
            // 7. Calcul des scores
            $score_engagement = $this->calculate_engagement_score($utilisateur_id, $date_debut);
            $score_assiduité = $this->calculate_attendance_score($utilisateur_id, $date_debut);
            
            // Générer le résumé textuel
            $resume = $this->generate_ai_summary($heures_pic, $jour_actif, $temps_reponse, $taux_forum, $taux_completion, $patterns, $score_engagement);
            
            // Enregistrer l'analyse
            return $this->save_analysis(
                $utilisateur_id,
                $heures_pic,
                $jour_actif,
                $temps_reponse,
                $taux_forum,
                $taux_completion,
                $patterns,
                $resume,
                $score_engagement,
                $score_assiduité
            );
        } catch (Exception $e) {
            error_log("Erreur analyze_user_behavior: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les heures de pic d'activité
     */
    private function get_peak_hours($utilisateur_id, $date_debut) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT HOUR(date_activite) as heure, COUNT(*) as nombre
                FROM user_activity
                WHERE utilisateur_id = ? AND date_activite >= ?
                GROUP BY HOUR(date_activite)
                ORDER BY nombre DESC
                LIMIT 3
            ");
            $stmt->execute([$utilisateur_id, $date_debut]);
            $heures = $stmt->fetchAll();
            
            $heures_list = [];
            foreach ($heures as $h) {
                $heures_list[] = $h['heure'] . 'h00';
            }
            return implode(', ', $heures_list);
        } catch (Exception $e) {
            return 'Non disponible';
        }
    }
    
    /**
     * Récupère le jour le plus actif
     */
    private function get_most_active_day($utilisateur_id, $date_debut) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT DAYNAME(date_activite) as jour, COUNT(*) as nombre
                FROM user_activity
                WHERE utilisateur_id = ? AND date_activite >= ?
                GROUP BY DAYNAME(date_activite)
                ORDER BY nombre DESC
                LIMIT 1
            ");
            $stmt->execute([$utilisateur_id, $date_debut]);
            $result = $stmt->fetch();
            return $result['jour'] ?? 'Non disponible';
        } catch (Exception $e) {
            return 'Non disponible';
        }
    }
    
    /**
     * Récupère le temps moyen de réponse (pour les messages du forum)
     */
    private function get_average_response_time($utilisateur_id, $date_debut) {
        try {
            // Simulation: retourner une valeur moyenne
            $stmt = $this->pdo->prepare("
                SELECT AVG(HOUR(fm.date_creation) - HOUR(fs.date_creation)) as temps_moyen
                FROM forum_messages fm
                JOIN forum_sujets fs ON fm.sujet_id = fs.id
                WHERE fm.utilisateur_id = ? AND fm.date_creation >= ?
            ");
            $stmt->execute([$utilisateur_id, $date_debut]);
            $result = $stmt->fetch();
            $temps = $result['temps_moyen'] ?? 0;
            return max(0, intval($temps)) . ' heures';
        } catch (Exception $e) {
            return 'Non disponible';
        }
    }
    
    /**
     * Récupère le taux de participation au forum
     */
    private function get_forum_participation_rate($utilisateur_id, $date_debut) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as messages
                FROM forum_messages
                WHERE utilisateur_id = ? AND date_creation >= ?
            ");
            $stmt->execute([$utilisateur_id, $date_debut]);
            $result = $stmt->fetch();
            
            // Nombre total de messages sur la période
            $total_messages = $result['messages'] ?? 0;
            
            // Calculer le taux (par rapport au nombre de jours)
            $jours = 30;
            $taux = round(($total_messages / $jours) * 100, 2);
            return $taux;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Récupère le taux de complétion des cours
     */
    private function get_course_completion_rate($utilisateur_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statut_inscription = 'termine' THEN 1 ELSE 0 END) as termines
                FROM cours_progression
                WHERE utilisateur_id = ?
            ");
            $stmt->execute([$utilisateur_id]);
            $result = $stmt->fetch();
            
            $total = $result['total'] ?? 0;
            $termines = $result['termines'] ?? 0;
            
            if ($total == 0) return 0;
            return round(($termines / $total) * 100, 2);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Analyse les patterns de comportement
     */
    private function analyze_patterns($utilisateur_id, $heures_pic, $jour_actif, $taux_forum, $taux_completion) {
        $patterns = [];
        
        if (strpos($heures_pic, '20h') !== false || strpos($heures_pic, '21h') !== false) {
            $patterns[] = "Préfère travailler en soirée";
        }
        
        if (strpos($jour_actif, 'Monday') !== false || strpos($jour_actif, 'Tuesday') !== false) {
            $patterns[] = "Plus actif en début de semaine";
        }
        
        if ($taux_forum > 2) {
            $patterns[] = "Participant très actif au forum";
        } else if ($taux_forum > 0.5) {
            $patterns[] = "Participe régulièrement au forum";
        }
        
        if ($taux_completion > 70) {
            $patterns[] = "Taux de complétion excellent";
        } else if ($taux_completion > 50) {
            $patterns[] = "Bonne persévérance dans les cours";
        }
        
        return $patterns;
    }
    
    /**
     * Calcule le score d'engagement (0-100)
     */
    private function calculate_engagement_score($utilisateur_id, $date_debut) {
        try {
            $score = 0;
            
            // Activités récentes (30 points)
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM user_activity
                WHERE utilisateur_id = ? AND date_activite >= ?
            ");
            $stmt->execute([$utilisateur_id, $date_debut]);
            $count = $stmt->fetch()['count'];
            $score += min(30, $count);
            
            // Messages au forum (20 points)
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM forum_messages
                WHERE utilisateur_id = ? AND date_creation >= ?
            ");
            $stmt->execute([$utilisateur_id, $date_debut]);
            $count = $stmt->fetch()['count'];
            $score += min(20, $count * 2);
            
            // Quiz soumis (25 points)
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM user_activity
                WHERE utilisateur_id = ? AND type_activite = 'quiz_soumis' AND date_activite >= ?
            ");
            $stmt->execute([$utilisateur_id, $date_debut]);
            $count = $stmt->fetch()['count'];
            $score += min(25, $count * 5);
            
            // Devoirs soumis (25 points)
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM user_activity
                WHERE utilisateur_id = ? AND type_activite = 'fichier_upload' AND date_activite >= ?
            ");
            $stmt->execute([$utilisateur_id, $date_debut]);
            $count = $stmt->fetch()['count'];
            $score += min(25, $count * 5);
            
            return min(100, $score);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Calcule le score d'assiduité (0-100)
     */
    private function calculate_attendance_score($utilisateur_id, $date_debut) {
        try {
            $score = 0;
            
            // Connexions régulières
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT DATE(date_activite)) as jours_actifs
                FROM user_activity
                WHERE utilisateur_id = ? AND type_activite = 'connexion' AND date_activite >= ?
            ");
            $stmt->execute([$utilisateur_id, $date_debut]);
            $jours_actifs = $stmt->fetch()['jours_actifs'];
            $score = round(($jours_actifs / 30) * 100, 2);
            
            return min(100, $score);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Génère un résumé textuel du comportement
     */
    private function generate_ai_summary($heures_pic, $jour_actif, $temps_reponse, $taux_forum, $taux_completion, $patterns, $engagement) {
        $resume = "Comportement utilisateur : ";
        
        if ($engagement > 80) {
            $resume .= "Très engagé et actif. ";
        } else if ($engagement > 50) {
            $resume .= "Modérément engagé. ";
        } else {
            $resume .= "Engagement faible. ";
        }
        
        if ($heures_pic) {
            $resume .= "Actif principalement à $heures_pic. ";
        }
        
        if ($jour_actif) {
            $resume .= "Plus actif les $jour_actif. ";
        }
        
        if (!empty($patterns)) {
            $resume .= implode(", ", $patterns) . ". ";
        }
        
        $resume .= "Taux de complétion : $taux_completion%. ";
        $resume .= "Participation forum : " . number_format($taux_forum, 2) . "%";
        
        return $resume;
    }
    
    /**
     * Enregistre l'analyse dans la base de données
     */
    private function save_analysis($utilisateur_id, $heures_pic, $jour_actif, $temps_reponse, $taux_forum, $taux_completion, $patterns, $resume, $score_engagement, $score_assiduité) {
        try {
            // Vérifier si l'enregistrement existe
            $stmt_check = $this->pdo->prepare("SELECT id FROM user_behavior_analysis WHERE utilisateur_id = ?");
            $stmt_check->execute([$utilisateur_id]);
            
            $patterns_json = json_encode($patterns);
            
            if ($stmt_check->fetch()) {
                // Mise à jour
                $stmt = $this->pdo->prepare("
                    UPDATE user_behavior_analysis 
                    SET heures_pic_activite = ?,
                        jour_plus_actif = ?,
                        temps_moyen_reponse = ?,
                        taux_participation_forum = ?,
                        taux_completion_cours = ?,
                        pattern_comportement = ?,
                        score_engagement = ?,
                        score_assiduité = ?,
                        derniere_analyse = NOW()
                    WHERE utilisateur_id = ?
                ");
                return $stmt->execute([
                    $heures_pic,
                    $jour_actif,
                    intval(str_replace(' heures', '', $temps_reponse)),
                    $taux_forum,
                    $taux_completion,
                    $patterns_json,
                    $score_engagement,
                    $score_assiduité,
                    $utilisateur_id
                ]);
            } else {
                // Insertion
                $stmt = $this->pdo->prepare("
                    INSERT INTO user_behavior_analysis 
                    (utilisateur_id, heures_pic_activite, jour_plus_actif, temps_moyen_reponse, 
                     taux_participation_forum, taux_completion_cours, pattern_comportement, 
                     score_engagement, score_assiduité)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                return $stmt->execute([
                    $utilisateur_id,
                    $heures_pic,
                    $jour_actif,
                    intval(str_replace(' heures', '', $temps_reponse)),
                    $taux_forum,
                    $taux_completion,
                    $patterns_json,
                    $score_engagement,
                    $score_assiduité
                ]);
            }
        } catch (Exception $e) {
            error_log("Erreur save_analysis: " . $e->getMessage());
            return false;
        }
    }
}

function get_behavior_analyzer() {
    global $pdo;
    if (!isset($GLOBALS['behavior_analyzer'])) {
        $GLOBALS['behavior_analyzer'] = new BehaviorAnalyzer($pdo);
    }
    return $GLOBALS['behavior_analyzer'];
}
?>
