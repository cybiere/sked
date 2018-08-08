# Sked

Outil de gestion de planning.

## Fonctionnalités

> **Note sur l'internationalisation :**
>
> (fr) Bien que le code soit rédigé en anglais, Sked a été réalisé pour un contexte francophone. Au delà de la langue du site, celui-ci utilise aussi les jours fériés Français.
>
> (en) Although code is in english, Sked was developped for a french environment, including french holidays.

## Installation

### Production

La branche master est la branche de production. Pour avoir la dernière version stable, il suffit de tirer la dernière version de la branche master. Les sources sont disponible dans le dossier "www".

Sur la base d'un environnement Debian 9 à jour : 

```
cd /opt
git clone https://github.com/ncosnard/sked.git
apt-get install git apache2 php mysql-server composer zip zlib1g-dev libldap2-dev php-mysql php-zip php-ldap php-xml
mysql_secure_installation
a2enmod rewrite
sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
sed -i 's%/var/www/html%/opt/sked/www/public%g' /etc/apache2/sites-enabled/000-default.conf
sed -i 's%/var/www%/opt/sked/www%g' /etc/apache2/apache2.conf
cd /opt/sked/www
composer update
##### Créer la BDD et l'utilisateur #####
##### Créer le compte LDAP pour bind #####
chown -R www-data:www-data /opt/sked/www
/opt/sked/www/bin/console doctrine:schema:create
##### Modifier /opt/sked/www/.env avec les informations de connexion à la base de données #####
##### Modifier /opt/sked/www/config/services.yaml avec les informations de connexion au serveur LDAP #####
```

Une fois l'installation effectuée, il suffit d'aller sur le site web et de se connecter avec un utilisateur LDAP. Si aucun utilisateur n'existe en base de données, un compte est créé automatiquement pour le premier utilisateur à se connecter, avec les droits d'administration.

> **Attention :**
> Il est nécessaire que les entrées LDAP aient une adresse email renseignée pour l'ajout d'utilisateurs.

### Développement

La branche de développement contient les sources ainsi que les configurations docker pour le projet. Cela inclut le conteneur "site" (Apache + php7.1 + extensions, voir Dockerfile), le conteneur "mysql" pour la base de données et le conteneur "ldap" pour le serveur ldap. Ce dernier repose sur l'image ["rroemhild/test-openldap"](https://github.com/rroemhild/docker-test-openldap) qui contient des données de test basées sur les personnages de la série Futurama.

Le tout est orchestré par docker-compose.

```
git clone https://github.com/ncosnard/sked.git
cd sked
git checkout dev
docker-compose build
docker-compose up -d
docker exec -ti sked_site_1 /bin/bash
##### Dans le docker : #####
composer update
exit
##### Hors du docker #####
##### Modifier /opt/sked/www/.env avec les informations de connexion à la base de données #####
```

Par défaut, l'environnement est accessible sur http://localhost:80

## Documentation

La documentation est en cours de rédaction et sera bientôt disponible sur [le site de sked](https://sked.team).
