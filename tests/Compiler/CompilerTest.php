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
    public function badLanguageCodes()
    {
        return [
            /** Donnée déja enregistée  */
            ['fr-FR:Français'],
            /** Code incorrecte */
            ['fra-FRA']
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
     * @author Neoblaster
     */
    public function testAddLanguage()
    {
        $this->assertEquals(true, self::$compiler->addLanguage('fr-FR:Français'));
        $this->assertEquals(true, self::$compiler->addLanguage('en-EN:English'));
        $this->assertEquals(true, self::$compiler->addLanguage('de-DE:Deutch', 'it-IT:Italian'));
    }

    /**
     * @author Neoblaster
     * @dataProvider badLanguageCodes
     * @expectedException \Exception
     */
    public function testAddLanguageExceptions($langCode)
    {
        self::$compiler->addLanguage($langCode);
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



    public function testInstallInSubDir()
    {
        $compilator = new Compiler(self::$testWorkingDir . '/auto-created');
        $compilator->install();
    }

    public function testDestruct()
    {
        self::$compiler = null;
    }
}
