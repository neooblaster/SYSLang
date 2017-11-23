# Moteur de langue SYSLang

#### Etats GitLab Grenoble
[![build status](http://gitlab.neoblaster.fr/Engines/SYSLang/badges/master/build.svg)](http://gitlab.neoblaster.fr/Engines/SYSLang/commits/master)
[![coverage report](http://gitlab.neoblaster.fr/Engines/SYSLang/badges/master/coverage.svg)](http://gitlab.neoblaster.fr/Engines/SYSLang/commits/master)

#### Etats GitLab Perso

[![build status](http://gitlab.neoblaster.fr/Engines/SYSLang/badges/master/build.svg)](http://gitlab.neoblaster.fr/Engines/SYSLang/commits/master)
[![coverage report](http://gitlab.neoblaster.fr/Engines/SYSLang/badges/master/coverage.svg)](http://gitlab.neoblaster.fr/Engines/SYSLang/commits/master)




## SYSLang :: Interface CLI


### Options transversales


#### Installation

```bash
# Configuration du "binaire" SYSLang :
chmod +x src/command_index.php
sudo ln -s /path/to/projet/src/command_index.php /usr/local/bin/SYSLang
```




#### Dossier de travail

L'outil de ligne de commande accepte comme option transversable ``--directory`` (`--dir`) 
qui sert à l'ensemble des commandes pour définir le dossier de travail. 
Si l'option est omise, le dossier de travail est le dossier courant dans lequel 
est exéxutée la commande.

```bash
SYSLang --directory path/to/installation [OPTIONS]
```



#### Mode silencieux

Par défaut, l'outil est configuré pour être verbeux. 
Si vous ne souhaitez pas recevoir de messages d'informations il suffit d'ajout l'option
``--silent``.

```bash
SYSLang [OPTIONS] --silent
```




### Installation de l'environnement

Pour rendre le dossier de travail comme étant système de langue **SYSLang**, 
il suffit de taper la commande suivante :

```bash
SYSLang --install 
```




### Enregistrer des nouvelles langues au registre

L'installation n'inclus aucune langue dans le registre et donc ne défini pas de langue 
par défaut.
L'enregistrement de la première langue définiera également celle-ci comme étant langue 
par défaut.

Pour enregistrer une langue, celle-ci doit être au format ``xx-XX:NomDeLaLangue`` où
 
* `xx` est le code de langue  selon l'[`ISO 639-1`](https://fr.wikipedia.org/wiki/Liste_des_codes_ISO_639-1)
* `XX` est le code pays selon l'[`ISO 3166 alpha-2`](http://www.nationsonline.org/oneworld/country_code_list.htm)
* `NomDeLaLangue` est le nom de la langue dans sa propre langue. 

Exemples : 

* Pour le Français en métropole ``fr-FR:Français``.
* Pour l'Anglais Britanique ``en-EN:English``.
* Pour le Japonais ``jp-JP:日本の``.

L'option permettant l'enregistrement d'une langue est ``--add-languages``.

```bash
SYSLang --add-languages fr-FR:Français
```

L'option admet un ou plusieurs couple ``xx-XX:Name`` et doivent être séparés par une virgule (`,`).

```bash
SYSLang --add-languages fr-FR:Français,en-EN:English,jp-JP:日本の

# Ou Encore ainsi si vous souhaitez utiliser des espaces dans les noms.
SYSLang --add-languages 'fr-FR:Français, en-EN:English, jp-JP:日本の'
```




### Supprimer des langues du registre

Il est possible de supprimer une langue (ou plusieurs) du registre.

Pour supprimer une langue du registre, il suffit de spécifier son code de langue au format 
``xx-XX`` à l'option `--remove-languages` (`--remove-langs`).

L'option admet une ou plusieurs valeurs ``xx-XX`` et doivent être séparés par une virgule 
(`,`).

```bash
SYSLang --remove-languages jp-JP,it-IT

# Ou Encore ainsi si vous souhaitez utiliser des espaces.
SYSLang --remove-languages 'jp-JP, it-IT'
```

**Important** : Lorsque la langue par défaut est supprimée du registre, une nouvelle est 
définie automatiquement par le programme. 




### Définir la langue par défaut

Si la langue par défaut définie ne convient pas, il suffit de modifier celle-ci à l'aide 
de l'option suivante ``--set-default-lang``.
Elle accepte comme valeur, le code de langue au format `xx-XX`.

```bash
SYSLang --set-default-lang en-EN
```

Il est impossible de définir une langue non enregistrée comme langue par défaut.










## SYSLang :: Sous PHP

Avant d'effectuer des opérations avec le moteur sous **PHP**, il y à deux façons de l'instancier :

* Soit en utilisant un espace de nom.
* Soit en utilisant l'espace de nom global.

Dans l'ensemble de la documentation '***sous PHP***', j'utiliserais la variable 
``$core`` étant l'instanciation de la classe `Core` en suivant l'une de ces 
deux méthodes. Les résultats étant évidemment le même en terme d'utilisation.

* Instanciation dans l'espace de nom `SYSLang\Compiler`:

    ```php
    use SYSLang\Core;

    $core = new Core();
    ```

* Instanciation dans l'espace de nom `global`:

    ```php
    $core = new \SYSLang\Core();
    ```


### Installation de l'environnement

Pour rendre le dossier de travil courant comme étant un système de langue **SYSLang**, 
il suffit d'exécuter l'instruction suivante :

```php
$core->install(); 
```



### Enregistrer des nouvelles langues au registre

L'installation n'inclus aucune langue dans le registre et donc ne défini pas de langue 
par défaut.
L'enregistrement de la première langue définiera également celle-ci comme étant langue 
par défaut.

Pour enregistrer une langue, celle-ci doit être au format ``xx-XX:NomDeLaLangue`` où
 
* `xx` est le code de langue  selon l'[`ISO 639-1`](https://fr.wikipedia.org/wiki/Liste_des_codes_ISO_639-1)
* `XX` est le code pays selon l'[`ISO 3166 alpha-2`](http://www.nationsonline.org/oneworld/country_code_list.htm)
* `NomDeLaLangue` est le nom de la langue dans sa propre langue. 

Exemples : 

* Pour le Français en métropole ``fr-FR:Français``.
* Pour l'Anglais Britanique ``en-EN:English``.
* Pour le Japonais ``jp-JP:日本の``.

La méthode ``addLanguages`` admet autant d'arguments `xx-XX:NomDeLaLangue` que vous voulez.

```php
$core->addLanguages('fr-FR:Français');

# Ou encore ainsi si vous souhaitez ajouter plusieurs langue à la fois.
$core->addLanguages('en-EN:English', 'jp-JP:日本の');
```




### Supprimer des langues du registre

Il est possible de supprimer une langue (ou plusieurs) du registre.

Pour supprimer une langue du registre, il suffit de spécifier son code de langue au format 
``xx-XX`` à la méthode `removeLanguages`. Il faudra d'abord indiqué si vous voulez concerver
les fichiers de langue dans le dossier courant (``$preverveFiles``).

La méthode admet autant d'arguments ``xx-XX`` que vous voulez.

```php
$core->removeLanguages(true, 'en-EN');

# Ou encore ainsi si vous souhaitez supprimer plusieurs langue à la fois.
$core->removeLanguages(true, 'en-EN', 'jp-JP');
```

**Important** : Lorsque la langue par défaut est supprimée du registre, une nouvelle est 
définie automatiquement par le programme. 




### Définir la langue par défaut

Si la langue par défaut définie ne convient pas, il suffit de modifier celle-ci à l'aide 
de la méthode ``setDefaultLanguage``.
Elle accepte comme valeur, le code de langue au format `xx-XX`.

```php
$core->setDefaultLanguage('fr-FR');
```

Il est impossible de définir une langue non enregistrée comme langue par défaut.
    