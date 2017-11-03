<?php
/**
 * File :: SYSLang.php
 *
 * Interface d'utilisation de SYSLang pour PHP.
 *
 * @author    Nicolas DUPRE
 * @release   18/10/2017
 * @version   2.0.0-beta1
 * @package   Index
 *
 * @TODO : Faire les méthodes pour ajouter et supprimer les targets possible.
 */

namespace SYSLang;


class SYSLang extends Core
{
    /**
     * @var string $useLanguage Langue définie qui sera utilisée par le système.
     */
    protected $useLanguage = null;

    /**
     * @var array $target Liste des attributs XML définissant une cible de sortie avec pour information le nom et
     * la fonction de traitement admettant trois arguments : $key et $val qui seront rattaché à $arr
     */
    protected $targets = [
        'SST' => [
            'name' => 'SERVER',
            'callback' => 'self::sstCallback'
        ],
        'CST' => [
            'name' => 'CLIENT',
            'callback' => 'self::cstCallback'
        ]
    ];


    /**
     * SYSLang constructor.
     * @param string $language          Langue à utiliser.
     * @param string $workingDirectory  Dossier d'installation.
     */
    function __construct($workingDirectory = ".", $language = null)
    {
        parent::__construct($workingDirectory);

        /**
         * SI    $language est valide et définie, vérifier sa disponibilité.
         * SINON Définir la langue selon le système utilisateur (ou par défaut si disponible).
         */
        if (!is_null($language) && parent::checkCode($language) && $this->isRegistered($language)) {
            $this->setLanguage($language);
        } else {
            $this->getUserLanguages();
        }
    }

    /**
     * SYSLang destructor.
     */
    function __destruct()
    {
        parent::__destruct();
    }

    /**
     * Fonction de rappel pour le formatage des données pour la cible SST pour le moteur Template.
     *
     * @param string $key Valeur de l'attribut KEY.
     * @param string $val Text qui lui associé.
     * @param array  $arr Le tableau qui recevra les données.
     *
     * @return bool
     */
    private static function sstCallback($key, $val, &$arr)
    {
        $arr[$key] = $val;
        return true;
    }

    /**
     * Fonction de rappel pour le formatage des données pour la cible CST pour le moteur Template.
     *
     * @param string $key Valeur de l'attribut KEY.
     * @param string $val Text qui lui associé.
     * @param array  $arr Le tableau qui recevra les données.
     *
     * @return bool
     */
    private static function cstCallback($key, $val, &$arr)
    {
        $arr[] = [
            "VAR_KEY" => $key,
            "VAR_VALUE" => $val
        ];
        return true;
    }

    /**
     * Extrait les textes associés à leur clé et les ranges dans le tableau fournir en argument.
     *
     * @param string $source Emplacement vers la/les source(s) xml.
     * @param array  $output Tableau receuillant les ressources, rangé par target.
     */
    protected function extract($source, array &$output)
    {
        $dirname = dirname($source);

        // Traitement spécifique aux dossiers.
        if (is_dir($source)) {
            $dh = opendir($source);

            while($file = readdir($dh)){
                if (preg_match('/^\.{1,2}$/', $file)) continue;

                $this->extract($dirname . '/' . $file, $output);
            }

            closedir($dh);
        }
        // Traitement spécifique aux fichiers.
        if (is_file($source)) {
            try {
                $sxeResource = new \SimpleXMLElement(file_get_contents($source));

                // Parcourir la ressource XML
                for ($i = 0; $i < count($sxeResource->resource); $i++) {
                    // Traitement des elements commun :
                    $resource = $sxeResource->resource[$i];

                    $key = strval($resource->attributes()->KEY);
                    $value = strval($resource);

                    // Traitements des différentes sorties possibles
                    foreach ($this->targets as $attribut => $instructions) {
                        if(is_null($resource->attributes()->$attribut)) continue;
                        if(strtolower(strval($resource->attributes()->$attribut)) === 'false') continue;

                        $targetName = $instructions['name'];
                        $callback = $instructions['callback'];

                        if(!isset($output[$targetName])) $output[$targetName] = [];

                        call_user_func_array($callback, [$key, $value, &$output[$targetName]]);
                    }
                }
            } catch (\Exception $e) {
                // Ne pas emettre d'erreur
            }
        }
    }

