<?php
/**
 * Created by PhpStorm.
 * User: Neoblaster
 * Date: 03/10/2017
 * Time: 15:29
 */

use SYSLang\Core;

require_once "src/SYSLang/Autoloader.php";

class CoreTest extends \PHPUnit_Framework_TestCase
{
    use initializer;

    /**
     * @var Core $compiler Instance pour les tests
     */
    protected static $compiler = null;

    /**
     * @var array $cleanseExcluded Fichier à ne pas supprimer lors du nettoyage.
     */
    protected static $cleanseExcluded = ['.', '..', 3 => 'Install'];

    /**
     * @var string $testWorkingDir Emplacement de travail pour dérouler les tests
     */
    protected static $testWorkingDir = 'tests/Core';

    /**
     * @var string $testResPath Emplacement vers les ressources utilisées lors des tests.
     */
    protected static $testResPath = 'tests/resources/Core';

    /**
     * @var string $exportDir Dossier de dépôt des fichiers d'exportation INI.
     */
    protected static $exportDir = "exports";

    /**
     * @var string $importDir Dossier de dépôt des fichiers INI à importer.
     */
    protected static $importDir = "imports";



    /**
     * Les dataProviders
     */

    /**
     * Liste d'arguments invalide pour la methode addLanguages().
     * @author Neoblaster
     * @return array
     */
    public function badLanguageCodesToAdd()
    {
        return [
            # Donnée déja enregistée
            ['fr-FR:Français'],
            # Code incorrecte
            ['fra-FRA']
        ];
    }

    /**
     * Liste d'arguments invalide pour la methode removeLanguages().
     * @author Neoblaster
     * @return array
     */
    public function badLanguageCodesToRemove()
    {
        return [
            // Donnée inexistante
            ['The language code "aa-AA" is not registered in "languages.xml"', true, 'aa-AA'],
            // Code incorrecte
            ['Argument supplied "fra-FRA" is not valid. It must be like this xx-XX. Argument "fra-FRA" is skipped.', true, 'fra-FRA']
        ];
    }

    /**
     * Liste d'argument invalide pour la métode setRefLanguage.
     * @author Neoblaster
     * @return array
     */
    public function badLanugageCodesAsCompileRef()
    {
        return [
            # Exception - Code invalide
            ['fra-FRA', 'The language code "fra-FRA" provided is not valid. It must be like xx-XX.'],
            # Exception - Code inexistant
            ['zz-ZZ', 'The requested language code "zz-ZZ" is not registered.']
        ];
    }



    /**
     * Setups et Méthods interne
     */

    /**
     * Initialisation de la batterie de tests (Execution une seule fois).
     */
    static function setUpBeforeClass()
    {
        /** Nettoyage avant exécution des tests */
        self::$cleanseExcluded[2] = basename(__FILE__);

        /**
         * Live test ici :
         */


        self::cleanseDir(self::$testWorkingDir, self::$cleanseExcluded);
    }

    static function simulateExistingInstallation()
    {
        // Nettoyer le dossier Install
        self::cleanseDir(self::$testWorkingDir . '/Install', ['.', '..', '.required']);

        // Instanciation et création d'un environnement
        $envMaker = new Core(self::$testWorkingDir . '/Install');
        $envMaker->install();
        $envMaker->addLanguages('fr-FR:Français', 'en-EN:English');

        return $envMaker;
    }



    /**
     * Batterie de tests
     */

    /**
     * Controle du bon emplacement des tests.
     * @author Neoblaster
     */
    public function testShowWorkingDir()
    {
        self::$compiler = new Core(self::$testWorkingDir);

        $this->assertEquals(self::$cleanseExcluded, self::$compiler->showWorkingDir());
    }

    /**
     * Contrôle la présence du fichier languages.xml
     * @expectedException \Exception
     * @author Neoblaster
     */
    public function testIsInstalled()
    {
        /** Vérification de l'installation */
        $this->assertEquals(false, self::$compiler->isInstalled());
        self::$compiler->isInstalled(true);
    }

