# TopTopGo — Backend Laravel 11 (Complet)

API REST complète pour la plateforme de covoiturage TopTopGo.

## Installation rapide

```bash
composer create-project laravel/laravel toptopgo
cd toptopgo
# Copier les fichiers du zip
composer require laravel/sanctum
cp .env.example .env
php artisan key:generate
# Configurer .env (DB PostgreSQL, Redis)
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

## Compte admin par défaut
- Email : admin@toptopgo.com
- Password : Admin@1234

## Ce qui est inclus (104 fichiers)

| Catégorie         | Fichiers                                          |
|-------------------|---------------------------------------------------|
| Models            | 19 modèles (Role, Driver, User, Trip, Payment...) |
| Migrations        | 12 fichiers — 20 tables dans l'ordre              |
| Controllers       | 20 controllers (Admin, Driver, User, Auth)        |
| Form Requests     | 8 requests avec validation                        |
| API Resources     | 6 resources JSON formatées                        |
| Services          | 4 services (Trip, Wallet, FileUpload, AdminLog)   |
| Events            | 5 events WebSocket (broadcast)                    |
| Notifications     | 4 notifications (DB)                              |
| Console Commands  | 3 commandes (documents, GPS, rapport)             |
| Seeders           | 5 seeders                                         |
| Tests             | 5 fichiers (Feature + Unit)                       |
| Config            | auth.php + toptopgo.php                           |
| Routes            | 55+ endpoints API                                 |

## Tâches planifiées
Ajouter dans crontab :
```
* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
```

## WebSocket (Laravel Reverb)
- trip.created → chauffeurs disponibles
- trip.status.updated → client/chauffeur
- message.sent → chat trip
- sos.alert → dashboard admin
- driver.location → carte admin

## Commission & Tarifs
Configurable dans config/toptopgo.php ou .env :
- Commission : 20% (TOPTOPGO_COMMISSION_RATE)
- Retrait minimum : 500 XAF
- GPS interval : 10 secondes
