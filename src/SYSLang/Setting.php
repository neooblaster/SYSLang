<?php
/**
 * File :: Settings.php
 *
 * Noyaux principale du moteur SYSLang.
 *
 * @author    Nicolas DUPRE
 * @release   04/11/2017
 * @version
 * @package   SYSLang
 *
 * @TODO : [P1] Rappratrier Core::LANG_CODE_PATTERN ici.
 * @TODO : [P2] Voir pr le rappatriement de checkCode (car utilisé chez Core et SYSLang)
 */

namespace SYSLang;


/**
 * @TODO: Définir la présentation et le comportement pour DEPLOY et EXPORT et IMPORT.
 * @TODO: voir pour la config
 *
 *
 * Class Setting
 * @package SYSLang
 */


class Setting {

    /**
     * Modèle RegExp identifiant un nom de fichier de langue valide.
     */
    const LANG_FILE_PATTERN = "/^[a-zA-Z-_.]+\.(?i)xml$/";

    const SCOPE_LEVEL_LANG = 0;

    const SCOPE_LEVEL_FILE = 1;

    const SCOPE_LEVEL_KEY = 2;

    const SCOPE_LEVEL_ALL = -1;


    /**
     * @var array $settings Configuration pour le déploiement, l'importation et l'exportation.
     */
    protected $settings = [];

    /**
     * @var array $processing Permet l'utilisation successive d'une opération (add/del/get) et la famille qui est
     * concernée par l'opérration (lang/file/code)
     */
    protected $processing = [
        "adding" => false,
        "deleting" => false,
        "getting" => false
    ];

    /**
     * @var mixed $scoping Scope d'application auquel la méthode suivante s'appliquera.
     *
     * String suivant le modèle SPattern
     * Array of String suivant le modèle SPattern
     *
     * SPattern:
     *    lang:     xx-XX       =>  [a-z]{2}-[A-Z]{2}       applicable : files() keys()
     *    file:     file.xml    =>  [a-zA-Z-_.]+\.xml       applicable : keys()
     *
     */
    protected $scoping = [
        "value" => null,
        "level" => null
    ];






    /**
     * Setting->get()->langs()                    => Retourne les langues de la config
     * Setting->get()->files($lang)               => Retourne les fichiers de la config
     * Setting->get()->keys($lang/$file, $file)   => Retourne les clés de la config
     * Setting->add()->lang()
     * Setting->add()->files()
     * Setting->add()->keys()
     * Setting->del()->lang()
     * Setting->del()->files()
     * Setting->del()->keys()
     */

    /**
     * Si instanciation
     * $moteur->setting->add()->lang()
     *
     * Si ext
     * $moteur->add()->lang()
     */

    /**
     * Regles de gestion pour les arguments
     *
     * $language = [        // peu reprendre la mécanique des autres arguments
     *      0 => "fr-FR"
     *      "fr-FR" => []
     * ]
     *
     * $files = [
     *      0 => "file_name"         => for all
     *      "fr-FR" => "file_name"   => for lang key
     *      "fr-FR" => ["files"]     => all file for lang key
     * }
     *
     * $keys = [
     *      0 => "key_name"         => for all
     *      "fr-FR" => "key"        => for lang key
     *      "fr-FR" => ["keys"]     => all keys for lang key
     *      "file"  => "key"        => for file key
     *      "file"  => ["keys"]     => all keys for file key
     * }
     *
     */

    protected function parseScopes($scope)
    {
        /**
         * 1. Soit c'est null et vaut pour tous (all) (*).
         * 2. Soit c'est une chaine et il est "unique"
         * 3. Soit c'est une liste et ils faut tous les parser
         */

        /**
         * Cas 1 et 2 : Analyse et mise en liste pour "lineariser" le traitement
         */
        if (
            is_null($scope)
            || is_string($scope)
        ) {
            $this->checkScope($scope, true);

            $scope = ($scope) ? [$scope] : null;
        }

        /**
         * Cas 3 : Parcourir les valeurs pour contrôler leur validité.
         */
        else if (is_array($scope)) {
            foreach ($scope as $idx => $value) {
                $this->checkScope($value);
            }
        } else {
            throw new \InvalidArgumentException(

            );
        }

        return $scope;
    }

    protected function parseRule($rule)
    {

    }

    protected function parse($rule, $processLevel)
    {
        /**
         * Le traitement est possible uniquement
         * 
         * #---------#-------#-------#------#-------#----------#
         * | process | level | scope | name | value | possible |
         * #---------#-------#-------#------#-------#----------#
         * |  lang   |   0   |  -1   | all  | null  |   OUI    |
         * |  lang   |   0   |   0   | lang | fr-FR |   NON    |
         * |  lang   |   0   |   1   | file | x.xml |   NON    |
         * |  lang   |   0   |   2   | key  | kname |   NON    |
         * #---------#-------#-------#------#-------#----------#
         * |  file   |   1   |  -1   | all  | null  |   OUI    |
         * |  file   |   1   |   0   | lang | fr-FR |   OUI    |
         * |  file   |   1   |   1   | file | x.xml |   NON    |
         * |  file   |   1   |   2   | key  | kname |   NON    |
         * #---------#-------#-------#------#-------#----------#
         * |  key    |   2   |  -1   | all  | null  |   OUI    |
         * |  key    |   2   |   0   | lang | fr-FR |   OUI    |
         * |  key    |   2   |   1   | file | x.xml |   OUI    |
         * |  key    |   2   |   2   | key  | kname |   NON    |
         * #---------#-------#-------#------#-------#----------#
         *
         * Soit plevel > slevel
         *
         */
        if (!($processLevel > $this->scoping["level"])) throw new \Exception(
            sprintf("The rule provided for method %s() cannot be parse for the scope setted in get()..",
                debug_backtrace()[1]['function']
            )
        );


        /** Traitement de la règle */


        return true;
    }





