# Site web de Ina Zaoui, photographe

## Pré-requis

* PHP 8.3
* Symfony 7.4
* PostgreSQL
* Composer 2.10
* Extension PHP Xdebug
* PHPUnit 11.5

## Installation
``` bash
git clone https://github.com/zacube/p15inazaoui.git  
cd p15inazaoui  
composer install  
```  

## Configuration
Créer un fichier d'environnement ".env.local" à la racine du projet, et le renseigner avec vos valeurs pour 'user', 'password' et 'dbname' :
```dotenv  
###> doctrine/doctrine-bundle ###  DATABASE_URL="pgsql://user:password@127.0.0.1:5432/dbname?serverVersion=17.6&charset=utf8"  ###< doctrine/doctrine-bundle ###  
```  

## Données
Le fichier [backup.zip](https://s3.eu-west-1.amazonaws.com/course.oc-static.com/projects/876_DA_PHP_Sf_V2/P15/backup.zip) contient un dump SQL anonymisé de la base de données et toutes les images qui se trouvaient dans le dossier public/uploads. Ces données peuvent être utilisées pour mettre en route le site.

- Créer la base de données
```bash  
php bin/console doctrine:database:create```  
- Créer les tables selon les entités  
```bash  
php bin/console doctrine:schema:create```  
  
- Dézipper le fichier backup.zip  
- Copier le dossier /public/uploads dans votre répertoire /public  
- Importer successivement les 3 fichiers user.sql, media.sql et album.sql  
  
```psql  
psql -U postgres -d p15inazaoui -f user.sql  
psql -U postgres -d p15inazaoui -f media.sql  
psql -U postgres -d p15inazaoui -f album.sql  
```  

> La table user.sql ne remplit pas la propriété $password de l'entité User. Il est donc nécessaire fournir un mot de passe hashé pour tous les utilisateurs afin que le site fonctionne correctement.
- Deux commandes sont prévues dans le dossier src/Command/Migration :
  - `SetAdminPasswordCommand` pour ajouter un mot de passe spécifique pour l'administratrice (ina@zaoui.com):    
    `php bin/console app:set-admin-password <mot_de_passe> <email>`
  - `SetPasswordCommand` pour ajouter le même mot de passe à tous les invités :    
    `php bin/console app:set-admin-password <mot_de_passe>`


- Lancer le projet :
```bash  
symfony serve
```  
→ Le site sera accessible sur `https://127.0.0.1:8000`.

## Données de test
Afin de réaliser des tests unitaires et fonctionnels, il est nécessaire de créer une base de données spécifique.
- Créer la base de données de test
```bash  
php bin/console doctrine:database:create --env=test
```  
- Exécuter les migrations
```bash  
php bin/console doctrine:migrations:migrate --env=test
```  
- Charger les fixtures pour les tests
```bash  
php bin/console doctrine:fixtures:load --env=test
```  

/!\  **Si une migration doit être faite, il faut l'appliquer sur les 2 environnements (dev et test) .**


Plusieurs utilisateurs sont créées pour les tests :

| login                | mot de passe | Rôle  |     |
| -------------------- | ------------ | ----- | --- |
| ina@zaoui.com        | password     | ADMIN |     |
| invite+1@example.com | aze          | user  |     |
| invite+2@example.com | aze          | user  |     |
| invite+3@example.com | aze          | user  |     |
| invite+4@example.com | aze          | user  |     |
| invite+5@example.com | aze          | user  |     |

## Tests unitaires et fonctionnels
- Exécution des tests
```bash  
symfony php bin/phpunit
```  
Les tests s'appuient sur le bundle _dama/doctrine-test-bundle_ pour remettre la base de données de test dans son état initial après chaque test.


- Pour lancer la génération de la couverture de code
```bash  
vendor/bin/phpunit --coverage-html var/coverage
```  

## Intégration continue (CI)

La mise en place d'outils d'analyse du code permet de détecter au fur et à mesure du développement les régressions et erreurs.
**PHPStan** est un analyseur statique qui détecte dans le code PHP sans l'exécuter la présence de bugs potentiels en vérifiant la cohérence des types, les appels de méthodes inexistantes, les retours de fonctions mal définis, etc.
```bash
./vendor/bin/phpstan
```

**PHP-CS-Fixer** est un outil (linter) qui corrige automatiquement le style du code pour le conformer à des conventions d'écriture (PSR-12, PSR-2, Symfony...) pour garantir une uniformité dans le code.
```
./vendor/bin/php-cs-fixer fix --dry-run --diff --verbose   // pour avoir un apreçu des modifications proposées
./vendor/bin/php-cs-fixer fix // pour appliquer au code les modifications proposées.
```

**PhpUnit** permet de lancer des tests unitaires et des tests fonctionnels pour vérifier la validité du code. Il permet aussi de vérifier la couverture de code.
```bash
vendor/bin/simple-phpunit // pour lancer les tests unitaires et fonctionnels
vendor/bin/simple-phpunit --coverage-html var/coverage  // pour générer la couverture de code
```

Avant de pousser de code vers le dépôt distant, ou de faire une Pull Request, s'assurer que ces outils ne détectent pas d'erreurs en local.


## Fonctionnement du site

Le site est le portfolio de Ina Zaoui, photographe, où elle présente son travail personnel, organisé en différents albums.    
Elle accueille également des invités, à qui elle propose une page où ils peuvent publier leurs propres images.

Le site est géré par Ina Zaoui, qui est la seule administratrice.  
L'accès à la partie "Administration" est protégé par mot de passe.  
Une fois dans la partie admin, elle peut :
- ajouter et supprimer ses images
- les classer dans des albums qu'elle crée, renomme ou supprime
- créer un compte pour un invité.

Lorsqu'elle crée un nouveau compte invité, le nouvel utilisateur reçoit un mail l'invitant à indiquer son mot de passe. (_fonctionnalité en attente d'implémentation_)    
L'invité a alors accès à son compte personnel, où il peut seulement ajouter ou supprimer des images à sa galerie.

L'administratrice garde le contrôle des comptes "invités". Elle peut :
- ajouter ou supprimer les images d'un compte invité
- bloquer un invité, qui n'a alors plus accès à son compte
- supprimer définitivement un compte et les meédias associés.

### Accès à la partie Admin
`HomeController.php` gère la partie publique du site.  
L'accès à la partie admin passe par `SecurityController.php`, et le composant Security de Symfony, pour  authentifier les utilisateurs et vérifier leur rôle.

Le ROLE_ADMIN, attribué à Ina Zaoui, donne un accès complet à l'administration.    
Les invités, qui ont tous le ROLE_USER, sont limités à l'administration de leur compte.

Tous les utilisateurs sont stockés en BD dans la table USER. Cette table stocke pour chaque utilisateur :
- le nom, l'identifiant (email) et le mot de passe hashé
- un booléen "owner" (pour identifier Ina Zaoui)
- un booléen "blocked" (pour identifier les utilisateurs bloqués par Ina Zaoui)

"Blocked" est utilisé lors de la connexion pour identifier un utilisateur   
Lors de l'authentification, si un utilisateur est bloqué,  `UserChecker.php` (déclaré dans _security.yaml_) est appelé et lance une exception. Cette exception est récupéré par un EventListener `LoginFailureListener.php`  (déclaré dans _services.yaml_) qui renvoie l'utilisateur vers une page spéciale l'invitant à contacter l'administrateur.


### Ajout de médias
Lorsque l'administratrice utilise la formulaire d'ajout de média, elle doit en premier lieu indiquer l'utilisateur qui recevra le nouveau média. Si cet utilisateur a le ROLE_ADMIN, un champ supplémentaire apparait pour choisir l'album. L'apparition de ce champ est géré par un script JS inclus dans la fichier *templates/admin/media/add.html.twig*

Lors de la soumission d'une nouvelle image via le formulaire `MediaType.php` le système de validation de Symfony déclenche les **contraintes de validation** fournies par le composant `Symfony\Component\Validator\Constraints\Image` qui vérifient le poids (< 2Mo) et le type de l'image (jpeg, png ou webp).

### Suppression de médias
Le bouton de suppression est en fait un formulaire qui envoie une requête POST vers la route `admin_media_delete`, avec un jeton CSRF pour la sécurité.


### Ajout d'un invité
Seule l'administratrice peut ajouter un nouvel invité via le formulaire `GuestType.php`. Elle renseigne le nom, l'email et une description. Le mot de passe n'est pas indiqué, car un mail est envoyé au nouvel invité avec un lien pour qu'il crée lui-même son mot de passe. <u>Cette fonctionnalité n'a pas été implémentée</u> compte tenu des conditions de développement en mode local.  
Dans la configuration actuelle, lors de la validation d'un nouvel invité, un mot de passe temporaire est généré dans `GuestController.php` (admin_guest_add) . Puis il est hashé pour être stocké directement en base. Pour pouvoir conserver une trace de ce mot de passe, il est également envoyé au service `FileLogPasswordNotifier` qui implémente une interface `PasswordNotifierInterface`.  
Cette interface dispose de 2 méthodes, notify et forget, pour inscrire ou supprimer le mot de passe dans un fichier temporaire *var/dev_passwords.log* .
À terme, le service pourrait utiliser `symfonycasts/reset-password-bundle`
<mark style="background: #FFB86CA6;">**Gestion des mots de passe en attente de finalisation**  </mark>

### Optimisation des requêtes
Dans la partie publique du site, la page "invités" a été optimisée pour limiter le nombre de requêtes. Dans `UserRepository.php`, la fonction `findAllGuestsWithDto`
génère une seule requête pour récupérer les informations $id, $name, $mediaCount pour chaque utilisateur, et les stocker ensuite sous la forme d'un tableau d'instances DTO `GuestListDto`,

### Optimisation des images
L'utilisation de LiipImagineBundle permet de réduire considérablement le poids des images transmises en ne transmettant que la partie "utile" des images.
Le bundle est configuré via `config/packages/liip_imagine.yaml`, et est appelé depuis les templates twig comportant des balises \<img\>.
Ex : \<img class="w-100" src="{{ media.path | imagine_filter('media_vignette') }}"\>

### Optimisation des performances avec OPcache
Pour améliorer la réactivité du site, activer OPcache en modifiant `php.ini`.
Ouvrir `php.ini`, et décommenter les lignes suivantes :
```php.ini
opcache.enable=1  
opcache.enable_cli=1  
opcache.memory_consumption=128  
opcache.max_accelerated_files=10000  
opcache.validate_timestamps=1  
opcache.revalidate_freq=0    
```


