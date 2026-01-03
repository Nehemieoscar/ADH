# ADH - Plateforme de Gestion d'Apprentissage Avancée

## 📚 Description

ADH est une plateforme complète de gestion d'apprentissage (LMS) conçue pour faciliter la gestion des formations, des utilisateurs, et des interactions pédagogiques. Elle offre des fonctionnalités avancées comme la gestion des rôles, le suivi des activités, l'analyse comportementale par IA, et la synchronisation hors-ligne.

## ✨ Fonctionnalités Principales

### 🎓 Gestion des Formations
- Créer et gérer des formations avec plusieurs niveaux (formations → cours → modules)
- Suivi de la progression des utilisateurs
- Gestion des certifications et badges

### 👥 Gestion Avancée des Utilisateurs
- **Fiche profil ultra-détaillée** avec 6 onglets:
  - Aperçu (statistiques)
  - Rôles & Historique
  - Données Académiques
  - Activité Récente
  - Permissions
  - Comportement IA

- **Rôles modulables**: Chaque utilisateur peut avoir plusieurs rôles (étudiant, formateur, superviseur, admin)
- **Filtres intelligents**: Par rôle, statut, formation, participation, date d'inscription, comportement
- **Export de données**: CSV, PDF, Excel

### 📊 Suivi et Analyse
- Historique détaillé d'activités pour chaque utilisateur
- Progression par formation et par cours
- Taux de participation global
- Analyse comportementale par IA
- Statut en temps réel (connecté, inactif, en session)

### 🔔 Système d'Alertes
- Alertes automatiques basées sur les règles
- Notifications en temps réel
- Alertes personnalisables par utilisateur
- Support multi-canal (email, SMS, notification interne)

### 💬 Communication
- Chatbot interne pour support utilisateur
- Messaging ciblé entre utilisateurs
- Notifications avec priorités (basse, normale, haute, urgente)

### 📱 Mode Hors-Ligne
- Synchronisation automatique des données
- Queue d'actions hors-ligne
- Résolution intelligente des conflits

### 🔐 Sécurité
- Authentification session-based
- Contrôle d'accès basé sur les rôles (RBAC)
- Permissions personnalisées
- Audit d'activité complet

## 🏗️ Architecture

### Stack Technologique
- **Backend**: PHP 7.4+
- **Base de données**: MySQL 5.7+
- **Frontend**: Vanilla JavaScript ES6+
- **CSS**: Flexbox/Grid + CSS Variables
- **Storage**: IndexedDB (offline)

### Structure du Projet
```
ADH/
├── dashboard/              # Tableaux de bord admin
│   ├── dashboard.php       # Dashboard principal
│   ├── css/               # Styles du dashboard
│   ├── js/                # Scripts JS
│   ├── api/               # API endpoints
│   └── admin/             # Pages d'administration
├── includes/              # Classes et services
│   ├── ActivityTracker.php
│   ├── NotificationService.php
│   ├── RoleManager.php
│   ├── BehaviorAnalyzer.php
│   └── OfflineSyncService.php
├── css/                   # Styles globaux
├── js/                    # Scripts globaux
├── config.php             # Configuration
└── [autres pages]         # Pages principales
```

## 🚀 Installation

### Prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Apache ou Nginx
- Composer (optionnel)

### Étapes

1. **Cloner le repository**
```bash
git clone https://github.com/ton-username/ADH.git
cd ADH
```

2. **Configurer la base de données**
```bash
# Importer le schéma SQL
mysql -u root -p < users_advanced_schema.sql
```

3. **Configurer les fichiers**
- Copier `config.php` et l'adapter à ton environnement
- Configurer les clés API (Stripe, MonCash, etc.)

4. **Démarrer le serveur**
```bash
# Avec XAMPP
php -S localhost:8000
```

## 📖 Documentation

Voir les fichiers dans `docs/`:
- `QUICK_START.md` - Guide de démarrage rapide
- `README_USERS_SYSTEM.md` - Documentation complète du système utilisateur
- `DEPLOYMENT_CHECKLIST.md` - Checklist de déploiement
- Autres guides spécialisés

## 🤝 Contribution

Les contributions sont bienvenues ! Pour contribuer:

1. Fork le repository
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit tes changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

### Directives de Contribution
- Respecter le code style existant
- Ajouter des tests pour les nouvelles fonctionnalités
- Mettre à jour la documentation
- Utiliser des messages de commit clairs

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 👨‍💻 Auteurs

- **ADH Team** - Développement principal
- Contributeurs bienvenues !

## 📧 Contact

Pour toute question ou suggestion: nehemieoscar306@gmail.com

## 🐛 Signaler un Bug

Si tu trouves un bug, merci de l'ouvrir comme issue GitHub avec:
- Description claire du bug
- Étapes pour reproduire
- Comportement attendu vs. réel
- Screenshots (si applicable)

## 🗺️ Roadmap

- [ ] Intégration avec LTI (Learning Tools Interoperability)
- [ ] Tableau de bord mobile responsive
- [ ] Gamification (points, badges, leaderboards)
- [ ] Intégration Vidéo (streaming, enregistrements)
- [ ] Analytics avancés (prédiction d'abandon, recommandations)
- [ ] API publique complète
- [ ] Support multi-langue

---

**Note**: Ce projet est actuellement en développement actif. Des changements significants peuvent survenir.