    /**
     * Controle de l'installation.
     * @author Neoblaster
     */
    public function testInstall()
    {
        /** Installation */
        $this->assertEquals(true, self::$compiler->install());

        /** Contrôler la présence du fichier language.xml */
        $this->assertEquals(true,
            in_array(Core::XML_CONFIG_FILE, scandir(self::$testWorkingDir))
        );

        /** Opération similaire de contrôle */
        $this->assertEquals(true, self::$compiler->isInstalled());

        /** Seconde installation ne fais rien (false) */
        $this->assertEquals(false, self::$compiler->install());
    }

    /**
     * Controle le bon fonctionnement de l'enregistrement de langue
     *
     * @expectedException \Exception
     *
     * @author Neoblaster
     */
    public function testAddLanguages()
    {
        # Doit ajouter la langue fr-FR + par défaut + dossier nommé avec un fichier generic.xml
        $this->assertEquals(true, self::$compiler->addLanguages('fr-FR:Français'));

        # Doit enregistrer les trois langue : en-EN, de-DE et jp-JP
        $this->assertEquals(true, self::$compiler->addLanguages('en-EN:English'));
        $this->assertEquals(true, self::$compiler->addLanguages('de-DE:Deutch', 'it-IT:Italian'));

        # Doit emettre une exception (ne peut etre dataProvidé)
        $this->expectExceptionMessage("At least one language name with code must be provided. " .
            "It must be like this : xx-XX:Name");
        self::$compiler->addLanguages();
    }

    /**
     * Test des exception émis par la méthode AddLanguages.
     * @author Neoblaster
     * @dataProvider badLanguageCodesToAdd
     * @expectedException \Exception
     */
    public function testAddLanguagesExceptions($langCode)
    {
        self::$compiler->addLanguages($langCode);
    }

    /**
     * Contrôle le bon fonctionnement de la méthode isRegistered
     */
    public function testIsRegistered()
    {
        $this->assertEquals(true, self::$compiler->isRegistered('fr-FR'));
        $this->assertEquals(false, self::$compiler->isRegistered('aa-AA'));

        $this->expectException('Exception');
        $this->expectExceptionMessage('The language "aa-AA" is not registred');
        $this->assertEquals(false, self::$compiler->isRegistered('aa-AA', true));
    }

    /**
     * Contrôle de la liste des langues enregistrées.
     * @author Neoblaster
     */
    public function testGetRegLanguages()
    {
        $this->assertEquals([
            'KEYS' => [
                'fr-FR' => 'Français',
                'en-EN' => 'English',
                'de-DE' => 'Deutch',
                'it-IT' => 'Italian'
            ],
            'LIST' => [
                ['LANG_KEY' => 'fr-FR', 'LANG_NAME' => 'Français'],
                ['LANG_KEY' => 'en-EN', 'LANG_NAME' => 'English'],
                ['LANG_KEY' => 'de-DE', 'LANG_NAME' => 'Deutch'],
                ['LANG_KEY' => 'it-IT', 'LANG_NAME' => 'Italian']
            ]
        ], self::$compiler->getRegLanguages());
    }

    /**
     * @expectedException \Exception
     * @author Neoblaster
     */
    public function testSetDefaultLang()
    {
        $this->assertEquals(true, self::$compiler->setDefaultLanguage('fr-FR'));
        $this->assertEquals(true, self::$compiler->setDefaultLanguage('en-EN'));
        self::$compiler->setDefaultLanguage('jp-JP');
    }

    /**
     * Vérifie la bonne suppression d'une langue dans le registre.
     *  Avec conservation des fichiers
     *  @TODO: Avec suppression des fichiers
     * @author Neoblaster
     */
    public function testRemoveLanguages()
    {
        // Supprimer une langue lambda
        $this->assertEquals(true, self::$compiler->removeLanguages(true, 'it-IT'));
        $this->assertEquals([
            'KEYS' => [
                'fr-FR' => 'Français',
                'en-EN' => 'English',
                'de-DE' => 'Deutch'
            ],
            'LIST' => [
                ['LANG_KEY' => 'fr-FR', 'LANG_NAME' => 'Français'],
                ['LANG_KEY' => 'en-EN', 'LANG_NAME' => 'English'],
                ['LANG_KEY' => 'de-DE', 'LANG_NAME' => 'Deutch']
            ]
        ], self::$compiler->getRegLanguages());

        // Supprtimer la langue par défaut
        $this->assertEquals(true, self::$compiler->removeLanguages(true, 'en-EN'));

        // La ré-enregistrée et redéfinir par défaut
        $this->assertEquals(true, self::$compiler->addLanguages('en-EN:English'));
        $this->assertEquals(true, self::$compiler->setDefaultLanguage('en-EN'));
    }

