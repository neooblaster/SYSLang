<?php

/**
 * File :: Command.php
 *
 * Processeur CLI pour le moteur SYSLang.
 *
 * @author    Nicolas DUPRE with the contribution of marvin255
 * @release   18/10/2017
 * @version   2.0.0-beta1
 * @package   Index
 *
 * @TODO : [P1] Reconstituer le système d'Import / Export.
 * @TODO : [P2] Créer un système d'alias pour les langues proche : en-US pointe vers en-EN.
 */

namespace SYSLang;

use Exception;
use InvalidArgumentException;

/**
 * Class for cli command.
 */
class Command
{
    /**
     * Liste des différentes options utilisée dans la classe Command.
     */
    const OPTIONS = [
        'colors' => [
            'color_err' => '196',
            'color_in' => '220',
            'color_suc' => '76',
            'color_war' => '208',
            'color_txt' => '221',
        ],
        'separator' => ',',
        'shortopt' => "h",
        "longopt" => [
            "add-languages:",
            "default",
            "deploy",
            "directory:",
            "dir:",
            "from:",
            "help",
            "install",
            "remove-languages:",
            "remove-langs:",
            "set-default-lang:",
            "silent",
        ]
    ];

    /**
     * @var string $workdir Dossier de travail
     */
    protected $workdir = null;

    /**
     * @var string $cmdName Nom de la commande
     */
    protected $cmdName = null;

    /**
     * @var array $argv
     */
    protected $argv = null;

    /**
     * @var bool|resource $psdtout Pointeur vers la ressource de sortie standard.
     */
    protected $psdtout = STDOUT;

    /**
     * @var bool|resource $pstderr Pointeur vers la ressource de sortie des erreurs.
     */
    protected $pstderr = STDERR;

    /**
     * @var bool $noDie Flag pour ne pas jouer les evenements die.
     */
    protected $noDie = false;

    /**
     * Constructor function.
     *
     * @param string $workdir Path to working directory.
     * @param array  $argv    Array of command line arguments.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($workdir, array $argv, $cmdName)
    {
        $workdir = trim($workdir);
        if (empty($workdir)) {
            throw new InvalidArgumentException("workdir parameter in constructor can't be empty.");
        }
        if (!is_dir($workdir)) {
            throw new InvalidArgumentException("workdir `{$workdir}` doesn't exist.");
        }
        $this->workdir = $workdir;
        $this->argv = $argv;
        $this->cmdName = $cmdName;

    }

    /**
     * Execution du script.
     */
    public function run()
    {
        $options = $this->argv;

        // Création du compilateur
        $directory = @($options["dir"]) ?: (@$options["directory"]) ?: $this->workdir;
        $compiler = new Core($directory);

        // Afficher l'aide si demandé et s'arrêté la$
        if (
            array_key_exists("h", $options)
        ||  array_key_exists("help", $options)
        ) $this->help();

        // Processus d'installation
        if (array_key_exists("install", $options)) {
            if (!$compiler->isInstalled()) {
                $compiler->install();
                $this->stdout('Installation effectuée avec succès dans %s', [ $directory]);
            } else {
               $this->stderr("Le système de langue est déjà installé dans %s", [$directory], 0);
            }
        }

        // Processus d'enregistrement d'une langue au registre
        if (array_key_exists("add-languages", $options)) {
            $languages = explode(self::OPTIONS['separator'], $options["add-languages"]);
            $languages = array_map(function($el){
                return trim($el);
            }, $languages);

            try {
                call_user_func_array(array($compiler, 'addLanguages'), $languages);

                // Notification après coup
                array_map(function($el){
                    $this->stdout("Enregistrement de langue %s effectué avec succès.", [$el]);
                }, $languages);

                if (array_key_exists("default", $options)) {
                    list($code, $name) = explode(":", $languages[0]);
                    $compiler->setDefaultLanguage($code);
                    $this->stdout("La langue par défaut est définie à %s.", [$languages[0]]);
                }
            } catch (\Exception $e) {
                $this->stderr($e->getMessage(), []);
            }
        }

        // Processus de suppression de langue du registre
        if (
            array_key_exists("remove-languages", $options)
        ||  array_key_exists("remove-langs", $options)
        ) {
            // Options à valeur obligatoire, null ne doit jamais se produire.
            $languages = @($options["remove-languages"]) ?: @($options["remove-langs"]) ?: null;
            $languages = explode(self::OPTIONS['separator'], $languages);
            $languages = array_map(function($el) {
                return trim($el);
            }, $languages);

            try {
                call_user_func_array([$compiler, "removeLanguages"], array_merge([true], $languages));

                // Notification après coup
                array_map(function($el){
                    $this->stdout("Suppression de langue %s effectué avec succès.", [$el]);
                }, $languages);
            } catch (Exception $e) {
                $this->stderr($e->getMessage(), []);
            }
        }

        // Configuration du pack de langue par défaut
        if (array_key_exists("set-default-lang", $options)) {
            try {
                $compiler->setDefaultLanguage($options["set-default-lang"]);
                $this->stdout("La langue par défaut est définie à %s.", [$options["set-default-lang"]]);
            } catch (Exception $e) {
                $this->stderr($e->getMessage(), []);
            }
        }

        // Processus de déploiement
        if (array_key_exists("deploy", $options)) {
            # Si l'option "from" est fournie, alors définir la langue de référence
            if (array_key_exists("from", $options)) {
                try {
                    $compiler->setRefLanguage($options["from"]);
                } catch (Exception $e) {
                    $this->stderr($e->getMessage(), []);
                    return false;
                }
            }

            # Déploiement
            try {
                $compiler->deploy();
                $refLanguage = $compiler->getRefLanguage();
                $this->stdout("Le déploiment des clés à bien été effectué avec " .
                    "succès depuis la langue de référence %s", [$refLanguage]);
            } catch (Exception $e) {
                $this->stderr($e->getMessage(), []);
                return false;
            }
        }
    }

