<?php
/**
 * AutoloadTest.php
 *
 * @author    Nicolas DUPRE
 * @release   19/10/2017
 * @version   1.0.0
 * @package   Index
 */

use SYSLang\Autoloader;

require_once "src/SYSLang/Autoloader.php";

class AutoloadTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test le bon fonctionnement de l'enregistrement SPL.
     */
    public function testAutoloaderRegister()
    {
        Autoloader::register();
    }

    /**
     * Test le chargement d'une classe existante dans le bonne espace de nom.
     */
    public function testAutoloadLoadOK()
    {
        Autoloader::load("SYSLang\Compiler");
    }

    /**
     * Test le chargement d'une classe inexistante.
     */
    public function testAutoloadLoadKO()
    {
        Autoloader::load("Compiler");
    }
}
