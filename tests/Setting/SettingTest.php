<?php
/**
 * Created by PhpStorm.
 * User: Neoblaster
 * Date: 04/11/2017
 * Time: 09:14
 */

use SYSLang\Setting;

class SettingTest extends \PHPUnit_Framework_TestCase
{
    use initializer;

    /**
     * @var string $testWorkingDir Dossier de travail pour l'exécution des tests.
     */
    protected static $testWorkingDir = "tests/Setting";

    /**
     * @var string $testResPath Emplacement où se trouve les ressources utile pour les tests.
     */
    protected static $testResPath = "tests/resources";



    /**
     * Initialisation de la batterie de tests (Execution une seule fois).
     */
    static function setUpBeforeClass()
    {

    }



    /**
     * Jeu de donnée pour le test testCheckCode
     * @return array
     */
    public function providerCheckScope()
    {
        return [
            /** Scope donnée --- Niveau attendu --- Reussite */

            # Scope Global
            [null, Setting::SCOPE_LEVEL_ALL, true],

            # Scope Language
            ["fr-FR", Setting::SCOPE_LEVEL_LANG, true],
            ["fra-FRA", Setting::SCOPE_LEVEL_LANG, false],

            # Scope File
            ["generic.xml", Setting::SCOPE_LEVEL_FILE, true],
            ["generic.XML", Setting::SCOPE_LEVEL_FILE, true],
            ["index.master.xml", Setting::SCOPE_LEVEL_FILE, true],
            ["index.master.csv", Setting::SCOPE_LEVEL_FILE, false]
        ];
    }

    public function providerAddLangs()
    {
        /** $scope, $rule, $expected, $exception, $exceptionMsg */
        return [
            [null, "OUI", [], null],
            ["fr-FR", "NON", [], "Exception"],
            ["x.xml", "NON", [], "Exception"],
            ["key", "KO", [], "Exception"],
        ];
    }

    public function providerAddFiles()
    {
        /** $scope, $rule, $expected */
        return [
            [null, "OUI", [], null],
            ["fr-FR", "OUI", [], null],
            ["x.xml", "NON", [],"Exception"],
            ["key", "KO", [],"Exception"],
        ];
    }

    public function providerAddKeys()
    {
        /** $scope, $rule, $expected */
        return [
            [null, "OUI", [], null],
            ["fr-FR", "OUI", [], null],
            ["x.xml", "OUI", [], null],
            ["key", "KO", [], "Exception"],
        ];
    }


    /**
     * Contrôle le bon fonctionnement de la méthode protected commute
     * @author Neoblaster
     */
    public function testCommute()
    {
        # Création d'une instance
        $setting = new Setting();

        # Reflection de la classe
        $rsetting = new ReflectionObject($setting);
        # Propritété Setting (protected)
        $processing = $rsetting->getProperty("processing");
        $processing->setAccessible(true);
        # Méthode commute (protected)
        $commute = $rsetting->getMethod("commute");
        $commute->setAccessible(true);

        # Tableau de comparaison de base
        $elements = $processing->getValue($setting);

        # Test "Activation" du mode ->add()->
        $commute->invokeArgs($setting, ["adding"]);
        $elements["adding"] = true;
        $this->assertEquals($elements, $processing->getValue($setting));

        # Test "Commuting" passagade du mode add() à del()
        $commute->invokeArgs($setting, ["deleting"]);
        $elements["adding"] = false;
        $elements["deleting"] = true;
        $this->assertEquals($elements, $processing->getValue($setting));

        # Test "Désengagement" de mode
        $commute->invokeArgs($setting, [null]);
        $elements["deleting"] = false;
        $this->assertEquals($elements, $processing->getValue($setting));
    }

    /**
     * Jeu de donnée pour le test testCheckCode
     * @dataProvider providerCheckScope
     * @author Neoblaster
     *
     * @param string $scope Chaine définissant le scope à tester.
     *
     * @param $return
     */
    public function testCheckAndStoreScope($scope, $type, $return)
    {
        # Création d'une instance
        $setting = new Setting();

        # Reflection de la classe via l'objet
        $rsetting = new ReflectionObject($setting);
        $checkScope = $rsetting->getMethod("checkScope");
        $checkScope->setAccessible(true);
        $storeScope = $rsetting->getMethod("storeScope");
        $storeScope->setAccessible(true);
        $scoping = $rsetting->getProperty("scoping");
        $scoping->setAccessible(true);

        $this->assertEquals($return, $checkScope->invokeArgs($setting, [$scope]));
        if ($return) {
            $storeScope->invokeArgs($setting, [$scope]);
            $this->assertEquals([
                "value" => $scope,
                "level" => $type
            ], $scoping->getValue($setting));
        }
    }

    /**
     * @dataProvider providerAddLangs
     * @author Neoblaster
     * @param $scope
     * @param $rule
     * @param $expected
     */
    public function testAddLangs($scope, $rule, $expected, $exception)
    {
        if (!is_null($exception)) {
            $this->expectException($exception);
        }

        $setting = new Setting();
        $setting->add($scope)->langs($rule);

        if (is_null($exception)){
            $this->assertEquals($expected, $setting->get()->all());
        }
    }

    public function testDelLangs()
    {

    }

    /**
     * @dataProvider providerAddFiles
     * @author Neoblaster
     * @param $scope
     * @param $rule
     * @param $expected
     */
    public function testAddFiles($scope, $rule, $expected, $exception)
    {
        if (!is_null($exception)) {
            $this->expectException($exception);
        }

        $setting = new Setting();
        $setting->add($scope)->files($rule);

        if (is_null($exception)){
            $this->assertEquals($expected, $setting->get()->all());
        }
    }

    public function testDelFiles()
    {

    }

    /**
     * @dataProvider providerAddKeys
     * @author Neoblaster
     * @param $scope
     * @param $rule
     * @param $expected
     */
    public function testAddKeys($scope, $rule, $expected, $exception)
    {
        if (!is_null($exception)) {
            $this->expectException($exception);
        }

        $setting = new Setting();
        $setting->add($scope)->keys($rule);

        if (is_null($exception)){
            $this->assertEquals($expected, $setting->get()->all());
        }
    }

    public function testDelKeys()
    {

    }

    /**
     * La méthode all() ne fonctionne pas avec le processeur add().
     * @author Neoblaster
     */
    public function testAllWithWrongProcessAdd()
    {
        # Création d'une instance
        $setting = new Setting();

        $this->expectException("\Exception");
        $this->expectExceptionMessage("all method can only used with processing get().");
        $setting->add()->all();
    }

    /**
     * la méthode all() ne fonctionne pas avec le processeur del().
     * @author Neoblaster
     */
    public function testAllWithWrongProcessDel()
    {
        # Création d'une instance
        $setting = new Setting();

        $this->expectException("\Exception");
        $this->expectExceptionMessage("all method can only used with processing get().");
        $setting->del()->all();
    }

}
