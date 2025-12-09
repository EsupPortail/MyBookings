# MyBookings

![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?style=flat-square&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.x-000000?style=flat-square&logo=symfony&logoColor=white)
![Node.js](https://img.shields.io/badge/Node.js-22+-339933?style=flat-square&logo=nodedotjs&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=flat-square&logo=vuedotjs&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-003545?style=flat-square&logo=mariadb&logoColor=white)

> MyBookings fournit une solution logicielle complète pour la réservation, la gestion des accès et le suivi de parc matériel. Pensée pour les établissements d'enseignement supérieur et les organisations, elle combine une UX simple pour les utilisateurs finaux, une grande flexibilité pour les services et une architecture extensible pour les équipes IT.

🔗 **[Plus d'information sur la page du projet](https://mybookings.uca.fr/about)**

---

## 📋 Prérequis

| Composant | Version / Détails |
|-----------|-------------------|
| [MyBookings-services](https://github.com/EsupPortail/MyBookings-services) | Conteneur de service **installé et configuré** |
| PHP | 8.4+ |
| Extensions PHP | `curl`, `mysql`, `xml`, `mbstring`, `zip`, `soap` |
| Composer | Dernière version stable |
| Node.js | ≥ 22.x |
| MariaDB | Dernière version stable |

---

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/EsupPortail/MyBookings
cd MyBookings
```

### 2. Configuration de l'environnement

```bash
cp .env .env.local
```

Éditez le fichier `.env.local` avec vos paramètres :

| Variable | Description | Exemple |
|----------|-------------|---------|
| `DATABASE_URL` | Connexion à la base de données | `mysql://user:password@domaine:3306/mybookings_db` |
| `AUTH_TYPE` | Type d'authentification | `UCA` ou `SHIBBOLETH` |
| `REMOTE_CONTAINER_URL` | URL du conteneur MyBookings-services | `https://mybookings-services.domaine` |
| `REMOTE_CONTAINER_TOKEN` | Clé secrète pour l'authentification | `votre_token_secret` |
| `CAS_LOGIN` / `CAS_VALIDATION` / `CAS_LOGOUT` | Variables CAS (si applicable) | — |

### 3. Installation des dépendances back-end

```bash
composer install --no-interaction
```

### 4. Configuration de la base de données

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate
```

### 5. Installation des dépendances front-end

```bash
# Avec npm
npm install && npm run build

# Ou avec yarn
yarn install && yarn build
```

### 6. Configuration du serveur web

Configurez votre serveur web (Apache/Nginx) pour pointer vers le répertoire `public/` du projet.

---

## 🧪 Données de test (Développement uniquement)

> ⚠️ **Attention** : Cette manipulation supprime les données existantes dans la base.

Configurez les utilisateurs de test dans `.env.local` :

```env
DEFAULT_GROUP_STUDENT='["student1","student2","student3"]'
DEFAULT_GROUP_TEACHER='["teacher1","teacher2","teacher3"]'
DEFAULT_GROUP_TESTER='["tester1","tester2","tester3"]'
DEFAULT_GROUP_PROVIDER=db
```

Puis chargez les fixtures :

```bash
php bin/console hautelook:fixtures:load
```

---

## ⏰ Tâches planifiées (Cron)

MyBookings nécessite des tâches cron pour :
- Mise à jour automatique des statuts de réservation
- Envoi des notifications par email
- Synchronisation des groupes d'utilisateurs

Ajoutez ces lignes à votre crontab (adaptez les chemins) :

```cron
# Mise à jour des réservations (toutes les 5 min)
*/5 * * * * /usr/local/bin/php /var/www/html/bin/console app:auto-update-bookings >> /var/www/html/var/log/crontab.log

# Mise à jour des groupes (tous les jours à 6h)
00 06 * * * /usr/local/bin/php /var/www/html/bin/console app:auto-update-groups >> /var/www/html/var/log/crontab.log

# Nettoyage des fichiers de verrouillage (toutes les 10 min)
*/10 * * * * find /var/www/html/var/locks -name "*.lock" -type f -mmin +1 -delete
```

---

## 🔐 Anonymisation des données (RGPD)

Définissez la durée de rétention des données (en mois) dans `.env.local` :

```env
LIMIT_CLEANUP_OLD_DATA=12
LIMIT_CLEANUP_OLD_DATA_STATISTICS=24
```

Ajoutez la tâche cron pour l'anonymisation automatique :

```cron
# Anonymisation des anciennes données (tous les jours à 5h15)
15 05 * * * /usr/local/bin/php /var/www/html/bin/console app:cleanup:old-data >> /var/www/html/var/log/crontab.log
```

---

## C'est prêt !

Accédez à l'application via votre navigateur à l'adresse configurée dans votre serveur web.

**Bonne réservation !**