    # --------------- FINISHED AND TESTED --------------- #

    /**
     * Passe le système en mode "ajout".
     *
     * @param string $scope Scope d'application pour l'ajout des ressources qui seront spécifiées
     * par la méthode suivante.
     *
     * @return Setting $this
     */
    public function add($scope = null)
    {
        $this->enable($scope, "adding");

        return $this;
    }

    /**
     * Renvoie la configuration complète.
     * Utilisable uniquement en process "getting"
     * Ignore le scope défini par get()
     *
     * @return array
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function all()
    {
        if ($this->processing["getting"]) return $this->settings;

        throw new \Exception(
            sprintf("%s method can only used with processing get().", __METHOD__)
        );
    }

    /**
     * Contrôle l'argument scope soumis pour les méthode add(), del(), get()
     *
     * @param $scope
     * @param bool $throws
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected function checkScope($scope, $throws = false)
    {
        $test = (
            is_null($scope)
            || preg_match(Core::LANG_CODE_PATTERN, $scope)
            || preg_match(self::LANG_FILE_PATTERN, $scope)
        );

        if ($throws) throw new \Exception(
            sprintf('Argument $scope provided "%s" is not valid.', $scope)
        );

        return $test;
    }

    /**
     * Bascule l'indicateur donnée à l'état vrai en désengagant les autres.
     *
     * @param string $process Nom de l'indicateur représentant le processus en cours d'execution.
     *
     * @return bool
     */
    protected function commute($process)
    {
        foreach ($this->processing as $key => &$state) {
            $state = ($key === $process) ?: false;
        }

        unset($state);

        return true;
    }

    /**
     * Passe le système en mode "suppression".
     *
     * @param string $scope Scope d'application pour la suppression des ressources qui seront spécifiées
     * par la méthode suivante.
     *
     * @return Setting $this
     */
    public function del($scope = null)
    {
        $this->enable($scope, "deleting");

        return $this;
    }

    /**
     * Contrôle et active le mode "processing" demandé.
     *
     * @param string $scope     Scope d'application auquel la méthode suivante s'appliquera.
     * @param string $process   Nom du process qui va se dérouler.
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected function enable($scope, $process)
    {
        //if (!$this->checkScope($scope))
        $this->parseScopes($scope);

        $this->storeScopes($scope);
        $this->commute($process);

        return true;
    }

    /**
     * Ajoute les règles données au niveau FICHIER
     * Configuration de niveau 1 (Moyen)
     *
     * @param $rule
     *
     * @return bool
     */
    public function files($rule)
    {
        return $this->parse($rule, self::SCOPE_LEVEL_FILE);
    }

    /**
     * Passe le système en mode "Récupération".
     *
     * @param string $scope Scope d'application pour la récupération des ressources qui seront spécifiées
     * par la méthode suivante.
     *
     * @return $this
     */
    public function get($scope = null)
    {
        $this->enable($scope, "getting");

        return $this;
    }

    /**
     * Ajoute les règles données au niveau KEYS
     * Configuration de niveau 2 (Précis)
     *
     * @param $rule
     *
     * @return bool
     */
    public function keys($rule)
    {
        return $this->parse($rule, self::SCOPE_LEVEL_KEY);
    }

    /**
     * Ajoute les règles données au niveau des langues
     * Configuration de niveau 0 (Large)
     *
     * @param $rule
     *
     * @return bool
     */
    public function langs($rule)
    {
        return $this->parse($rule, self::SCOPE_LEVEL_LANG);
    }

    /**
     * Mémorise le scope demandé en y associant le type auquel il correspond.
     *
     * @param string $scope Scope d'application auquel la méthode suivante s'appliquera.
     *
     * @return bool
     */
    protected function storeScopes($scope)
    {
        if (is_null($scope)) {
            $this->scoping["value"] = $scope;
            $this->scoping["level"] = self::SCOPE_LEVEL_ALL;
        }

        if (preg_match(Core::LANG_CODE_PATTERN, $scope)){
            $this->scoping["value"] = $scope;
            $this->scoping["level"] = self::SCOPE_LEVEL_LANG;
        }

        if (preg_match(self::LANG_FILE_PATTERN, $scope)) {
            $this->scoping["value"] = $scope;
            $this->scoping["level"] = self::SCOPE_LEVEL_FILE;
        }

        return true;
    }

}