    /**
     * Renvoie la langue utilisée.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->useLanguage;
    }

    /**
     * Récupère l'ensemble des textes associé à leur clé, ordonné en fonction de leur cible à l'aide des attributs.
     *
     * @param null|string $langFile Autant d'emplacement (Fichier ou Dossier) dont il faut traiter.
     *
     * @throws \Exception
     *
     * @return array
     */
    public function getTexts($langFile = null)
    {
        $sources = [];
        $outputs = [];

        // Si aucun argument, alors on extrait l'intégralité du dossier.
        if (is_null($langFile)) {
            $sources[] = '.';
        } else {
            $sources = func_get_args();
        }

        // Parcourir les sources
        foreach ($sources as $index => $source) {
            $sourceFullPath = $this->workingDirectory . '/' . $this->useLanguage . '/' . $source;
            if (!file_exists($sourceFullPath)) throw new \Exception(
                sprintf("The following source '%s' not found in '%s'",
                    $source,
                    $this->workingDirectory . '/' . $this->useLanguage
                )
            );
            $this->extract($sourceFullPath, $outputs);
        }

        return $outputs;
    }

    /**
     * Traite les paramètres de langue de l'utilisateur pour définir la langue à utilisée.
     * Si les paramètres ne sont pas compatible, la langue par défaut est utilisée.
     */
    public function getUserLanguages()
    {
        $acceptLanguage = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
            ? explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'])
            : [];
        $userLanguages = [];

        /**
         * Accept-Language: <language>
         * Accept-Language: <locale>
         * Accept-Language: *
         *
         * // Multiple types, weighted with the quality value syntax:
         * Accept-Language: fr-FR, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5
         *
         * CHROME : Array ( [0] => fr-FR [1] => fr;q=0.8    [2] => en-US;q=0.6 [3] => en;q=0.4 )
         * FIREFO : Array ( [0] => fr    [1] => fr-FR;q=0.8 [2] => en-US;q=0.5 [3] => en;q=0.3 )
         * OPERA  : Array ( [0] => fr-FR [1] => fr;q=0.8    [2] => en-US;q=0.6 [3] => en;q=0.4 )
         * SAFARI : Array ( [0] => fr-FR )
         * IE     : Array ( [0] => fr-FR )
         */
        foreach ($acceptLanguage as $key => $language) {
            $language = trim($language);

            if(!preg_match('#^[a-z]{2}#', $language)) continue;

            preg_match_all('#^[a-z]{2}(-[A-Z]{2})?#', $language, $matches);

            $code = $matches[0][0];
            if(preg_match("/^[a-z]{2}$/", $code)) $code .= '-' . strtoupper($code);

            if (!in_array($code, $userLanguages)) $userLanguages[] = $code;
        }

        /**
         * Si aucune langue n'à pu être trouvée, alors utilisé la langue définie dans le fichier de config.
         */
        if (count($userLanguages) === 0) {
            $this->useLanguage = $this->defaultLanguage;
        } else {
            $useDefault = true;

            foreach ($userLanguages as $key => $language) {
                if ($this->isRegistered($language)) {
                    $this->setLanguage($language);
                    $useDefault = false;
                    break;
                }
            }

            if ($useDefault) $this->useLanguage = $this->defaultLanguage;
        }
    }

    /**
     * Définie la langue à utilisée pour l'extraction des textes.
     *
     * @param null|string $lang Code de lange à utiliser.
     *
     * @return bool
     */
    public function setLanguage($lang = null)
    {
        if (is_null($lang) || !$this->isRegistered($lang)) {
            $this->getUserLanguages();
        } else {
            $this->useLanguage = $lang;
            //setcookie('SYSLang_LANG', $lang, time()+365*24*60*60, '/');
        }

        return true;
    }

}
