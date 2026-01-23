# WR506D - Movie API

lien back-end : [https://wr506.theo-birost.fr/]

## Description
API REST et GraphQL pour la gestion de films, acteurs, catégories et utilisateurs.
Projet réalisé dans le cadre du module WR506D.

## Fonctionnalités
- **API REST & GraphQL** : Gestion complète des ressources (CRUD)
- **Authentification** : JWT (JSON Web Token)
- **Sécurité** : Gestion des rôles (ROLE_USER, ROLE_ADMIN)
- **Upload de fichiers** : Gestion des médias (images)
- **Rate Limiting** : Protection contre les abus
- **API Key** : Authentification par clé API
- **Qualité de code** : PHPCS, PHPStan, PHPMD, PHPUnit

## Installation

### Prérequis
- PHP 8.2+
- Composer
- Symfony CLI
- Base de données (SQLite par défaut, configurable)

### Étapes
1. Cloner le dépôt :
   ```bash
   git clone https://github.com/votre-username/wr506d.git
   cd wr506d
   ```

2. Installer les dépendances :
   ```bash
   composer install
   ```

3. Configurer l'environnement :
   Copier le fichier `.env` en `.env.local` et adapter si nécessaire.

4. Générer les clés JWT :
   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```

5. Créer la base de données et les tables :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

6. (Optionnel) Charger les fixtures :
   ```bash
   php bin/console doctrine:fixtures:load
   ```

7. Lancer le serveur :
   ```bash
   symfony server:start
   ```

## Endpoints Principaux

### Authentification
- `POST /auth` : Obtenir un token JWT
- `POST /api/users` : Créer un compte utilisateur

### Ressources (REST)
- `GET /api/movies` : Liste des films
- `GET /api/movies/{id}` : Détails d'un film
- `GET /api/actors` : Liste des acteurs
- `GET /api/categories` : Liste des catégories

### GraphQL
- Endpoint : `/api/graphql`
- Interface GraphiQL disponible en mode dev

## Tests et Qualité
Lancer les tests et analyses :
```bash
# PHPUnit
php bin/phpunit

# PHP CodeSniffer
vendor/bin/phpcs --standard=PSR2 src/

# PHPStan
vendor/bin/phpstan analyze src/ --level=2

# PHPMD
vendor/bin/phpmd src/ text cleancode,codesize,controversial,design
```

## Déploiement
L'application est déployée et accessible à l'adresse : [https://cineaste.theo-birost.fr/profile]

## Auteur
Théo
