<?php
/**
 * Created by PhpStorm.
 * User: Neoblaster
 * Date: 23/10/2017
 * Time: 20:56
 */

//require_once "src/SYSLang/Autoloader.php";

class CommandTest extends \PHPUnit_Framework_TestCase
{
    use initializer;

    /**
     * @var string $testWorkingDir Dossier de travail pour l'exécution des tests.
     */
    protected static $testWorkingDir = "tests/Command";

    /**
     * @var string $testResPath Emplacement où se trouve les ressources utile pour les tests.
     */
    protected static $testResPath = "tests/resources";

    /**
     * @var array $cleanseExcluded Fichier et Dossier à exclure du nettoyage.
     */
    protected static $cleanseExcluded = [".", "..", "makeCLItxt"];



    /**
     * Initialisation de la batterie de tests (Execution une seule fois).
     */
    static function setUpBeforeClass()
    {
        /** Nettoyage avant exécution des tests */
        self::$cleanseExcluded[] = basename(__FILE__);

        self::cleanseDir(self::$testWorkingDir, self::$cleanseExcluded);
    }

    /**
     * DataProvider pour automatiser les tests sur l'ensemble des commandes.
     *
     * @return array
     */
    public function commands()
    {
        /**
         * Test à lire de manière consécutive et dépendante l'une de l'autre.
         */
        return [
            // 1. Affichage de l'aide.
            ["1.1.", ["h" => null], file_get_contents(self::$testResPath . "/cli/help.txt"), false, null, null],
            ["1.2.", ["help" => null], file_get_contents(self::$testResPath . "/cli/help.txt"), false, null, null],

            // 2. Installations
            ["2.1.", ["install" => null], file_get_contents(self::$testResPath . "/cli/install.txt"), false, null, null],
            ["2.2.", ["install" => null], file_get_contents(self::$testResPath . "/cli/installed.txt"), false, null, null],

            // 3. Ajouter des langues
            [
                "3.1.", ["add-languages" => "fr-FR:Français"],
                file_get_contents(self::$testResPath . "/cli/add-lang-frFR.txt"), false, null, null
            ],
            [
                "3.2.", ["add-languages" => "en-EN:English", "default" => null],
                file_get_contents(self::$testResPath . "/cli/add-lang-enEN.txt"), false, null, null
            ],
            [
                "3.3.", ["add-languages" => "Japonais"],
                file_get_contents(self::$testResPath . "/cli/add-lang-Japonais.txt"), false, null, null
            ],

            // 4. Définir une langue par défault
            [
                "4.1.", ["set-default-lang" => "fr-FR"],
                file_get_contents(self::$testResPath . "/cli/set-def-lang-frFR.txt"), false, null, null
            ],
            [
                "4.2.", ["set-default-lang" => "jp-JP"],
                file_get_contents(self::$testResPath . "/cli/set-def-lang-jpJP.txt"), false, null, null
            ],

            // 5. Supprimer une langue
            [
                "5.1.", ["remove-languages" => "en-EN"],
                file_get_contents(self::$testResPath . "/cli/rem-lang-enEN.txt"), false, null, null
            ],
            [
                "5.2.", ["remove-langs" => "Japonais"],
                file_get_contents(self::$testResPath . "/cli/rem-lang-Japonais.txt"), false, null, null
            ],

            // 6. Test du déploiement après ré-enregistrement de l'anglais.
            [
                "6.1.", ["add-languages" => "en-EN:English", "default" => null],
                file_get_contents(self::$testResPath . "/cli/add-lang-enEN.txt"), false, null, null
            ],
            [
                "6.2.", ["set-default-lang" => "fr-FR"],
                file_get_contents(self::$testResPath . "/cli/set-def-lang-frFR.txt"), false, null, null
            ],
            [
                "6.3.", ["deploy" => null],
                file_get_contents(self::$testResPath . "/cli/deploy-from-def-fr.txt"), false, null, null
            ],
            [
                "6.4.", ["deploy" => null, "from" => "en-EN"],
                file_get_contents(self::$testResPath . "/cli/deploy-from-en.txt"), false, null, null
            ],
            [
                "6.5.", ["deploy" => null, "from" => "xx-XX"],
                file_get_contents(self::$testResPath . "/cli/deploy-from-xx.txt"), false, null, null
            ],
            [
                "6.6.", ["deploy" => null, "from" => "invalid"],
                file_get_contents(self::$testResPath . "/cli/deploy-from-invalid.txt"), false, null, null
            ],

            // 7. Exportation
            [
                "7.1.", ["export" => null],
                file_get_contents(self::$testResPath . "/cli/export.txt"), false, null, null
            ],
            [
                "7.2.", ["export" => null, "complete" => null],
                file_get_contents(self::$testResPath . "/cli/export-complete.txt"), false, null, null
            ],

            // 8. Importation
            [
                "8.1.", ["import" => null],
                file_get_contents(self::$testResPath . "/cli/import.txt"), false, null, null
            ],
            [
                "8.2.", ["import" => null, "import-dir" => "exports"],
                file_get_contents(self::$testResPath . "/cli/import-import-dir.txt"), false, null, null
            ],
            [
                "8.3.", ["import" => null, "import-dir" => "exports", "finalize" => null],
                file_get_contents(self::$testResPath . "/cli/import-import-dir-finalize.txt"), false, null, null
            ],
            [
                "8.4.", ["import" => null, "import-dir" => "exports", "finalize" => null, "preserve-files" => null],
                file_get_contents(self::$testResPath . "/cli/import-import-dir-finalize-preserve.txt"), false, null, null
            ],
        ];
    }

    /**
     * Joue l'ensemble des commandes founir par le dataProvider commands.
     *
     * @dataProvider commands
     *
     * @param $options
     * @param $outputMessage
     * @param $expectException
     * @param $exception
     * @param $exceptionMessge
     *
     * @author Neoblaster
     */
    public function testCommands(
        $dataSetId,
        $options,
        $outputMessage,
        $expectException,
        $exception,
        $exceptionMessge
    ){
        // Configuration du tests
        if ($expectException) {
            $this->expectException($exception);
            $this->expectExceptionMessage($exceptionMessge);
        }

        $this->expectOutputString($outputMessage);

        // Création de l'interface
        $cli = new \SYSLang\Command(self::$testWorkingDir, $options, "SYSLang");

        // Modifier les fluxs
        $stream = fopen("php://output", "w");

        $cli->setStdout($stream);
        $cli->setStderr($stream);
        $cli->setNoDie(true);

        // Executions
        $cli->run();
    }

    /**
     * Test l'exception lorsque l'emplacement donnée est vide.
     *
     * @author Neoblaster
     */
    public function testCommandNoPath()
    {
        $this->expectException("InvalidArgumentException");
        $this->expectExceptionMessage("workdir parameter in constructor can't be empty.");

        // Création de l'interface
        $cli = new \SYSLang\Command("", [], "SYSLang");
    }

    /**
     * Test l'exception lorsque l'emplacement donnée n'existe pas.
     *
     * @author Neoblaster
     */
    public function testCommandWrongPath()
    {
        $workdir = "abc";

        $this->expectException("InvalidArgumentException");
        $this->expectExceptionMessage("workdir `{$workdir}` doesn't exist.");

        // Création de l'interface
        $cli = new \SYSLang\Command($workdir, [], "SYSLang");
    }

}
