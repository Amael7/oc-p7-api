# oc-p7-api

## Informations du projet
OpenClassroom : Projet n°7 de la formation Développeur d'application - PHP / Symfony.

Nom du Projet : Créez un web service exposant une API

### Version du projet

- Symfony 6.1
- PHP 8.0.0
- mysql 8.0.28
- Symfony CLI version 5.3.0
- Composer version 2.2.5

### Installation

1.Clonez le repo :
      
        git clone git@github.com:Amael7/oc-p7-api.git

2.Modifier le .env avec vos informations.

3.Installez les dependances :

         composer install


4.Mettez en place la BDD :

         php bin/console doctrine:database:create
         php bin/console doctrine:migrations:migrate

5.Implementez les fixtures :

         php bin/console doctrine:fixtures:load
         
6.Lancer le serveur :
  
         symfony serve
