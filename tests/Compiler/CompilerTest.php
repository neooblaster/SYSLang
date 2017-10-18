<?php
/**
 * Created by PhpStorm.
 * User: Neoblaster
 * Date: 03/10/2017
 * Time: 15:29
 */

use SYSLang\Compiler;

require_once "src/SYSLang/Compiler.php";


class CompilerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Compiler $compiler Instance pour les tests
     */
    protected static $compiler = null;

    /**
     * @var array $cleanseExcluded Fichier à ne pas supprimer lors du nettoyage.
     */
    protected static $cleanseExcluded = ['.', '..', 3 => 'CompilerWithManualInstallTest.php', 'Install'];

    /**
     * @var string $testWorkingDir Emplacement de travail pour dérouler les tests
     */
    protected static $testWorkingDir = 'tests/Compiler';



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
            ['Argument supplied "fra-FRA" is not valide. It must be like this xx-XX. Argument "fra-FRA" is skipped.', true, 'fra-FRA']
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
     * Nettoie le dossier spécifié de manière recursive.
     * @param string $path Chemin vers le dossier à parcourir et nettoyer.
     */
    protected static function cleanseDir($path)
    {
        $dir = scandir($path);

        foreach ($dir as $key => $file) {
            if (!in_array($file, self::$cleanseExcluded)) {
                $full_path = $path . '/' . $file;
                if (is_dir($full_path)) {
                    self::cleanseDir($full_path);
                    rmdir($full_path);
                } else {
                    unlink($full_path);
                }
            }
        }
    }

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


        self::cleanseDir(self::$testWorkingDir);
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
        self::$compiler = new Compiler(self::$testWorkingDir);

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
            in_array(Compiler::XML_CONFIG_FILE, scandir(self::$testWorkingDir))
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
    public function testAddLanguage()
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
     * Contrôle du comportement du compiler sur un dossier déjà configuré.
     * @author Neoblaster
     */
    public function testInstanciationOnInstalledDir()
    {
        $compiler = new Compiler(self::$testWorkingDir);
        $this->assertEquals(true, $compiler->isInstalled());
    }

    /**
     * Contrôle de la bonne création récursive de l'arborescence d'installation
     * @author Neoblaster
     */
    public function testInstallInUnexistingSubDir()
    {
        $compilator = new Compiler(self::$testWorkingDir . '/auto-created');
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
     * Destruction de l'instance Compiler
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
        //echo PHP_EOL . file_get_contents(self::$testWorkingDir . '/' . Compiler::XML_CONFIG_FILE);
    }

}