    /**
     * @dataProvider badLanguageCodesToRemove
     * @expectedException \Exception
     * @author neoblaster
     */
    public function testRemoveLanguagesExceptions($message, $preserve, $langCode)
    {
        $this->expectExceptionMessage($message);
        self::$compiler->removeLanguages($preserve, $langCode);
    }

    /**
     * @expectedException \Exception
     * @author neoblaster
     */
    public function testRemoveLanguagesExceptionsForArgNumber()
    {
        $this->expectExceptionMessage('At least one language code must be provided after argument $preserveFiles.');
        self::$compiler->removeLanguages(true);
    }

    /**
     * Test le bon fonctionnement des exceptions
     * @dataProvider badLanugageCodesAsCompileRef
     * @expectedException \Exception
     * @author Neoblaster
     */
    public function testSetRefLanguageExceptions($argument, $exceptionMessage)
    {
        $this->expectExceptionMessage($exceptionMessage);
        self::$compiler->setRefLanguage($argument);
    }

    /**
     * Vérifie que la selection du paquet de référence fonctionne correctement.
     * @author Neoblaster
     */
    public function testSetRefLanguage()
    {
        $this->assertEquals(true, self::$compiler->setRefLanguage('fr-FR'));
        $this->assertEquals('fr-FR', self::$compiler->getRefLanguage());
    }

    /**
     * Test l'exception de la méthode deploy lorsque la langue de référence n'a aucun fichier.
     * @author Neoblaster
     */
    public function testDeployEmptyLanguage()
    {
        self::$compiler->setRefLanguage("en-EN");

        $this->expectException("Exception");
        $this->expectExceptionMessage("There is no folder name 'en-EN' to deploy.");

        self::$compiler->deploy();
    }

