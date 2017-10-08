# Release de la classe SYSLang

Objet de la version ``2.0.0`` : Concevoir un coeur du moteur, une interface pour le développement PHP avec des méthodes dédiées et une inteface en ligne de commande.

L'objectif est aussi de transformer le moteur en respectant les **standards PSR**.


## 2.0.x-alpha3 (2017-10-07)

- [CHANGED] ``addLanguage`` devient ``addLanguages``, car admet un ou plusieurs code de langue.
- [CHANGED] ``removeLanguage`` devient `removeLanguages`,car admet un ou plusieurs code de langue.
- [CHANGED] ``compile`` devient `deploy` qui correspond plus à l'aspect fonctionnel.
- [ADDED] Ajout de la méthode ``checkCode`` qui valide le format réprésentant un code de langue.
- [ADDED] Ajout de la méthode ``setRefLanguage`` qui définie la langue de référence.
- [ADDED] Ajout de la méthode ``getRefLanguage`` qui retourne le pack de langue de référence.


## 2.0.x-alpha2 (2017-10-07)

- [CHANGED] ``Compilator`` devient ``Compiler``.


## 2.0.x-alpha1 (2017-10-06)

- [ADDED] Création d'un espace de nom : ``SYSLang``.
- [CHANGED] ``SYSLangCompilator`` devient ``Compilator`` et fait office de coeur.
- [CHANGED] ``SYSLang`` reste ``SYSLang``, mais n'a de rôle que pour l'utilisation sur serveurs Web sous PHP 
et n'est plus le coeur.
- [CHANGED] ``get_avail_languages`` devient ``getRegLanguages`` dans la classe ``Compilator``.
- [CHANGED] ``list_languages`` devient ``listLanguages`` dans la classe ``Compilator``.
- [CHANGED] ``save_xml`` devient ``saveXml`` dans la classe ``Compilator``.
- [CHANGED] ``add_language`` devient ``addLanguage``.
- [CHANGED] ``build_environnement`` devient ``install``.
- [CHANGED] ``environnement_exists`` devient ``isInstalled``.
- [ADDED] Mise en place des tests unitaires sous **GitLab**.
- [ADDED] Développement sous tests **phpunit**.
- [ADDED] Ajout du Changlog ``CHANGELOG.md``.
- [ADDED] Ajout de la configuration pour générer la documentation à l'aide de ``phpdoc``.





[!ADDED]:#
[!FIXED]:#
[!CHANGED]:#
[!REMOVED]:#
[!SECURITY]:#
[!DEPRECATED]:#
[!OTHER]:#
[!BUGFIX]:#
