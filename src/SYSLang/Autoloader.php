<?php
/**
 * File :: Autoloader.php
 *
 * Système d'auto-chargement du moteur SYSLang.
 *
 * @author    marvin255, Edit Nicolas DUPRE
 * @release   23/10/2017
 * @version   2.0.0-beta1
 * @package   Index
 *
 * @TODO : [P1] Reconstituer le système d'Import / Export.
 * @TODO : [P2] Créer un système d'alias pour les langues proche : en-US pointe vers en-EN.
 */

namespace SYSLang;

/**
 * Autoloader class.
 */
class Autoloader
{
    /**
     * @param string $path Dossier de travail.
     */
    protected static $path = null;

    /**
     * Initialise l'emplacement et enregistre la méthode statique load dans le registre SPL.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function register($path = null)
    {
        // Si $path est défini, l'utiliser, sinon utiliser l'emplacement du présent script.
        self::$path = $path ? $path : dirname(__FILE__);

        return spl_autoload_register(array(__CLASS__, 'load'), true, true);
    }

    /**
     * Cherche et charge la classe demandée.
     *
     * @param string $class Nom de la classe demandée.
     */
    public static function load($class)
    {
        // Controler que la fonction sert son espace de nom.
        $prefix = __NAMESPACE__ . '\\';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        // Chargement de la classe demandée.
        $relative_class = substr($class, $len);
        $file = self::$path . '/' . str_replace('\\', '/', $relative_class).'.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

Autoloader::register(dirname(__FILE__));