    /**
     * Contrôle de la bonne maintenance des langues.
     *
     * EN CAS DE MODIFICATION : En cas de modifications sur :
     *  - Une clé
     *  - Un texte
     *  - Un fichier
     * Le test import échouera, car les ressources ne seront plus à jour.
     * CF testExport pour la marche à suivre.
     *
     * @author Neoblaster
     */
    public function testDeploy()
    {
        /**
         * Initialisation du tests
         */
        # Test de déploiement sur les langues suivantes.
        $ref = 'fr-FR';
        $langs = ["en-EN", "de-DE"];
        $keyToManipulate = "KEY_TEXT_ADD_BY_TEST";
        $textToInsert = "TEXT ADD BY TEST";
        $textUpdated = "TEXT EDIT BY TEST";

        # Utilisation de la langue FR comme langue de référence
        self::$compiler->setRefLanguage($ref);

        # Création d'une ressource supplémentaire pour tester la récursivité
        mkdir(self::$testWorkingDir . "/$ref/path/to/sub/folder", 0777, true);
        copy(
            self::$testWorkingDir . "/$ref/generic.xml",
            self::$testWorkingDir . "/$ref/path/to/sub/folder/generic.xml"
        );



        /**
         * testDeploy#1 :: Création des langues inistantes.
         */

        # Controler que les dossiers des langues enregistrées n'existe pas (non déployée)
        foreach ($langs as $lang) {
            $this->assertEquals(false, file_exists(self::$testWorkingDir . "/$lang"));
        }

        self::$compiler->deploy();

        # Controler que le déploiement à bien fonctionné
        foreach ($langs as $lang) {
            $this->assertEquals(true, file_exists(self::$testWorkingDir . "/$lang"));
        }



        /**
         * testDeploy#2 :: Ajout de clés.
         */
        $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . '/fr-FR/generic.xml'));
        $newKey = $generic->addChild("resource", $textToInsert);
        $newKey->addAttribute("KEY", $keyToManipulate);
        $newKey->addAttribute("SST", "true");
        $newKey->addAttribute("CST", "false");
        Core::saveXml($generic, self::$testWorkingDir . '/fr-FR/generic.xml');

        # Contrôler l'absence de la clé KEY_TEXT_ADD_BY_TEST
        foreach ($langs as $lang) {
            $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . "/$lang/generic.xml"));
            $keyFound = false;

            foreach ($generic as $index => $resource) {
                if (strval($resource->attributes()->KEY) === $keyToManipulate){
                    $keyFound = true;
                    break;
                }
            }

            $this->assertEquals(false, $keyFound);
        }

        self::$compiler->deploy();

        # Contrôler la présence de la clé KEY_TEXT_ADD_BY_TEST suite au déploiement
        foreach ($langs as $lang) {
            $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . "/$lang/generic.xml"));
            $keyFound = false;

            foreach ($generic as $index => $resource) {
                if (strval($resource->attributes()->KEY) === $keyToManipulate){
                    $keyFound = true;
                    break;
                }
            }

            //$this->assertEquals(true, $keyFound);
        }



        /**
         * testDeploy#3 :: Mise à jour de clés existantes.
         */
        $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . '/fr-FR/generic.xml'));
        for ($r = 0; $r < count($generic->resource); $r++) {
            if (strval($generic->resource[$r]->attributes()->KEY) === $keyToManipulate) {
                $generic->resource[$r] = $textUpdated;
                break;
            }
        }
        Core::saveXml($generic, self::$testWorkingDir . '/fr-FR/generic.xml');

        # Contrôler que la valeur n'à pas été mise à jour
        foreach ($langs as $lang) {
            $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . "/$lang/generic.xml"));

            $assertDone = false;

            foreach ($generic as $index => $resource) {
                if (strval($resource->attributes()->KEY) === $keyToManipulate){
                    $this->assertEquals($textToInsert, strval($resource));
                    $assertDone = true;
                    break;
                }
            }

            $this->assertEquals(true, $assertDone);
        }

        self::$compiler->deploy();

        # Contrôler que la valeur a été mise à jour
        foreach ($langs as $lang) {
            $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . "/$lang/generic.xml"));

            $assertDone = false;

            foreach ($generic as $index => $resource) {
                if (strval($resource->attributes()->KEY) === $keyToManipulate){
                    $this->assertEquals($textUpdated, strval($resource));
                    $assertDone = true;
                    break;
                }
            }

            $this->assertEquals(true, $assertDone);
        }



        /**
         * testDeploy#4 :: Suppression de clés retirées.
         */
        $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . '/fr-FR/generic.xml'));
        for ($r = 0; $r < count($generic->resource); $r++) {
            if (strval($generic->resource[$r]->attributes()->KEY) === $keyToManipulate) {
                unset($generic->resource[$r]);
                break;
            }
        }
        Core::saveXml($generic, self::$testWorkingDir . '/fr-FR/generic.xml');

        # Contrôler que la clé n'à pas été supprimée.
        foreach ($langs as $lang) {
            $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . "/$lang/generic.xml"));

            $keyFound = false;

            foreach ($generic as $index => $resource) {
                if (strval($resource->attributes()->KEY) === $keyToManipulate){
                    $keyFound = true;
                    break;
                }
            }

            $this->assertEquals(true, $keyFound);
        }

        self::$compiler->deploy();

        # Contrôler que la clé a été supprimée.
        foreach ($langs as $lang) {
            $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . "/$lang/generic.xml"));

            $keyFound = false;

            foreach ($generic as $index => $resource) {
                if (strval($resource->attributes()->KEY) === $keyToManipulate){
                    $keyFound = true;
                    break;
                }
            }

            $this->assertEquals(false, $keyFound);
        }



        /**
         * testDeploy#5 :: Changement de référence :: Tester l'attribut TIR existant et passant à vrai.
         */
        self::$compiler->setRefLanguage("en-EN");
        $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . '/en-EN/generic.xml'));
        $nodeWithAttTIR = $generic->addChild('resource', "SIMULATE");
        $nodeWithAttTIR->addAttribute("KEY", "SIMULATE");
        $nodeWithAttTIR->addAttribute("SST", "true");
        $nodeWithAttTIR->addAttribute("CST", "false");
        $nodeWithAttTIR->addAttribute("TIR", "false");
        Core::saveXml($generic, self::$testWorkingDir . '/en-EN/generic.xml');

        self::$compiler->deploy();

        # Check dans la langue fr-FR que l'attribut est présent et vaut vrai
        self::$compiler->setRefLanguage("en-EN");
        $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . '/fr-FR/generic.xml'));

        foreach ($generic as $index => $resource) {
            if (strval($resource->attributes()->KEY === "SIMULATE")) {
                $this->assertEquals("true", strval($resource->attributes()->TIR));
            }
        }



        /**
         * Nettoyage
         */
        $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . '/fr-FR/generic.xml'));
        for ($r = 0; $r < count($generic->resource); $r++) {
            if (strval($generic->resource[$r]->attributes()->KEY) === "SIMULATE") {
                unset($generic->resource[$r]);
                break;
            }
        }
        Core::saveXml($generic, self::$testWorkingDir . '/fr-FR/generic.xml');
    }

    /**
     * Contrôle de la sécurisation de clé contre les interventions manuelles.
     * @author Neoblaster
     */
    public function testDeployExceptionKeyAlreadyUsed()
    {
        # Ajouter deux ressources ayant la même clé.
        $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . '/fr-FR/generic.xml'));
        $resourceA = $generic->addChild("resource", "KEY_ONE");
        $resourceA->addAttribute("KEY", "resource_key");
        $resourceB = $generic->addChild("resource", "KEY_TWO_DUPLICATED");
        $resourceB->addAttribute("KEY", "resource_key");
        Core::saveXml($generic, self::$testWorkingDir . '/fr-FR/generic.xml');

        # Le deploiement doit échouer
        $this->expectException("\Exception");

        self::$compiler->setRefLanguage("fr-FR");
        self::$compiler->deploy();
    }

    /**
     * Contrôle de la sécurisation de clé contre les interventions manuelles.
     * @author Neoblaster
     */
    public function testDeployExceptionInvalideXMLFile()
    {
        file_put_contents(self::$testWorkingDir . '/fr-FR/invalid.xml', "INVALID_XML_STRING");

        $this->expectException("\Exception");
        self::$compiler->deploy();
    }

    /**
     * Suite aux tests consécutif, il faut vérifier si la langue par défaut est utilisée lorsqu'aucune langue
     * n'est définie comme langue de référence.
     */
    public function testDeployFromDefaultLanguage()
    {

        $languages = Core::SXEOverhaul(
            file_get_contents(self::$testWorkingDir . '/' . Core::XML_CONFIG_FILE)
        );

        $defaultLang = strval($languages->attributes()->default);

        $core = new Core(self::$testWorkingDir);
        $rcore = new ReflectionObject($core);
        $refLang = $rcore->getProperty("refLanguage");
        $refLang->setAccessible(true);
        $defLang = $rcore->getProperty("defaultLanguage");
        $defLang->setAccessible(true);

        # Etats initiaux
        $this->assertEquals($defaultLang, $defLang->getValue($core));
        $this->assertEquals(null, $refLang->getValue($core));

        # Deploiement :: en-EN >> fr-FR
        $core->deploy();

        # Vérifier que refLanguage n'est plus null et vaut la langue par défault
        $this->assertEquals($defaultLang, $refLang->getValue($core));


        /**
         * Nettoyage
         */
        $generic = Core::SXEOverhaul(file_get_contents(self::$testWorkingDir . '/fr-FR/generic.xml'));
        for ($r = 0; $r < count($generic->resource); $r++) {
            if (strval($generic->resource[$r]->attributes()->KEY) === "SIMULATE") {
                unset($generic->resource[$r]);
                break;
            }
        }
        Core::saveXml($generic, self::$testWorkingDir . '/fr-FR/generic.xml');
    }

    /**
     * Vérifie la bonne définition de l'emplacement pour l'exportation des fichiers.
     *
     * @author Neoblaster
     */
    public function testSetExportDir()
    {
        $rcore = new ReflectionObject(self::$compiler);
        $rExportDirectoryPath = $rcore->getProperty("exportDirectoryPath");
        $rExportDirectoryPath->setAccessible("true");

        // Contrôle de la définition par défaut
        $this->assertEquals(
            self::$testWorkingDir . '/exports',
            $rExportDirectoryPath->getValue(self::$compiler)
        );

        // Contrôle d'un chemin relatif
        self::$compiler->setExportDirectory("export");
        $this->assertEquals(
            self::$testWorkingDir . '/export',
            $rExportDirectoryPath->getValue(self::$compiler)
        );

        // Contrôle d'un chemin absolu
        self::$compiler->setExportDirectory("/export");
        $this->assertEquals(
            '/export',
            $rExportDirectoryPath->getValue(self::$compiler)
        );

        // Contrôle d'un chemin pseudo-absolu
        self::$compiler->setExportDirectory("/export", false);
        $this->assertEquals(
            $_SERVER["PWD"] . '/export',
            $rExportDirectoryPath->getValue(self::$compiler)
        );
    }

    /**
     * Vérifie la bonne définition de l'emplacement pour l'importation des fichiers.
     *
     * @author Neoblaster
     */
    public function testSetImportDir()
    {
        $rcore = new ReflectionObject(self::$compiler);
        $rImportDirectoryPath = $rcore->getProperty("importDirectoryPath");
        $rImportDirectoryPath->setAccessible("true");

        // Contrôle de la définition par défaut
        $this->assertEquals(
            self::$testWorkingDir . '/imports',
            $rImportDirectoryPath->getValue(self::$compiler)
        );

        // Contrôle d'un chemin relatif
        self::$compiler->setImportDirectory("import");
        $this->assertEquals(
            self::$testWorkingDir . '/import',
            $rImportDirectoryPath->getValue(self::$compiler)
        );

        // Contrôle d'un chemin absolu
        self::$compiler->setImportDirectory("/import");
        $this->assertEquals(
            '/import',
            $rImportDirectoryPath->getValue(self::$compiler)
        );

        // Contrôle d'un chemin pseudo-absolu
        self::$compiler->setImportDirectory("/import", false);
        $this->assertEquals(
            $_SERVER["PWD"] . '/import',
            $rImportDirectoryPath->getValue(self::$compiler)
        );
    }

    /**
     * Controle le bon fonctionnement de la méthode SXEOverhaul lorsqu'elle contient des balises CDATA.
     * @author Neoblaster
     */
    public function testCDATAParsing()
    {
        $xml = Core::XML_HEADER . PHP_EOL;
        $xml .= "<root>" . PHP_EOL;
        $xml .= "\t<element><![CDATA[<h1>Code HTML dans XML</h1>]]></element>" . PHP_EOL;
        $xml .= "</root>" . PHP_EOL;

        $sxeo = Core::SXEOverhaul($xml);

        // <![CDATA ==> [[
        // ]]>      ==> ]]
        $this->assertEquals("[[::lt::h1::gt::Code HTML dans XML::lt::/h1::gt::]]", strval($sxeo->element[0]));
    }

    /**
     * Vérifie que l'exportation sous forme INI s'effectue normalement.
     *
     * EN CAS DE MODIFICATION : Copiez les fichiers exportés dans le dossier de ressources "Core/toImport"
     * et faites des modifications vallant pour une traduction.*
     * CF testImport pour la marche à suivre
     *
     * @author Neoblaster
     */
    public function testExport ()
    {
        /** Ne peux pas fonctionner si le fichier invalid.xml est présent */
        unlink(self::$testWorkingDir . '/fr-FR/invalid.xml');

        /** Exporter les fichiers dans l'environnement de test */
        self::$compiler->setExportDirectory(self::$exportDir);
        self::$compiler->export();

        /** Contrôler que le dossier d'exportation à bien été créé */
        $this->fileExists(self::$testWorkingDir . '/' . self::$exportDir);

        $exportDir = opendir(self::$testWorkingDir . '/' . self::$exportDir);

        while ($file = readdir($exportDir)) {
            if (preg_match("/^\.+$/", $file)) continue;

            $generated = file_get_contents(self::$testWorkingDir . '/' . self::$exportDir . '/' .$file);

            $this->assertRegExp("/[HEADERS]/", $generated);
            $this->assertRegExp("/[FILES]/", $generated);
            $this->assertRegExp("/[KEYS]/", $generated);
            $this->assertRegExp("/[TEXTS]/", $generated);
            $this->assertRegExp("/^[0-9]{3}\.[0-9]{5}\s=\s(.)+$/m", $generated);
        }

        closedir($exportDir);
    }

    /**
     * Met à jour les fichiers de langue à l'aide des fichier ini.
     *
     * EN CAS DE MODIFICATION : Si des modifications ont eu lieu dans testDeploy et/ou testExport
     * Après l'execution des tests, il faudra mettre à jour les différents pack de langues
     * du dossier ressource "Core/imported"
     *
     * @author Neoblaster
     */
    public function testImport()
    {
        // "Charger" les ressources sources.
        self::recursiveCopy(
            self::$testResPath . '/import/src/*',
            self::$testResPath . '/import/default/imports/'
        );
        self::recursiveCopy(
            self::$testResPath . '/import/src/*',
            self::$testResPath . '/import/finalize/imports/'
        );
        self::recursiveCopy(
            self::$testResPath . '/import/src/*',
            self::$testResPath . '/import/preserve/imports/'
        );



        // Test d'importation par défaut.
        self::$compiler->setImportDirectory('/' . self::$testResPath . '/import/default/imports', false);
        self::$compiler->import();
        // Le mode par défaut ne finalize pas, donc ne détruit rien.
        $this->assertEquals(
            ["de-DE.ini", "en-EN.ini", "fr-FR.ini"],
            self::listFolderFile(self::$testResPath . '/import/default/imports')
        );
        /**
         * Décommenter pour effectuer une mise à jour des ressources.
         */
//        self::recursiveCopy(
//            self::$testWorkingDir . '/fr-FR',
//            self::$testResPath . '/import/default/results'
//        );
//        self::recursiveCopy(
//            self::$testWorkingDir . '/en-EN',
//            self::$testResPath . '/import/default/results'
//        );
//        self::recursiveCopy(
//            self::$testWorkingDir . '/de-DE',
//            self::$testResPath . '/import/default/results'
//        );



        // Test d'importation avec finalisation.
        self::$compiler->setImportDirectory('/' . self::$testResPath . '/import/finalize/imports', false);
        self::$compiler->import(true);
        // Sans preservation, les fichiers sont supprimés.
        $this->assertEquals(
            [],
            self::listFolderFile(self::$testResPath . '/import/finalize/imports')
        );
        /**
         * Décommenter pour effectuer une mise à jour des ressources.
         */
//        self::recursiveCopy(
//            self::$testWorkingDir . '/fr-FR',
//            self::$testResPath . '/import/finalize/results'
//        );
//        self::recursiveCopy(
//            self::$testWorkingDir . '/en-EN',
//            self::$testResPath . '/import/finalize/results'
//        );
//        self::recursiveCopy(
//            self::$testWorkingDir . '/de-DE',
//            self::$testResPath . '/import/finalize/results'
//        );
//        self::recursiveCopy(
//            self::$testWorkingDir . '/fr-FR',
//            self::$testResPath . '/import/preserve/results'
//        );
//        self::recursiveCopy(
//            self::$testWorkingDir . '/en-EN',
//            self::$testResPath . '/import/preserve/results'
//        );
//        self::recursiveCopy(
//            self::$testWorkingDir . '/de-DE',
//            self::$testResPath . '/import/preserve/results'
//        );



        // Test d'importation avec finalisation.
        self::$compiler->setImportDirectory('/' . self::$testResPath . '/import/preserve/imports', false);
        self::$compiler->import(true, true);
        // La préservation doit bien avoir eu lieu.
        $this->assertEquals(
            ["de-DE.ini", "en-EN.ini", "fr-FR.ini"],
            self::listFolderFile(self::$testResPath . '/import/preserve/imports')
        );
    }

    /**
     * Met à jour les fichiers de langue à l'aide des fichier ini.
     *
     * @author Neoblaster
     */
    public function testImportExceptionFolderNotExist()
    {
        self::$compiler->setImportDirectory("import");

        $this->expectException("Exception");
        $this->expectExceptionMessage(
            sprintf("Import folder '%s' does not exist.", self::$testWorkingDir . '/import')
        );

        self::$compiler->import();
    }

    /**
     * Met à jour les fichiers de langue à l'aide des fichier ini.
     *
     * @author Neoblaster
     */
    public function testImportExceptionInvalidFile()
    {
        self::$compiler->setImportDirectory('/' . self::$testResPath . '/import/invalid/imports', false);

        $this->expectException("Exception");
        $this->expectExceptionMessage(
            sprintf("The file '%s' can not be imported.", 'invalid.ini')
        );

        self::$compiler->import();
    }


    /**
     * Test avancées simulant des manipulations humaine sur le fichier languages.xml
     */

    /**
     * Test d'instanciation dans une installation existante où il n'y à pas de valeur
     * pour la langue par défaut
     * @author Neoblaster
     */
    public function testInstanciationOnInstalledDirWithNoDefaultLang()
    {
        self::simulateExistingInstallation();

        // Supprimer la valeur par défaut
        $sxe = new \SimpleXMLElement(file_get_contents(
            self::$testWorkingDir . '/Install/' . Core::XML_CONFIG_FILE
        ));

        $sxe->attributes()->default = null;

        Core::saveXml($sxe, self::$testWorkingDir . '/Install/' . Core::XML_CONFIG_FILE);

        $this->expectException("Exception");
        $this->expectExceptionMessage("The default language '' is not registred.'fr-FR' use instead.");
        $compiler = new Core(self::$testWorkingDir . '/Install');
    }

    /**
     * Test d'instanciation dans une installation existante où l'attribut default n'existe pas
     * et va définir en-En comme langue par défaut puisque définie.
     * @author Neoblaster
     */
    public function testInstanciationOnInstalledDirWithNoDefaultLangAttribut_enEN()
    {
        self::simulateExistingInstallation();

        // Supprimer la valeur par défaut
        $sxe = new \SimpleXMLElement(file_get_contents(
            self::$testWorkingDir . '/Install/' . Core::XML_CONFIG_FILE
        ));

        unset($sxe->attributes()->default);

        Core::saveXml($sxe, self::$testWorkingDir . '/Install/' . Core::XML_CONFIG_FILE);

        $compiler = new Core(self::$testWorkingDir . '/Install');
    }

    /**
     * Test d'instanciation dans une installation existante où l'attribut default n'existe pas
     * et va définir une autre langue que en-EN car celle-ci n'existe pas.
     * @author Neoblaster
     */
    public function testInstanciationOnInstalledDirWithNoDefaultLangAttribut_frFR()
    {
        $maker = self::simulateExistingInstallation();
        $maker->removeLanguages(true, 'en-EN');
        $maker = null;

        // Supprimer la valeur par défaut
        $sxe = new \SimpleXMLElement(file_get_contents(
            self::$testWorkingDir . '/Install/' . Core::XML_CONFIG_FILE
        ));

        unset($sxe->attributes()->default);

        Core::saveXml($sxe, self::$testWorkingDir . '/Install/' . Core::XML_CONFIG_FILE);

        $compiler = new Core(self::$testWorkingDir . '/Install');
    }

    /**
     * Vérifie que le programme ne traite pas de ligne 'language' lorsque celle-ci ne dispose
     * pas de l'attribut LANG
     * @author Neoblaster
     */
    public function testListRegLanguageWithLanguageHaveMissingAttributLANG()
    {
        self::simulateExistingInstallation();

        // Supprimer la valeur par défaut
        $sxe = new \SimpleXMLElement(file_get_contents(
            self::$testWorkingDir . '/Install/' . Core::XML_CONFIG_FILE
        ));

        unset($sxe->language[0]->attributes()->LANG);

        Core::saveXml($sxe, self::$testWorkingDir . '/Install/' . Core::XML_CONFIG_FILE);

        $this->expectException("Exception");
        $this->expectExceptionMessage('Key "LANG" is missing for "Français". This language is skipped.');
        $compiler = new Core(self::$testWorkingDir . '/Install');
    }

    /**
     * Contrôle de la bonne création récursive de l'arborescence d'installation
     * @author Neoblaster
     */
    public function testInstallInUnexistingSubDir()
    {
        $compilator = new Core(self::$testWorkingDir . '/auto-created');
        $compilator->install();
    }

    /**
     * Vérifie que la méthode de récupération de la langue par défaut échoue lorsqu'une configuration
     * manuelle a eu lieu et que la langue définie n'existe pas (Erreur).
     */
    public function testGetDefaultLanguageWithManualEditFile()
    {
        //@TODO : faire le tests testGetDefaultLanguageWithManualEditFile
    }

    /**
     * Destruction de l'instance Core
     * @author Neoblaster
     */
    public function testEndByDestruct()
    {
        self::$compiler = null;
    }



    /**
     * Execution à la fin des tests
     */
    static function tearDownAfterClass()
    {
        //echo PHP_EOL . file_get_contents(self::$testWorkingDir . '/' . Core::XML_CONFIG_FILE);
    }

}
