# Infos Progressives Mise En Production

Ce fichier sert de reference de travail pendant la preparation de la vraie production.
Il sera complete au fur et a mesure.

## 1. Domaine

- Domaine principal: `https://www.slidecenter.bj`
- Exposition du site: `racine du domaine`
- Dossier web chez l'hebergeur: `httpdocs`

Implications:

- `base_url` cible: `https://www.slidecenter.bj`
- `RewriteBase` cible: `/`

## 2. Base de donnees MySQL

- Hote: `localhost`
- Port: `3306`
- Nom de la base: `slidece1_ecolenumerique`
- Utilisateur: `slidece1_ecolenumerique`
- Mot de passe: `A_REMPLIR_MANUELLEMENT`

Config cible:

```php
'db' => [
    'dsn' => 'mysql:host=localhost;port=3306;dbname=slidece1_ecolenumerique;charset=utf8mb4',
    'user' => 'slidece1_ecolenumerique',
    'pass' => 'A_REMPLIR_MANUELLEMENT',
],
```

Statut:

- Base creee: `oui`

## 3. Email / SMTP

- Boite retenue pour le site: `support@slidecenter.bj`
- Email externe de recuperation: `configure dans Plesk`

Parametres SMTP identifies:

- Serveur SMTP: `mail.slidecenter.bj`
- Port SMTP: `465`
- Securite SMTP: `SSL/TLS`
- Utilisateur SMTP: `support@slidecenter.bj`
- Mot de passe SMTP: `A_REMPLIR_MANUELLEMENT`

Parametres de lecture mail identifies:

- Serveur entrant: `mail.slidecenter.bj`
- IMAP: `993`
- POP3: `995`

Config cible:

```php
'app' => [
    'base_url' => 'https://www.slidecenter.bj',
    'from_email' => 'support@slidecenter.bj',
    'from_name' => 'SlideCenter',
],
'mail' => [
    'driver' => 'smtp',
    'log_path' => __DIR__ . '/../storage/mail.log',
    'smtp' => [
        'host' => 'mail.slidecenter.bj',
        'port' => 465,
        'encryption' => 'ssl',
        'username' => 'support@slidecenter.bj',
        'password' => 'A_REMPLIR_MANUELLEMENT',
        'timeout' => 15,
    ],
],
```

Recommandations retenues:

- Garder `Boite mail` active dans Plesk
- Laisser la reponse automatique desactivee pour l'instant
- Possibilite d'ajouter plus tard `contact@slidecenter.bj` comme alias
- Idealement decocher `Utilisable pour la connexion a Plesk` si non necessaire

## 4. Fichiers a modifier en production

Fichiers identifies:

- `includes/config.php`
- `includes/config.production.php`
- `.htaccess`
- `.htaccess.production`

Valeurs a poser:

- `app.base_url = https://www.slidecenter.bj`
- `app.from_email = support@slidecenter.bj`
- `app.from_name = SlideCenter`
- `mail.driver = smtp`
- `mail.smtp.host = mail.slidecenter.bj`
- `mail.smtp.port = 465`
- `mail.smtp.encryption = ssl`
- `mail.smtp.username = support@slidecenter.bj`
- `mail.smtp.password = A_REMPLIR_MANUELLEMENT`
- `db.dsn = mysql:host=localhost;port=3306;dbname=slidece1_ecolenumerique;charset=utf8mb4`
- `db.user = slidece1_ecolenumerique`
- `db.pass = A_REMPLIR_MANUELLEMENT`
- `.htaccess -> RewriteBase /`

Copie de travail creee:

- `includes/config.production.php` preparee pour la prod avec placeholders sur les secrets
- `.htaccess.production` prepare pour une exposition a la racine du domaine

## 5. Variables / secrets encore a definir

- `APP_KEY`
- Mot de passe MySQL
- Mot de passe SMTP
- `APP_ENV=production`
- `ADMIN_BOOTSTRAP_REGISTER=0`
- `APP_SCHEMA_ENFORCE=1` a confirmer
- `APP_SUPPORT_STORAGE_DIR`

### Detail APP_KEY

Statut:

- Definie: `non`
- Valeur: `A_GENERER_HORS_REPO`
- Traitement: `reporte a plus tard`

Role technique dans ce projet:

