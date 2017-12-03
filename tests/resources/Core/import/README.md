# Dossier de ressources pour les tests **Core\Import**

Rôle des différents dossier :

* ``src`` : Fichiers INI source servant aux tests.
* ``default`` : Fichiers INI à importer et résultats avec les paramètres par défaut pour `import()`.
* ``finalize`` : Fichiers INI à importer et résultats avec l'argument `finalize = true`.
* ``perserve`` : Fichiers INI à importer et résultats avec les arguments `finalize` et `preserve` à `true`.


En cas de modification, les tests échouerons tant que les mises à jour nécessaires n'auront pas été effectuées.

## En cas de modification du test ``testDeploy`` :

La modication du test ``testDeploy`` peut avoir des conséquences sur la suites des tests, dont `testExport` et
``testImport``.

Il est souhaitable que le test d'importation échoue, car les ressources exportées ont évolué et les ressources
de contrôle présent dans le sous-dossier ``résult`` doit être mise à jour.

Il conviendra donc de déposer les différents fichiers INI générés par le test ``testExport`` et d'y apporter
quelques modifications comme cela aurait lieu lorsque le fichier aurait été traduit.

Exemple :

Traduire les différents textes présent dans ``[TEXTS]`` :

```
[TEXTS]
000.00000 = your_coresponding_text_here
000.00001 = SIMULATE
001.00000 = your_coresponding_text_here
```

```
[TEXTS]
000.00000 = ici_votre_texte_correspondant
000.00001 = CLE_UNE
001.00000 = ici_votre_texte_correspondant
```

```
[TEXTS]
000.00000 = dein_entsprechender_text_hier
000.00001 = SIMULIEREN
001.00000 = dein_entsprechender_text_hier
```

Ensuite, il faudra mettre à jour les différents dossiers ``results`` pour certifier les tests pour le reste du temps.

il faudra le faire en plusieurs temps, car les langues mise à jour seront manipulé plusieurs fois par les tests.

Cette opération est voulue, car elle permet de contrôler à l'élaboration que tout se passe normalement.
Le reste du temps, les tests et les ressources font leur office, c'est-à-dire de contrôler qu'aucun changements
non désirés à eu lieu lors des futurs développements.
