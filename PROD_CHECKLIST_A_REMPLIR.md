# Checklist Mise En Production

Date:
Projet: `ecolenumerique`
Environnement cible:
Responsable:

## 1. Domaine et HTTPS

- Domaine principal: `https://________________________`
- Hébergement en sous-dossier ?
  - `non` si le site est à la racine
  - `oui` si le site est exposé sous `https://domaine.tld/ecolenumerique`
- Certificat SSL actif: `oui / non`
- Redirection HTTP vers HTTPS active: `oui / non`

À vérifier:

- `includes/config.php`
  - `app.base_url = https://________________________`
- `.htaccess`
  - `RewriteBase /` si racine du domaine
  - `RewriteBase /ecolenumerique/` si sous-dossier

## 2. Email applicatif

Fichier: `includes/config.php`

- `app.from_email = ________________________`
- `app.from_name = ________________________`

SMTP:

- `mail.driver = smtp`
- `mail.smtp.host = ________________________`
- `mail.smtp.port = ________________________`
- `mail.smtp.encryption = tls / ssl / none`
- `mail.smtp.username = ________________________`
- `mail.smtp.password = ________________________`
- `mail.smtp.timeout = 15`

## 3. Base de données

Fichier: `includes/config.php`

- `db.dsn = mysql:host=________________;dbname=________________;charset=utf8mb4`
- `db.user = ________________________`
- `db.pass = ________________________`

Initialisation:

- Import `database/schema.sql`
- Import tous les `database/schema_v*.sql`
- Vérification: `php app/tests/schema_coverage.php`

Premier admin:

- Fichier: `database/admin_seed.sql`
- Remplacer:
  - email admin: `________________________`
  - hash mot de passe: `________________________`

Commande utile:

```powershell
php -r "echo password_hash('MotDePasseFortIci', PASSWORD_DEFAULT), PHP_EOL;"
```

## 4. Variables d'environnement serveur

À définir sur le serveur:

```txt
APP_ENV=production
ADMIN_BOOTSTRAP_REGISTER=0
APP_KEY=________________________
APP_SCHEMA_ENFORCE=1
APP_SUPPORT_STORAGE_DIR=________________________
APP_DRIVE_SERVICE_ACCOUNT_JSON=________________________
APP_DRIVE_OAUTH_TOKEN_PATH=________________________
APP_DRIVE_ROOT_FOLDER_ID=________________________
APP_GOOGLE_OAUTH_CLIENT_ID=________________________
APP_GOOGLE_OAUTH_CLIENT_SECRET=________________________
PAY_FEDAPAY_PUBLIC_KEY=________________________
PAY_FEDAPAY_SECRET_KEY=________________________
PAY_FEDAPAY_ENV=live
PAY_FEDAPAY_WEBHOOK_SECRET=________________________
```

Notes:

- `APP_KEY` doit être long, unique et aléatoire.
- `APP_SUPPORT_STORAGE_DIR` doit être hors web root.
- Les fichiers Google JSON / token doivent être hors dépôt et hors `htdocs`.

## 5. Google OAuth connexion utilisateur

À créer dans Google Cloud:

- Client OAuth Web: `oui / non`
- Domaine autorisé: `________________________`

URLs de redirection à enregistrer:

- `https://________________________/oauth/google`

Si sous-dossier:

- `https://________________________/ecolenumerique/oauth/google`

## 6. Google Drive

Deux modes possibles:

- Service account
- OAuth Drive

Si service account:

- fichier JSON placé hors site: `________________________`
- dossier Drive partagé avec le service account: `oui / non`
- `APP_DRIVE_ROOT_FOLDER_ID = ________________________`

Si OAuth Drive:

- callback à déclarer:
  - `https://________________________/drive/oauth_callback`
  - ou `https://________________________/ecolenumerique/drive/oauth_callback`
- token stocké hors site: `________________________`

Important:

- ne pas réutiliser d'anciens secrets/tokens exposés
- si Drive n'est pas configuré, l'application peut retomber sur un stockage local public

## 7. FedaPay

Clés à renseigner:

- clé publique: `________________________`
- clé secrète: `________________________`
- webhook secret: `________________________`
- environnement: `live`

URLs à déclarer:

- retour paiement:
  - `https://________________________/payment_return`
- webhook:
  - `https://________________________/api/fedapay_webhook`

Si sous-dossier:

- `https://________________________/ecolenumerique/payment_return`
- `https://________________________/ecolenumerique/api/fedapay_webhook`

## 8. Web Push

Fichier: `includes/config.php`

- `webpush.enabled = true / false`
- `webpush.vapid_public_key = ________________________`
- `webpush.vapid_private_key_path = ________________________`
- `webpush.vapid_subject = mailto:________________________`

Pré-requis:

- HTTPS obligatoire
- clé privée idéalement hors `storage/` public

## 9. Paramètres métier à compléter après mise en ligne

Dans l'admin:

- `admin/site_settings`
  - logo
  - favicon
  - texte footer
  - nom du DG
  - signature du DG

Entreprise:

- compléter les profils entreprise si utilisés
- compléter la configuration SSO entreprise si utilisée

Nom public du site:

- si besoin, renseigner aussi `site_name` dans la table `platform_settings`

## 10. Sécurité et stockage

Vérifier:

- dossier support privé: `________________________`
- permissions dossier support restreintes: `oui / non`
- permissions secrets Google restreintes: `oui / non`
- `admin/register` doit être inaccessible en prod
- HTTPS forcé
- sauvegardes DB planifiées

Point de contrôle:

- `SECURITY_DEPLOYMENT.md`

## 11. Tests après déploiement

Tests utiles:

```powershell
php app/tests/schema_coverage.php
php app/tests/login_throttle_functional.php
php app/tests/admin_bootstrap_route_guard.php
php app/tests/certificate_privacy_guard.php
php app/tests/support_storage_private_path_functional.php
```

Vérifications manuelles:

- page d'accueil accessible
- inscription / connexion email
- mail de vérification envoyé
- login Google fonctionne
- upload Drive fonctionne
- paiement FedaPay fonctionne
- webhook FedaPay reçu
- `/admin/register` retourne `404`
- `robots.txt` et `sitemap.xml` accessibles
- notifications push testées si activées

## 12. Fuseau horaire

Valeur actuelle dans `includes/bootstrap.php`:

- `Africa/Porto-Novo`

Décision:

- garder cette valeur: `oui / non`
- nouvelle valeur si besoin: `________________________`

## 13. Validation finale

- domaine ok: `oui / non`
- HTTPS ok: `oui / non`
- DB ok: `oui / non`
- SMTP ok: `oui / non`
- Google login ok: `oui / non`
- Drive ok: `oui / non`
- FedaPay ok: `oui / non`
- admin ok: `oui / non`
- tests ok: `oui / non`
- prêt pour prod: `oui / non`
