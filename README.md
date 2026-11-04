# Cofina Pointage

Application web de gestion du pointage (présence) des employés : pointage par QR code de site avec
contrôle GPS, code OTP, déclarations (retard, absence, congé, régularisation), paramétrage RH des horaires
et des jours fériés, rapports et exports. Elle sert aussi de backend à l'application mobile de
pointage (API `/api`, schéma d'URL `cofipointe` par défaut).

## Fonctionnalités

- **Pointage employé** : arrivée / départ, historique, profil de pointage, code OTP envoyé par
  e-mail et/ou SMS selon le paramétrage, position GPS obligatoire.
- **Sites de pointage** (agences) : QR code par site, régénération, désactivation, rayon de
  géofencing, lien de borne (kiosque) à jeton secret qui affiche le QR du site sur une tablette.
- **Déclarations** (retard, absence, congé, régularisation) avec circuit de validation manager puis RH.
- **Espace manager** : équipe, retards et absences, rapports du service.
- **Espace RH** : employés et affectations aux sites (enrôlement, site principal, statut),
  jours ouvrables, week-ends, pauses (déjeuner, technique, durée), jours fériés (saisie,
  calendrier PDF, import depuis l'API publique Nager.Date), récupération de pointages,
  paramétrage et export de la fiche horaires, vue de tous les pointages.
- **Rapports et exports** Excel : mensuel RH, quotidien, journalier RH, synthèse RH ; export de
  l'historique et des profils.
- **Administration** : utilisateurs, rôles, profils (import / export Excel), départements,
  agences, filiales (contexte de filiale par utilisateur), QR codes, journaux d'audit, sécurité.
- **Comptes** : connexion, double authentification (2FA), réinitialisation et changement
  obligatoire du mot de passe, vérification de l'e-mail (Laravel Fortify).
- **API mobile** : connexion + OTP, enregistrement de l'appareil, scan de QR, check-in /
  check-out, historique, pointage du jour, profil, notifications (jetons Sanctum) ; génération de
  QR réservée aux administrateurs (JWT).

## Stack technique

- PHP 8.3+, Laravel 12, Laravel Fortify, Sanctum, Inertia.js 2
- Vue 3 + TypeScript, Vite 7, Tailwind CSS 4, reka-ui, lucide-vue-next, Laravel Wayfinder
- Exports : maatwebsite/excel (Excel), dompdf (PDF) ; QR : `qrcode` ; signatures : `signature_pad`
- JWT : firebase/php-jwt ; SMS : Twilio (optionnel) ; e-mails : SMTP, Mailgun ou Resend
- Base de données : SQLite par défaut (`.env.example`), MySQL possible
- Tests : Pest 4

## Prérequis

- PHP 8.3 ou plus récent avec Composer
- Node.js et npm (la CI utilise Node 22)
- SQLite (par défaut) ou MySQL

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # si DB_CONNECTION=sqlite
php artisan migrate
npm install
npm run build
```

Le script Composer `composer setup` enchaîne ces étapes (sauf la création du fichier SQLite).

Données de départ (rôles, utilisateur de test, données de démonstration du pointage) :

```bash
php artisan db:seed
```

Créer un administrateur :

```bash
php artisan admin:create --name="..." --email="..." --password="..."
```

## Configuration

Variables d'environnement à renseigner dans `.env` (aucune valeur n'est donnée ici) :

- **Application** : `APP_NAME`, `APP_URL`, `APP_ENV`, `APP_DEBUG`, `APP_LOCALE`, `APP_TIMEZONE`
- **Base de données** : `DB_CONNECTION`, `DB_DATABASE` (et `DB_HOST`, `DB_PORT`, `DB_USERNAME`,
  `DB_PASSWORD` pour MySQL)
- **File d'attente** : `QUEUE_CONNECTION`
- **E-mail** : `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
  `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
- **SMS (Twilio)** : `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_MESSAGING_SERVICE_SID`,
  `TWILIO_SMS_FROM`
- **OTP de pointage** : `POINTAGE_OTP_SMS_ENABLED`, `POINTAGE_OTP_SMS_DRIVER` (`log` par défaut,
  ou `twilio`), `POINTAGE_OTP_SMS_DEFAULT_CC`, `POINTAGE_OTP_MAIL_MAILER`,
  `POINTAGE_OTP_EMAIL_FALLBACK_LOG`
- **Règles de pointage** (`config/pointage.php`) : horaires et plages (`POINTAGE_HEURE_ARRIVEE`,
  `POINTAGE_HEURE_DEPART`, `POINTAGE_PLAGE_*`, `POINTAGE_TOLERANCE_MINUTES`), géofencing
  (`POINTAGE_GEOFENCING_ENABLED`, `POINTAGE_DEFAULT_GEOFENCING_RADIUS`), QR (`POINTAGE_QR_TTL`,
  `POINTAGE_QR_SCAN_URL`, `POINTAGE_QR_ENCODE_AS_URL`), borne (`POINTAGE_KIOSK_AUTO_SITE_GPS`),
  application mobile (`POINTAGE_MOBILE_APP_SCHEME`), heures supplémentaires et pénalités
  (`POINTAGE_SEUIL_HS_JOUR`, `POINTAGE_MAJORATION_HS_PCT`, `POINTAGE_PENALITE_ABSENCE`), export
  paie (`POINTAGE_MODE_EXPORT_SAGE`)
- **API mobile** (`config/pointrust.php`) : `POINTRUST_JWT_SECRET` (à définir en production),
  `POINTRUST_ACCESS_TTL`, `POINTRUST_REFRESH_TTL`, `POINTRUST_OTP_TTL`, `POINTRUST_QR_TTL`,
  `POINTRUST_LOGIN_REQUIRES_OTP`, `POINTRUST_DEVICE_REGISTER`, `POINTRUST_MOBILE_WEB_LOGIN`,
  `POINTRUST_MOBILE_NATIVE_SCHEME`
- `POINTAGE_DEV_UNLOCK_CODE` : déverrouillage du pointage, pris en compte seulement si
  `APP_DEBUG` est actif ; à laisser vide en production.

## Utilisation

Lancer le serveur, la file d'attente, les journaux et Vite en une commande :

```bash
composer dev
```

L'application redirige `/` vers la page de connexion. Accès par rôle : `super_admin`, `admin`,
`rh`, `metier`, `controle`, `finance`, `md`, `conformite` (voir `database/seeders/RoleSeeder.php`).

Routes utiles hors connexion web :

- `/pointage/kiosk/{token}` : borne d'affichage du QR d'un site ;
- `/mobile/pointage/scan` : page ouverte lors du scan d'un QR de site ;
- `/mobile/pointrust/login` : connexion pour la WebView de l'application mobile.

Tâches planifiées (`routes/console.php`) — lancer le planificateur Laravel (`php artisan
schedule:work` en local, ou `schedule:run` en cron) :

| Commande | Fréquence |
| --- | --- |
| `pointage:feries-import-nager` | chaque année, le 2 janvier à 3 h |
| `pointage:feries-prolonger-recurrence` | chaque année, le 1er janvier à 4 h |
| `pointage:feries-auto-pointage` | chaque jour à 0 h 05 |

Autres commandes Artisan : `admin:create`, `user:create-with-profil`, `profils:fix-n-plus1`,
`roles:migrate-to-users`, `mail:test`.

## Structure du projet

```
app/Http/Controllers/        contrôleurs web (pointage, RH, sites, admin)
app/Http/Controllers/Api/    API mobile (auth, appareil, scan, présence, notifications)
app/Services/Pointage/       logique métier du pointage (horaires, fériés, récupération…)
app/Services/Pointrust/      JWT et contenu des QR codes
app/Exports/                 exports Excel
app/Console/Commands/        commandes Artisan
config/pointage.php          règles de pointage
config/pointrust.php         réglages de l'API mobile
resources/js/pages/          pages Vue (Inertia)
routes/web.php, api.php      routes web et API
public/build/                assets front compilés (versionnés dans le dépôt)
scripts/oracle-proxy-python/ proxy HTTP FastAPI vers Oracle, non appelé par le code Laravel actuel
```

## Tests

```bash
composer test        # ou ./vendor/bin/pest
```

La CI GitHub Actions (`.github/workflows/`) lance sur `main` et `develop` les tests Pest et un
contrôle de style (Pint, Prettier, ESLint). Commandes de style en local :

```bash
vendor/bin/pint
npm run format
npm run lint
```

## Déploiement

Aucun script de déploiement n'est fourni. Pour une mise en production : `composer install --no-dev`,
`php artisan migrate --force`, `npm run build` (ou utiliser les assets de `public/build/`), un
worker de file d'attente (`php artisan queue:work`) et le planificateur Laravel en cron.

## Licence

Aucune licence n'est déclarée dans ce dépôt (pas de fichier LICENSE ; le champ `license: MIT` de
`composer.json` est celui hérité du starter kit Laravel).
