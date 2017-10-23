# Guide de contribution


## Présentation de l'environnement.


### Dossier ``coverage``

Ce dossier sert à déposer le rapport de couverture des tests.
Il ne doit pas être remonté sur le serveur Git à l'exception du fichier ``.required``
qui permet sa création chez les utilisateurs.




### Dossier ``docs``

Ce dossier acceuille la documentation qui vous sera particulierement utile.
Lorsque vous avez cloné le projet, faites ``phpdocs`` pour que celle-ci soit générée.

Ce dossier ne doit pas être remonté sur le serveur Git à l'exception du fichier
``cache/.required`` qui permet sa création chez les utilisateurs.




### Dossier ``src``

Il s'agit du dossier source dans lequel se trouve les classes PHP constituant le programme.
Votre contribution sur le moteur s'effectuera sur les fichiers qui s'y trouvent dedans.




### Dossier ``tests``

Il s'agit du dossier dans lequel est centralisé l'ensemble des tests permettant d'assurer
le bon fonctionnement du moteur. Si des tests échouents sur des partie où vous n'êtes pas
intervenu, vous avez cassé quelque chose.

Charge à vous de définir la bonne baterie de tests ou les ajustements en fonction des 
modifications que vous avez apportés sur le projet.


#### Sous-dossier ``Autoload``

Ce dossier est l'environnement de tests de la classe ``SYSLang\Autoloader``.




#### Sous-dossier ``CLI``

Ce dossier sert d'environnement de tests pour executer des ligne de commande manuelle pour
ne pas altérer d'autres dossiers en laissant des traces (fichiers).




#### Sous-dossier ``Command``

Ce dossier est l'environnement de tests de la classe ``SYSLang\Command``.




#### Sous-dossier ``Compiler``

Ce dossier est l'environnement de tests de la classe ``SYSLang\Compiler``.




#### Sous-dossier ``dev``

Ce dossier est l'environnement développement pour une exécution sous serveur HTTPD.




#### Sous-dossier ``resources``

Ce dossier contient les différentes ressources utilisées par les tests.
Si vous avez besoin de créer des fichiers personnalisés, au lieu de les générer en code,
vous pouvez le créer à la main dans ce dossier puis l'utiliser dans les tests par la suite.




#### Sous-dossier ``SYSLang``

Ce dossier est l'environnement de tests de la classe ``SYSLang\SYSLang``.




### Fichier ``bootstrap.php``

Ce fichier est chargé en amont par le moteur ``phpunit``.
Il contient des configurations globales qui seront utilisés dans les différents tests
permettant ainsi de multiplier les déclarations.




## Contribuer


### Nettoyer le projet 

J'ai fais un script **bash** pour nettoyer les dossiers suivants pour disposer d'un
environnement propre : ``./cleanse.sh``

* Dossier ``coverage``
* Dossier ``docs``




### Générer la documentation

Grâce aux commentaires ``DocBLock`` qui se trouvent dans les différents fichiers, il est 
possible d'automatiser la génération d'une documentation.
Pour la générer, il suffit de taper la commande ``phpdoc`` dans la racine du projet.

Pour que la commande ``phpdoc`` soit reconnue, il convient d'abord de se munir de l'archive
**PHAR** et de la déposé dans le dossier ``/usr/local/bin`` :

```bash
wget http://phpdoc.org/phpDocumentor.phar
chmod +x phpDocumentor.phar
sudo mv phpDocumentor.phar /usr/local/bin/phpdoc
```



### Lancer les tests

Grâce au fichier de configuration ``phpunit.xml``, seule la commande ``phpunit`` suffit
pour jouer la batterie de tests définie dans le dossier `tests`.

Pour que la commande ``phpunit`` soit reconnue, il convient d'abord de se munir de l'archive
**PHAR** et de la déposé dans le dossier ``/usr/local/bin`` :

```bash
wget https://phar.phpunit.de/phpunit-5.7.phar
chmod +x phpunit-5.7.phar
sudo mv phpunit-5.7.phar /usr/local/bin/phpunit
```




### Générer les sorties ``STDOUT`` et ``STDERR`` de la ligne de commande pour les tests

Si vous êtes intervenu sur la classe ``SYSLang\Command``, il faudra probablement mettre
à jour les fichiers contenant les sorties ``STDOUT`` et ``STDERR`` qui permettent la
comparaison entre les tests de ``Command`` et ce que vous obtenez réellement en ligne
de commande.

Pour ce faire il faut maintenir et exécuter le script **bash** ``makeCLItxt`` qui se trouve
dans le dossier ``tests/Command``.
    