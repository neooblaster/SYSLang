<?php
/**
 * Created by PhpStorm.
 * User: ndu90045
 * Date: 15/10/2017
 * Time: 19:37
 */

use SYSLang\SYSLang;

require_once "src/SYSLang/Autoloader.php";

class SYSLangTest extends \PHPUnit_Framework_TestCase
{
    use initializer;

    /**
     * @var SYSLang $lang Instance de la classe SYSLang, interface d'utilisation PHP.
     */
    protected static $lang = null;

    /**
     * @var array $cleanseExcluded Fichier à ne pas supprimer lors du nettoyage.
     */
    protected static $cleanseExcluded = ['.', '..'];

    /**
     * @var string $testWorkingDir Emplacement de travail pour dérouler les tests
     */
    protected static $testWorkingDir = 'tests/SYSLang';



    /**
     * Les dataProviders
     */

    /**
     * Setups et Méthods interne
     */

    /**
     * Initialisation de la batterie de tests (Execution une seule fois).
     */
    static function setUpBeforeClass()
    {
        /** S'auto-exclure */
        self::$cleanseExcluded[] = basename(__FILE__);

        /**
         * Live test ici :
         */

        /** Nettoyage avant exécution des tests */
        self::cleanseDir(self::$testWorkingDir, self::$cleanseExcluded);

        /** Récupération de l'installation issue des tests du noyaux */
        self::recursiveCopy('tests/Compiler/languages.xml', self::$testWorkingDir);
        self::recursiveCopy('tests/Compiler/fr-FR', self::$testWorkingDir);
        self::recursiveCopy('tests/resources/*', self::$testWorkingDir . '/fr-FR');
    }



    /**
     * Batterie de tests
     */

    /**
     * Création du moteur de langue.
     * @author Neoblaster
     */
    public function testInstanciation()
    {
        self::$lang = new SYSLang(self::$testWorkingDir);
        $this->assertEquals("en-EN", self::$lang->getLanguage());
    }

    /**
     * Instanciation avec définition de la langue.
     * @author Neoblaster
     */
    public function testInstanciationWithLang()
    {
        // Langue enregistrée, elle doit-être définie à l'instanciation.
        self::$lang = new SYSLang(self::$testWorkingDir, 'fr-FR');
        $this->assertEquals("fr-FR", self::$lang->getLanguage());

        // Langue valide mais inconnue, c'est la langue par défaut qui doit être utilisée.
        self::$lang = new SYSLang(self::$testWorkingDir, 'xx-XX');
        $this->assertEquals("en-EN", self::$lang->getLanguage());
    }

    /**
     * Controle le bon comportement sous navigateur avec HTTP_ACCEPT_LANGUAGE simulé.
     *
     * @author Neoblaster
     */
    public function testsInstanciationWithSimulatedHTTP_ACCEPT_LANGUAGE()
    {
        // Doit prendre fr-FR
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5';
        self::$lang = new SYSLang(self::$testWorkingDir);
        $this->assertEquals('fr-FR', self::$lang->getLanguage());

        // Doit prendre fr;q=0.9 soit fr-FR
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'xx-XX, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5';
        self::$lang = new SYSLang(self::$testWorkingDir);
        $this->assertEquals('fr-FR', self::$lang->getLanguage());

        // Doit prendre en;q=0.8 soit en-EN
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'xx-XX, xx;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5';
        self::$lang = new SYSLang(self::$testWorkingDir);
        $this->assertEquals('en-EN', self::$lang->getLanguage());

        // Doit prendre  de;q=0.7 soit de-DE
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'xx-XX, xx;q=0.9, xx;q=0.8, de;q=0.7, *;q=0.5';
        self::$lang = new SYSLang(self::$testWorkingDir);
        $this->assertEquals('de-DE', self::$lang->getLanguage());

        // Doit prendre la valeur définie par défaut
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'xx-XX, xx;q=0.9, xx;q=0.8, xx;q=0.7, *;q=0.5';
        self::$lang = new SYSLang(self::$testWorkingDir);
        $this->assertEquals('en-EN', self::$lang->getLanguage());
    }

    /**
     * Vérifie le bon fonctionne de la selection de la langue.
     * @author Neoblaster
     */
    public function testSetLanguage()
    {
        // Langue enregistée.
        $this->assertEquals(true, self::$lang->setLanguage('fr-FR'));
        $this->assertEquals('fr-FR', self::$lang->getLanguage());

        // Langue inconnue.
        $this->assertEquals(true, self::$lang->setLanguage('xx-XX'));
        $this->assertEquals('en-EN', self::$lang->getLanguage());

        // Langue par défaut
        $this->assertEquals(true, self::$lang->setLanguage());
        $this->assertEquals('en-EN', self::$lang->getLanguage());
    }

    /**
     * Récupération des texts.
     * @author Neoblaster
     */
    public function testGetTexts()
    {
        // Pour s'assurer de la bonne execution du test :
        self::$lang->setLanguage('fr-FR');

        // Récupérer tous les textes
        $this->assertEquals([
            "SERVER" => [
                "your_key_name_here" => "your_coresponding_text_here"
            ],
            "CLIENT" => [
                [
                    "VAR_KEY" => "cst_key_name",
                    "VAR_VALUE" => "your_coresponding_text_here"
                ]
            ]
        ], self::$lang->getTexts());

        // Récupérer les textes du fichier spécifié
        $this->assertEquals([
            "SERVER" => [
                "your_key_name_here" => "your_coresponding_text_here"
            ]
        ], self::$lang->getTexts('generic.xml'));

        // Fichier inconnu
        $this->expectException("\Exception");
        self::$lang->getTexts('unknow.xml');
    }

    /**
     * Destruction de l'instance
     * @author Neoblaster
     */
    public function testEndByDestruct()
    {
        self::$lang = null;
    }



    /**
     * Execution à la fin des tests
     */
    static function tearDownAfterClass()
    {
        //echo PHP_EOL . file_get_contents(self::$testWorkingDir . '/' . Compiler::XML_CONFIG_FILE);
    }

}
