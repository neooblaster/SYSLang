# Moteur de langue SYSLang

* Etats GitLab Grenoble

[![pipeline status](https://gitlab-gre.viseo.net/MCOScheduler/Engines/SYSLang/badges/master/pipeline.svg)](https://gitlab-gre.viseo.net/MCOScheduler/Engines/SYSLang/commits/master)
[![coverage report](https://gitlab-gre.viseo.net/MCOScheduler/Engines/SYSLang/badges/master/coverage.svg)](https://gitlab-gre.viseo.net/MCOScheduler/Engines/SYSLang/commits/master)

* Etats GitLab Perso

[![build status](http://gitlab.neoblaster.fr/Engines/SYSLang/badges/master/build.svg)](http://gitlab.neoblaster.fr/Engines/SYSLang/commits/master)
[![coverage report](http://gitlab.neoblaster.fr/Engines/SYSLang/badges/master/coverage.svg)](http://gitlab.neoblaster.fr/Engines/SYSLang/commits/master)


## Sommaire

[](BeginSummary)
* [Sommaire](#sommaire)
* [SYSLang :: Interface CLI](#syslang-interface-cli)
    * [Options transversales](#options-transversales)
        * [Installation](#installation)
        * [Dossier de travail](#dossier-de-travail)
        * [Mode silencieux](#mode-silencieux)
    * [Installation de l'environnement](#installation-de-lenvironnement)
    * [Enregistrer des nouvelles langues au registre](#enregistrer-des-nouvelles-langues-au-registre)
    * [Supprimer des langues du registre](#supprimer-des-langues-du-registre)
    * [Définir la langue par défaut](#d%c3%a9finir-la-langue-par-d%c3%a9faut)
    * [Mise à jour des langues enregistrées](#mise-%c3%a0-jour-des-langues-enregistr%c3%a9es)
    * [Exportation des textes sous une forme simplifiée (INI).](#exportation-des-textes-sous-une-forme-simplifi%c3%a9e-%28ini%29)
    * [Importation des textes à partir de fichiers .INI.](#importation-des-textes-%c3%a0-partir-de-fichiers-ini)
* [SYSLang :: Sous PHP](#syslang-sous-php)
    * [Installation de l'environnement](#installation-de-lenvironnement)
    * [Enregistrer des nouvelles langues au registre](#enregistrer-des-nouvelles-langues-au-registre)
    * [Supprimer des langues du registre](#supprimer-des-langues-du-registre)
    * [Définir la langue par défaut](#d%c3%a9finir-la-langue-par-d%c3%a9faut)
[](EndSummary)




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




### Mise à jour des langues enregistrées

Lors des évolutions, les fichiers de langue sont amenés à évoluer.
Certain texte peut avoir changé, il peut y avoir eu des ajouts et même des suppressions.
Plus il y a de langues enregistrées, plus la maintenance devient impossible manuellement.

Pour répondre à ces besoins, le moteur SYSLang dispose de fonctionnalités permettant cette
maintenance.

L'option qui permet cette maintenance est ``--deploy``. Cette maintenance sera effectuée
en se référant à la langue par défaut définie dans le fichier de configuration ``languages.xml``.

```bash
SYSLang --deploy
```

Si la langue définie par défaut, n'est pas la langue de référence pour vos développements,
il est possible de spécifier la langue de référence à l'aide de l'option ``--from xx-XX``.

Par exemple, votre application définie l'anglais comme langue par défaut, mais que vous faites vos
développements via la langue française, il faudra donc maintenir l'ensemble des langues 
enregistrées via la commande suivante :

```bash
SYSLang --deploy --from fr-FR
```




### Exportation des textes sous une forme simplifiée (INI).

Pour simplifier l'operation de traduction, j'ai décidé de sortir les textes de leur structure **XML** et de
les présenter plus simplement dans un fichier **.INI**.
Les traducteurs n'ont plus qu'à se concentrer sur la traduction des textes qui se trouvent dans
l'ensemble INI ``[TEXTS]``.

Pour passer du format **XML** au format **INI**, j'ai donc créé l'option ``--export``.

Si cette option est utilisée seule, l'exportation est effectuée dans le dossier ``exports`` là où le système
est installé.

```bash
# Exemple
# Système installé dans le dossier "Lang"
cd Lang

# Exportation Simple
SYSLang --export

# L'exportation s'est effectuée dans le dossier suivant : ./exports
# Soit dans Lang/exports
```

**Important**: Seul les textes ayant besoin d'être traduit sont exportés.
S'il n'y a aucune information concernant ce besoin de traduction, alors il sera exporté.


Si vous souhaitez dans tous les cas tout exporter, alors il faut ajouter l'option ``--complete``.

```bash
# Tout extraire
cd Lang
SYSLang --export --complete
```


Il est possible de spécifier un dossier cible pour l'exportation à l'aide de l'option ``--export-dir``.
Si vous utilisez un chemin relatif, celui-ci sera relatif par rapport à l'emplacement du système
et non pas de l'endroit où vous vous trouvez.

```bash
# Exemple 1 : Commande dans le dossier où le système est installé
cd Lang
SYSLang --export --export-dir ExportINI
# Les fichiers seront extrait dans Lang/ExportINI
```

```bash
# Exemple 2 : Commande en spécifiant l'emplacement du système
SYSLang --dir Lang --export --export-dir ExportINI
# Vaut pour Lang/ExportINI
# Et non pas pour ./ExportINI
```

Si vous spécifiez un chemin absolu, celui-ci partira donc comme prévu depuis l'emplacement root ``/``
du système d'exploitation.

```bash
cd Lang
SYSLang --export --export-dir /var/www/ini
```




### Importation des textes à partir de fichiers .INI.

Une fois les traductions faites, il ne reste plus qu'à procéder à l'importation pour mettre à jour
les différents fichiers **XML**.

L'option pour effecuter cette importation est ``--import``.

Si cette option est utilisée seule, l'importation est effectuée depuis le dossier ``imports`` là où le système
est installé.

```bash
# Exemple
# Système installé dans le dossier "Lang"
cd Lang

# Exportation Simple
SYSLang --import

# L'importation s'est effectuée depuis le dossier suivant : ./imports
# Soit dans Lang/imports
```


Il est possible de spécifier un dossier source pour l'importation à l'aide de l'option ``--import-dir``.
Si vous utilisez un chemin relatif, celui-ci sera relatif par rapport à l'emplacement du système
et non pas de l'endroit où vous vous trouvez.

```bash
# Exemple 1 : Commande dans le dossier où le système est installé
cd Lang
SYSLang --import --import-dir INIToImport
# Les fichiers seront chargé depuis Lang/INIToImport
```

```bash
# Exemple 2 : Commande en spécifiant l'emplacement du système
SYSLang --dir Lang --import --import-dir INIToImport
# Vaut pour Lang/INIToImport
# Et non pas pour ./INIToImport
```

Si vous spécifiez un chemin absolu, celui-ci partira donc comme prévu depuis l'emplacement root ``/``
du système d'exploitation.

```bash
cd Lang
SYSLang --import --import-dir /var/www/ini
```

**Important** : Par défaut, le système d'importation détruit les fichiers traités.

Il est possible de demander au système de conserver ces fichiers via l'option ``--preserve-files``.

```bash
SYSLang --import --preserve-files
```


Le système de maintenance des textes de manière "**différentielle**".
En effet, la maintenance des textes étant faite depuis une langue de référence,
écraser les traductions existantes rajouterais du travail aux contributeurs.

C'est pour cette raison que le système dispose d'un attribut indiquant s'il faut ou non effectuer
une opération de traduction. Cet attribut est ``TIR`` pour `Translation Is Required`.

Une opération de traduction est nécessaire si le texte a changé ou si un texte a été ajouté.

Une fois que la traduction effectuée est importée, si le résultat est conforme aux attentes,
alors il faut demander au système de finaliser cette importation.

Ceci se fait à l'aide de l'option ``--finalize``. Cela aura pour effet de mettre les attributs
``TIR`` à `false`.

Ainsi les prochaines extractions **INI** contiendrons uniquement les textes ayant besoin d'être traduit.
Je rappelle que le couple d'options ``--export --complete`` permet de tout extraire.

Pour finaliser l'importation, il faut donc effectuer la commande suivante.

```bash
SYSLang --import --finalize
```

Pour le fun, une commande relativement complète.
```bash
# Je me trouve dans le dossier NGINX
# Je veux faire l'importation avec finalisation
# Tout en concervant mes fichiers
# Qui sont dans un dossier précis "Translates"
SYSLang --dir /var/www/app/lang --import --import-dir Translates --finalize --preserve-files
```







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
    