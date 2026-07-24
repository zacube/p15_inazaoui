# Site web de Ina Zaoui, photographe

## Pré-requis

* PHP 8.3
* PostgreSQL
* Composer 2.10
* Symfony 7.4
* Extension PHP Xdebug

## Installation
``` bash
git clone https://github.com/zacube/p15inazaoui.git
cd p15inazaoui
composer install
```

## Configuration
Créer un fichier d'environnement ".env.local" à la racine du projet, et le renseigner avec vos valeurs pour 'user', 'password' et 'dbname' :
```dotenv
###> doctrine/doctrine-bundle ###  
DATABASE_URL="pgsql://user:password@127.0.0.1:5432/dbname?serverVersion=17.6&charset=utf8"  
###< doctrine/doctrine-bundle ###
```
#### Supprimer la base de données
```bash
symfony console doctrine:database:drop --force --if-exists
```
### Serveur web
Pour lancer le projet :
```bash
symfony serve
```
Le site sera accessible sur `https://127.0.0.1:8000`.



## Tests
### Base de données de test
#### Créer la base de données de test
```bash
php bin/console doctrine:database:create --env=test
```
#### Exécuter les migrations
```bash
php bin/console doctrine:migrations:migrate --env=test
```
#### Charger les fixtures
```bash
php bin/console doctrine:fixtures:load --env=test
```

Plusieurs utilisateurs sont créées pour les tests

| login                | mot de passe | Rôle  |
| -------------------- | ------------ | ----- |
| ina@zaoui.com        | password     | ADMIN |
| invite+1@example.com | aze          | user  |
| invite+2@example.com | aze          | user  |
| invite+3@example.com | aze          | user  |
| invite+4@example.com | aze          | user  |
| invite+5@example.com | aze          | user  |

### Exécution des tests
```bash
symfony php bin/phpunit
```
Pour lancer la **génération de la couverture de code**
```bash
vendor/bin/phpunit --coverage-html var/coverage
```


Les tests utilisent
- dama/doctrine-test-bundle
- phpstan
- php-cs-fixer

**Compte bloqué**
User comporte un booléen 'blocked' pourles compte bloqué , et un booléen 'owner' pour identifier ina zaoui
dans security.yaml,
```
firewalls:  
    main:  
        user_checker: App\Security\UserChecker
```
utilise UserChecker lors de la connexion pour orienter un utilisateur bloqué vers la route SecurityController/user_blocked
Dans Security/, UserChecker lève une exception 'UserBlockedException'.
Elle est récupéré par src/EventListener/LoginFailureListener.php
LoginFailureListener.php est configuré dans services.yaml pour écouter les LoginFailureEvent ; si ce dernier est de type UserBlockedException, LoginFailureEvent renvoie l'utilisateur vers la route 'user_blocked'

**Optimisation des requêtes**
utilisation des dto : une seule requête pour stocker les informations $id, $name, $mediaCount des utilisateurs


**Gestion des mots de passe en attente de finalisation**









php.ini
**OPcache**
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=1
opcache.revalidate_freq=0

**Xdebug**
zend_extension=xdebug
xdebug.mode=coverage