    /**
     * Affiche le manuel d'aide.
     *
     * @param int $level
     *
     * @return void
     */
    protected function help($level = 0)
    {
        $separator = self::OPTIONS['separator'];
        $name = $this->cmdName;

        $man = <<<HELP
        
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
                            Séparateur : virgule ($separator)

        --default           Fait en sorte que la langue en cours d'ajout
                            devienne également la langue par defaut.
                            Si plusieurs valeur, alors c'est la première qui est
                            retenue.

        --remove-languages  Supprime la/les langue(s) spécifiée(s) du registre
        --remove-langs      et supprime les fichiers associé
                            Format : xx-XX
                            Séparateur : virgule ($separator)

        --preserve-files    Demande la concervation des fichiers lors d'une
                            suppresion de langue.

        --set-default-lang  Définit la langue par défaut.
HELP;
        fwrite($this->psdtout, $man . PHP_EOL);
        if ($level) die($level);
    }

    /**
     * Met en évidence les valeurs utilisateur dans les messages
     *
     * @param  string $message Message à analyser
     *
     * @return string $message Message traité
     */
    protected function highlight($message)
    {
        $color_in = self::OPTIONS['colors']['color_in'];

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
     *
     * @return void
     */
    protected function stderr($message, $args, $level = 1)
    {
        // Connexion aux variables globales
        $color_err = self::OPTIONS['colors']['color_err'];
        $color_war = self::OPTIONS['colors']['color_war'];

        // Traitement en fonction du niveau d'erreur
        $level_str = ($level) ? "ERROR" : "WARNING";
        $color = ($level) ? $color_err : $color_war;

        // Mise en evidence des saisie utilisateur
        $message = $this->highlight($message);
        $message = "[ \e[38;5;{$color}m$level_str\e[0m ] :: $message" . PHP_EOL;

        fwrite($this->pstderr, vsprintf($message, $args));
        if ($level && !$this->noDie) die($level);
    }

    /**
     * Emet des messages dans le flux classique STDOUT
     *
     * @param string $message Message à afficher dans le STDOUT
     * @param array  $arg     Elements à introduire dans le message
     */
    protected function stdout($message, $args)
    {
        $options = self::OPTIONS;

        if (!isset($options["silent"])) {
            $message = $this->highlight($message);
            $message = "[ INFO ] :: $message".PHP_EOL;
            fwrite($this->psdtout, vsprintf($message, $args));
        }
    }

    /**
     * Définie la ressource de sortie standard.
     *
     * @param bool|resource $stdout Pointeur vers une ressource ayant un accès en écriture.
     */
    public function setStdout($stdout = STDOUT)
    {
        $this->psdtout = $stdout;
    }

    /**
     * Définie la ressource de sortie des erreurs.
     *
     * @param bool|resource $stderr Pointeur vers une ressource ayant un accès en écriture.
     */
    public function setStderr($stderr = STDERR)
    {
        $this->pstderr = $stderr;
    }

    /**
     * Définie le comportement des fonctions die.
     *
     * @param bool $nodie
     */
    public function setNoDie($nodie = false)
    {
        $this->noDie = $nodie;
    }

}