- Signe certains tokens internes de securite
- Verifie les signatures de lecture video securisee
- Sert de secret applicatif dans les apercus/token HMAC
- Sert a chiffrer/dechiffrer certains secrets SSO stockes en base

Fichiers ou le projet s'appuie dessus:

- `includes/bootstrap.php`
- `app/Services/Web/PreviewTokenService.php`
- `app/Services/Web/VideoStreamService.php`
- `app/Services/Company/Auth/SsoService.php`
- `app/Services/Company/DashboardService.php`

Point important:

- En requete web, si `APP_KEY` n'est pas definie, l'application peut se retrouver avec une cle vide.
- Dans ce cas, certaines fonctions securisees peuvent casser ou renvoyer des erreurs de configuration.

Format recommande:

- 64 caracteres hexadecimaux minimum
- exemple de longueur cible: `32 octets` generes aleatoirement puis convertis en hex

Commande locale possible pour la generer:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Regles de gestion:

- Ne pas stocker la vraie valeur dans le depot Git
- La definir comme variable d'environnement serveur
- Eviter de la changer apres mise en service sans plan de migration

Impact si changement apres production:

- Invalidation possible de tokens signes existants
- Secrets SSO deja stockes pouvant devenir illisibles tant qu'ils ne sont pas reenregistres

## 6. Integrations encore a traiter

- Google Login: `a configurer`
- Google Drive: `a configurer`
- FedaPay: `compte ok / cles ok / mode live`
- Web Push: `en attente`

### Detail Google Login

Etat:

- Google Login souhaite: `oui`
- Client OAuth Google: `a creer ou confirmer`
- Client OAuth Google confirme: `oui`

Callback attendu par le code:

- `https://www.slidecenter.bj/oauth/google`

Origine web a autoriser:

- `https://www.slidecenter.bj`

Scopes utilises par le code:

- `openid`
- `email`
- `profile`

Variables attendues:

- `APP_GOOGLE_OAUTH_CLIENT_ID`
- `APP_GOOGLE_OAUTH_CLIENT_SECRET`

Valeurs recues:

- `APP_GOOGLE_OAUTH_CLIENT_ID = 823877393962-kn8o39p4rb28305o13pm5l3q28tmsurm.apps.googleusercontent.com`
- `APP_GOOGLE_OAUTH_CLIENT_SECRET = secret recu separement, non stocke ici en clair`

### Detail Google Drive

Etat:

- Depot Drive souhaite: `oui`
- Mode possible 1: `OAuth utilisateur`
- Mode possible 2: `Service account`

Callback OAuth Drive attendu par le code:

- `https://www.slidecenter.bj/drive/oauth_callback`

Scope Drive utilise par le code:

- `https://www.googleapis.com/auth/drive.file`

Variables attendues si mode OAuth:

- `APP_GOOGLE_OAUTH_CLIENT_ID`
- `APP_GOOGLE_OAUTH_CLIENT_SECRET`
- `APP_DRIVE_OAUTH_TOKEN_PATH`

Variables attendues si mode service account:

- `APP_DRIVE_SERVICE_ACCOUNT_JSON`
- `APP_DRIVE_ROOT_FOLDER_ID`

Note:

- Le projet peut reutiliser le meme client OAuth Google pour le login et pour Drive.
- Le client OAuth confirme est reutilise pour Google Login + Drive OAuth.

### Detail FedaPay

Statut actuel:

- Compte FedaPay: `oui`
- Cles API disponibles: `oui`
- Mode retenu: `live`
- Webhook cree: `oui`

Variables attendues:

- `PAY_FEDAPAY_PUBLIC_KEY`
- `PAY_FEDAPAY_SECRET_KEY`
- `PAY_FEDAPAY_ENV=live`
- `PAY_FEDAPAY_WEBHOOK_SECRET`

URLs applicatives prevues:

- Retour paiement: `https://www.slidecenter.bj/payment_return`
- Webhook: `https://www.slidecenter.bj/api/fedapay_webhook`

Secrets encore non enregistres ici:

- Cle publique: `A_CONFIRMER`
- Cle secrete: `A_GARDER_PRIVEE`
- Secret webhook: `A_GARDER_PRIVE`

## 7. Notes de travail

- Les mots de passe et secrets restent hors de ce fichier si tu preferes les garder prives.
- On peut continuer a completer ce document a chaque etape.
