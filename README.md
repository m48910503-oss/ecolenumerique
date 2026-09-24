École numérique
École numérique est une plateforme e-learning conçue pour faciliter la création, la gestion et la diffusion de formations en ligne.

La plateforme met en relation apprenants, enseignants et administrateurs dans un environnement sécurisé permettant de gérer les formations, les contenus pédagogiques, les évaluations et les paiements.

Projet en développement

Fonctionnalités
Apprenants
Création et sécurisation des comptes
Vérification de l'adresse e-mail
Consultation du catalogue de formations
Achat et accès aux formations
Suivi des cours et de la progression
Accès aux ressources pédagogiques
Exercices et évaluations
Enseignants
Création et gestion du profil enseignant
Soumission du profil pour validation
Création et gestion de formations
Organisation des formations en chapitres et leçons
Ajout de vidéos et ressources pédagogiques
Création d'exercices et évaluations
Soumission des formations pour validation
Administration
Gestion des utilisateurs
Gestion des rôles et permissions
Validation des enseignants
Gestion et modération des formations
Gestion des catégories
Supervision des paiements
Gestion des contenus et ressources
Paiements
Intégration de FedaPay
Support prévu pour les paiements en crypto
Gestion des transactions via webhooks
Protection contre le traitement multiple des transactions
Sécurité
La plateforme intègre plusieurs mécanismes de sécurité :

Authentification et contrôle d'accès par rôles
Protection CSRF
Requêtes SQL préparées avec PDO
Validation et échappement des données
Sessions et cookies sécurisés
Protection des fichiers uploadés
Limitation des tentatives sur les fonctionnalités sensibles
Vérification des webhooks de paiement
Gestion de l'idempotence des transactions
Contrôle des téléchargements et ressources privées
SEO et performances
Le projet est conçu avec une attention particulière portée au référencement naturel et aux performances :

URLs et slugs optimisés pour le SEO
Balises meta et Open Graph
URLs canoniques
Données structurées JSON-LD
Sitemap XML
Gestion du robots.txt
Images optimisées et format WebP
Lazy loading
Design responsive
Technologies
Technologie	Utilisation
PHP 8+	Backend
MySQL / MariaDB	Base de données
PDO	Accès aux données
Tailwind CSS	Interface utilisateur
JavaScript	Interactions et logique frontend
Fetch / AJAX	Communications asynchrones
FedaPay	Paiements
Git / GitHub	Gestion du code source
Architecture
Le projet utilise une architecture PHP procédurale, sans framework MVC, avec une séparation claire entre les différents espaces de la plateforme.

École numérique
│
├── Interface publique
├── Espace apprenant
├── Espace enseignant
├── Administration
├── Paiements
├── Authentification et sécurité
└── Gestion des formations

Objectifs du projet
Le projet a été développé autour de plusieurs objectifs :

Proposer une expérience d'apprentissage en ligne moderne
Faciliter la création et la commercialisation de formations
Centraliser la gestion des contenus pédagogiques
Sécuriser les comptes et les transactions
Concevoir une solution adaptée au contexte africain
Construire une architecture évolutive et maintenable
Aperçu
Les captures d'écran de l'application seront ajoutées prochainement.

Confidentialité
Ce dépôt contient un projet en développement. Certaines fonctionnalités, configurations et informations sensibles ne sont pas publiées dans ce dépôt.

Le code source présenté ici est destiné à démontrer les compétences techniques et l'architecture du projet.

