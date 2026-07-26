# Contribuer au projet

Merci de vouloir contribuer à ce projet, les contributions sont les bienvenues.
## Workflow GitHub

Ce workflow déclenche une action (ci.yaml) :
- à chaque **push** vers `main`
- à chaque **pull request** ciblant `main` (avant le merge)``

Les 4 jobs qui sont exécutés doivent réussir pour que la PR soit acceptée :
- php-cs-fixer
- phpstan
- phpunit
- composer audit
#### Avant toute PR :
Lancer la suite complète en local :
```bash
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse
vendor/bin/simple-phpunit
```

Générer un rapport HTML détaillé :
```bash
vendor/bin/simple-phpunit --coverage-html var/coverage
```


### Issues
- Toute nouvelle fonctionnalité, ou bug doit faire l'objet d'une **issue** avant d'être développée.
### Branches
- Une branche par issue, nommée `type/description-courte` :
    - `feature/media-pagination`
    - `fix/csrf-token-session`
    - `chore/upgrade-phpunit`
- Ne jamais committer directement sur `main`.

### Pull Requests
- Une PR doit rester **petite et ciblée** sur un seul sujet — préférez plusieurs PR successives à une PR géante difficile à relire.
- Le titre de la PR doit être clair et, si possible, référencer l'issue (`Fixes #42`).
- La description doit inclure :
    - Ce qui a changé et pourquoi
    - Comment tester manuellement le changement si pertinent
    - Toute migration de base de données nécessaire (**dev ET test**)
- Avant d'ouvrir une PR, s'assurer que les tests passent en local (PHPStan, php-cs-fixer, phpunit) et que la couverture de code ne régresse pas.
### Merge
- Utiliser **squash and merge** pour garder un historique `main` lisible, sauf si l'historique détaillé de la branche a un intérêt particulier.
- Supprimez la branche après merge.