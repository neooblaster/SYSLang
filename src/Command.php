#!/usr/bin/env php
<?php
/**
 * File :: Command.php
 *
 * @author    Nicolas DUPRE
 * @release   18/10/2017
 * @version   1.0.0-beta1
 * @package   CLI
 */

use SYSLang\Compiler;
use SYSLang\SYSLang;

require_once __DIR__ . "/SYSLang/Compiler.php";
require_once __DIR__ . "/SYSLang/SYSLang.php";


/**
 * Initialisation des variables
 *
 * @var string  $color_err   Code couleur pour les erreurs
 * @var string  $color_in    Code couleur pour les donnée saisie par l'utilisateur
 * @var string  $color_suc   Code couleur pour les success
 * @var string  $color_war   Code couleur pour les warnings
 * @var array   $longopt     Modèle des options version long (--password=Password)
 * @var array   $options     Argument parsé
 * @var string  $workdir     Dossier d'execution courant
 * @var string  $shortopt    Modèle des options admis (-pPassword)
 */
// Couleurs
$color_err = "196";
$color_in  = "220";
$color_suc = "76";
$color_war = "208";
$color_txt = "221";

// Options
$options = null;
$shortopt = "h";
$longopt = Array(
    "add-languages:",
    "default",
    "directory:",
    "dir:",
    "help",
    "install",
    "remove-languages:",
    "remove-langs:",
    "set-default-lang:",
    "silent"
);

$optionSeparator = ',';

// Emplacement
$workdir = $_SERVER["PWD"];



/**
 * Affiche le manuel d'aide
 *
 * @eturn void
 */
function help($level = 0){
    global $optionSeparator;

    $name = basename($_SERVER['SCRIPT_NAME']);

    echo <<<HELP
    
Usage : $name [OPTIONS]

Permet la maintenance de l'instalaltion SYSLang en ligne de commande.


0. Options transverses :
                            
 --dir, --directory     Spécifie l'emplacement de travail.
    -h, --help          Affiche la présente aide.
        --silent        Masque les messages d'informations.

1. Options d'installation :

        --install       Installe le fichier de configuration languages.xml
                        dans le dossier de travail défini.
                        Defaut : ./
   
2. Options de configurations :

        --add-languages     Ajoute la/les langue(s) spécifiée(s) au registre.
                            Format : xx-XX:Name
                            Séparateur : virgule ($optionSeparator)
        
        --default           Fait en sorte que la langue en cours d'ajout
                            devienne également la langue par defaut.
                            Si plusieurs valeur, alors c'est la première qui est
                            retenue.
                            
        --remove-languages  Supprime la/les langue(s) spécifiée(s) du registre 
        --remove-langs      et supprime les fichiers associé
                            Format : xx-XX
                            Séparateur : virgule ($optionSeparator)
                            
        --preserve-files    Demande la concervation des fichiers lors d'une
                            suppresion de langue.

        --set-default-lang  Définit la langue par défaut.
HELP;
    echo PHP_EOL;
    die($level);
}

/**
 * Met en évidence les valeurs utilisateur dans les messages
 *
 * @param  string $message   Message à analyser
 *
 * @return string $message   Message traité
 */
function highlight($message){
    global $color_in;

    // A tous ceux qui n'ont pas de couleur spécifiée, alors saisir la couleur par défaut
    $message = preg_replace("/(?<!>)(%[a-zA-Z0-9])/", "$color_in>$1", $message);

    // Remplacer par le code de colorisation Shell
    $message = preg_replace("#([0-9]+)>(%[a-zA-Z0-9])#", "\e[38;5;$1m$2\e[0m", $message);

    return $message;
}

/**
 * Emet des messages dans le flux STDERR de niveau WARNING ou ERROR
 *
 * @param string $message Message à afficher dans le STDERR
 * @param array  $args    Elements à introduire dans le message
 * @param int    $level   Niveau d'alerte : 0 = warning, 1 = error
 */
function stderr($message, $args, $level = 1){
    // Connexion aux variables globales
    global $color_err, $color_war;

    // Traitement en fonction du niveau d'erreur
    $level_str = ($level) ? "ERROR" : "WARNING";
    $color = ($level) ? $color_err : $color_war;

    // Mise en evidence des saisie utilisateur
    $message = highlight($message);
    $message = "[ \e[38;5;{$color}m$level_str\e[0m ] :: $message".PHP_EOL;

    fwrite(STDERR, vsprintf($message, $args));
    if($level) die($level);
}

/**
 * Emet des messages dans le flux classique STDOUT
 *
 * @param string $message Message à afficher dans le STDOUT
 * @param array  $arg     Elements à introduire dans le message
 */
function stdout($message, $args){
    global $options;

    if(!isset($options["silent"])){
        $message = highlight($message);
        $message = "[ INFO ] :: $message".PHP_EOL;
        fwrite(STDOUT, vsprintf($message, $args));
    }
}


/**
 * Récupération des arguments
 */
$options = getopt($shortopt, $longopt);
//print_r($options);


/**
 * Executions du script
 */
// Création du compilateur
$directory = @($options["dir"]) ?: (@$options["directory"]) ?: ".";
$compiler = new Compiler($directory);


// Afficher l'aide si demandé et s'arrêté la
if (isset($options["h"]) || isset($options["help"])) help();


// Processus d'installation
if (isset($options["install"])) {
    $compiler->install();

    stdout('Installation effectuée avec succès dans %s', [__DIR__ . '/' . $directory]);
}


// Processus d'enregistrement d'une langue au registre
if (isset($options["add-languages"])) {
    $languages = explode($optionSeparator, $options["add-languages"]);
    $languages = array_map(function($el){
        return trim($el);
    }, $languages);

    try {
        call_user_func_array(array($compiler, 'addLanguages'), $languages);

        // Notification après coup
        array_map(function($el){
            stdout("Enregistrement de langue %s effectué avec succès.", [$el]);
        }, $languages);

        if (isset($options["default"])) {
            $compiler->setDefaultLanguage($languages[0]);
            stdout("La langue par défaut est définie à %s.", [$languages[0]]);
        }
    } catch (\Exception $e) {
        stderr($e->getMessage(), []);
    }
}


// Processus de suppression de langue du registre
if (isset($options["remove-languages"]) || isset($options["remove-langs"])) {
    // Options à valeur obligatoire, null ne doit jamais se produire.
    $languages = @($options["remove-languages"]) ?: @($options["remove-langs"]) ?: null;
    $languages = explode($optionSeparator, $languages);
    $languages = array_map(function($el) {
        return trim($el);
    }, $languages);

    try {
        call_user_func_array([$compiler, "removeLanguages"], array_merge([true], $languages));

        // Notification après coup
        array_map(function($el){
            stdout("Suppression de langue %s effectué avec succès.", [$el]);
        }, $languages);
    } catch (\Exception $e) {
        stderr($e->getMessage(), []);
    }
}


// Configuration du pack de langue par défaut
if (isset($options["set-default-lang"])) {
    try {
        $compiler->setDefaultLanguage($options["set-default-lang"]);
        stdout("La langue par défaut est définie à %s.", [$options["set-default-lang"]]);
    } catch (Exception $e) {
        stderr($e->getMessage(), []);
    }
}
