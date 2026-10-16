# ClubConnect

Application PHP/MySQL pour la gestion des clubs universitaires.

<<<<<<< HEAD
## Dépendances
- PHP avec MySQLi
- MySQL/MariaDB
- Composer (PHPMailer)
- Node.js/npm (compilation Tailwind CSS)

## Installation locale
1. Crée une base de données MySQL.
2. Configure les variables de connexion dans `config/database.php` ou via les variables d'environnement `DB_HOST`, `DB_USER`, `DB_PASSWORD` et `DB_NAME`.
3. Installe les dépendances PHP avec `composer install` (le dossier `vendor/` n'est pas inclus).
4. Installe les dépendances CSS avec `npm install`, puis lance `npm run build`.
5. Configure les variables SMTP dans `traitement.php` avec tes propres identifiants avant d'activer l'envoi d'e-mails. N'utilise jamais ton vrai mot de passe dans un dépôt public.
6. Configure le serveur web pour pointer vers ce dossier.

Les fichiers de configuration ont été nettoyés avant publication. Les uploads d'utilisateurs ne sont pas inclus.
=======
## Technologies
- PHP et MySQLi
- MySQL/MariaDB
- Composer et PHPMailer
- Node.js/npm pour compiler Tailwind CSS

## Installation locale
1. Crée une base de données MySQL.
2. Configure les variables de connexion dans `config/database.php` ou avec `DB_HOST`, `DB_USER`, `DB_PASSWORD` et `DB_NAME`.
3. Installe les dépendances PHP avec `composer install` (le dossier `vendor/` n'est pas inclus).
4. Installe les dépendances CSS avec `npm install`, puis lance `npm run build`.
5. Configure tes propres identifiants SMTP avant d'activer l'envoi d'e-mails. Ne publie jamais de mots de passe ou de clés dans un dépôt public.
6. Configure ton serveur web pour pointer vers le dossier du projet.

Les identifiants réels doivent rester privés. Les fichiers d'uploads et les dépendances installées ne sont pas inclus dans l'archive publique préparée.
>>>>>>> d3f37118d8b59a010dcf86e9ef8884b78b095922
