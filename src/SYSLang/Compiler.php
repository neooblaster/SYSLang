<?php
/**
 * File :: Compiler.php
 *
 * %DESC BLOCK%
 *
 * @author    Nicolas DUPRE
 * @release   02/10/2017
 * @version   2.0.x-alpha1
 * @package   Index
 */

namespace SYSLang;


class Compiler
{
    /**
     * Modèle de remplacement de la balise ouvrante CDATA pour la portabilité.
     */
    const CDATA_STR_START = "[[";

    /**
     * Modéle RegExp pour retrouver la balise ouvrante CDATA pour la restitution.
     */
    const CDATA_REG_START = "\[\[";

    /**
     * Modèle de remplacement de la balise fermante CDATA pour la portabilité.
     */
    const CDATA_STR_END = "]]";

    /**
     * Modéle RegExp pour retrouver la balise fermante CDATA pour la restitution.
     */
    const CDATA_REG_END = "\]\]";

    /**
     * Fichier de configuration du moteur de langue
     */
    const XML_CONFIG_FILE = 'languages.xml';


    /**
     * @var string $defaultLanguage Langue lue par défault au format xx-XX.
     * xx = Language Code (ISO 639-1)
     * XX = Country Code (ISO 3166-1)
     */
    protected $defaultLanguage = null;

    /**
     * @var string $exportDirectoryPath Chemin vers le dossier d'exportation.
     */
    protected $exportDirectoryPath = null;

    /**
     * @var string $importDirectoryPath Chemin vers le dossier d'importation.
     */
    protected $importDirectoryPath = null;

    /**
     * @var array $iniFilesCode Liste des codes correspondants aux fichiers XML de langue.
     */
    protected $iniFilesCode = [];

    /**
     * @var array $iniKeysCode Liste des codes correspondants aux clés de textes de langue.
     */
    protected $iniKeysCode = [];

    /**
     * @var array $iniTexts Liste des textes associés à leur clé codé.
     */
    protected $iniTexts = [];

    /**
     * @var array $iniSXEResources Liste des ressources SimpleXMLElement pour un traitement dans un ordre aleatoire.
     */
    protected $iniSXEResources = [];

    /**
     * @var array $refPackageKeys Sauvegarde de toutes les clés existantes dans le package pour avertir
     * le user en cas de doublon.
     */
    protected $refPackageKeys = [];

    /**
     * @var array $registredLanguages Liste des langues disponibles.
     */
    protected $registredLanguages = [
        'KEYS' => [],
        'LIST' => []
    ];

    /**
     * @var string $runInstance L'instanciation de la classe se voit attribué un ID unique.
     */
    protected $runInstance = null;

    /**
     * @var string $workingDirectory Dossier de travail pour le compilateur.
     */
    protected $workingDirectory = null;










    /**
     * Compiler constructor.
     * @param string $workingDirectory Dossier de travail pour le compilateur.
     */
    function __construct($workingDirectory = ".")
    {
        /** Enregistrement des emplacements */
        $this->workingDirectory = $workingDirectory;
        $this->exportDirectoryPath = $workingDirectory . '/exports';
        $this->importDirectoryPath = $workingDirectory . '/imports';

        /** Génération d'un identifiant d'instance */
        $number = rand(0, 999999999);
        $this->runInstance = sha1(time() . $number);

        if ($this->isInstalled()) {
            $this->listRegLanguages();
            $this->getDefaultLanguage();
        }
    }

    /**
     * Compiler descrutor.
     */
    function __destruct()
    {
    }

    /**
     * Enregistre une nouvelle ou plusieurs langue suivant le modèle suivant xx-XX:LangueName.
     *
     * @param  string $lang Autant d'argument respectant le modèle suivant : xx-XX:LangueName
     * ou xx est le lang code ISO 639-1 et XX le country code ISO 3166-1.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function addLanguage()
    {
        $this->isInstalled(true);

        /** Récupération de langue enregistrées */
        $languages = $this->registredLanguages['KEYS'];
        $firstLangue = null;

        /** Lecture du fichier */
        $xml = self::SXEOverhaul(
            file_get_contents($this->workingDirectory . '/' . self::XML_CONFIG_FILE)
        );

        /** Parcourir les arguments, les contrôler et vérifier l'existance */
        foreach (func_get_args() as $key => $value) {
            /** Contole */
            if (preg_match("#^[a-z]{2}-[A-Z]{2}#", $value)) {
                /** Découpage */
                list($code,$name) = explode(":", $value);

                if (is_null($firstLangue)) $firstLangue = $code;
                if (is_null($name)) $name = $code;

                if (!array_key_exists($code, $languages)) {
                    list($lang,$country) = explode("-", $code);

                    $new_lang = $xml->addChild("language", $name);
                    $new_lang->addAttribute('LANG-CODE', $lang);
                    $new_lang->addAttribute('COUNTRY_CODE', $country);
                    $new_lang->addAttribute('LANG', $code);
                } else {
                    throw new \Exception(
                        sprintf(
                            'The language "%s" with code "%" already registered in "%s"',
                            $name, $code, self::XML_CONFIG_FILE
                        )
                    );
                }
            } else {
                throw new \Exception(
                    sprintf('Argument supplied "%1$s" is not valide.' .
                        'It must be like this xx-XX:Name.' .
                        'Argument "%1$s" is skipped.'
                        , $value
                    )
                );
            }
        }

        /** Sauvegarder les modfications */
        self::saveXml($xml, $this->workingDirectory . '/' . self::XML_CONFIG_FILE);

        /** Mettre à jour la liste des lang enregistrée */
        $this->listRegLanguages();

        /** Si pas de langue par défaut, alors l'utiliser comme langue par défaut */
        if (is_null($this->defaultLanguage) && !is_null($firstLangue)) {
            $this->setDefaultLanguage($code);
        }

        return true;
    }

    /**
     * Renvoie la liste des langues enregistrées.
     * @return array
     */
    public function getRegLanguages()
    {
        return $this->registredLanguages;
    }

    /**
     * Récupère et mémorise la langue par défaut.
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected function getDefaultLanguage()
    {
        /** Lecture du fichier */
        $languages = new \simpleXMLElement(file_get_contents(
            $this->workingDirectory . '/' . self::XML_CONFIG_FILE
        ));

        /** Contrôler la langue par défaut - A default en définir une */
        if (gettype($languages->attributes()->default) !== 'NULL') {
            /** Vérifier que la pack existe (en cas de modification manuelle du fichier) */
            $lang = strval($languages->attributes()->default);

            if (array_key_exists($lang, $this->registredLanguages['KEYS'])) {
                $this->defaultLanguage = $lang;

                return true;
            } else {
                foreach ($this->registredLanguages['KEYS'] as $key => $value) {
                    $this->defaultLanguage = $key;
                    break;
                }
                throw new \Exception(
                    "The default language '$lang' is not registred." .
                    "'$this->defaultLanguage' use instead."
                );
            }
        } else {
            /** Choisir l'anglais par défaut : en-EN , Sinon prendre la première disponible */
            if (array_key_exists('en-EN', $this->registredLanguages['KEYS'])) {
                $this->defaultLanguage = 'en-EN';
            } else {
                foreach ($this->registredLanguages['KEYS'] as $key => $value) {
                    $this->defaultLanguage = $key;
                    break;
                }
            }
        }
    }

    /**
     * Retour le contenu du dossier courant de la classe. Elle aide au debuggage.
     *
     * @return array
     */
    public function showWorkingDir()
    {
        return scandir($this->workingDirectory);
    }

    /**
     * Install le fichier de configuration dans le dossier spécifié.
     */
    public function install()
    {
        /** Contrôle de l'emplacement */
        if (!file_exists($this->workingDirectory)) {
            mkdir($this->workingDirectory, 0744, true);
        }

        /** Creation du fichier de configuration des langues */
        if (!file_exists($this->workingDirectory . '/' . self::XML_CONFIG_FILE)) {
            $header = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
            $openner = '<languages>' . PHP_EOL;
            $closer = '</languages>' . PHP_EOL;

            file_put_contents($this->workingDirectory . '/' . self::XML_CONFIG_FILE, $header);
            file_put_contents($this->workingDirectory . '/' . self::XML_CONFIG_FILE, $openner, FILE_APPEND);
            file_put_contents($this->workingDirectory . '/' . self::XML_CONFIG_FILE, $closer, FILE_APPEND);

            return true;
        }

        return false;
    }

    /**
     * Vérifier si le fichier de langue languages.xml est installé.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function isInstalled($throw = false)
    {
        if (!file_exists($this->workingDirectory . '/' . self::XML_CONFIG_FILE)) {
            if ($throw) throw new \Exception(
                "SYSLang is not installed in '$this->workingDirectory'"
            );
            return false;
        }

        return true;
    }

    /**
     * Liste les langues enregistrées.
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected function listRegLanguages()
    {
        /** Lecture du fichier */
        $languages = new \simpleXMLElement(file_get_contents(
            $this->workingDirectory . '/' . self::XML_CONFIG_FILE
        ));

        /** Lister les langues enregistrées */
        $keys = [];
        $list = [];

        for ($i = 0; $i < count($languages->language); $i++) {
            if (gettype($languages->language[$i]->attributes()->LANG) !== 'NULL') {
                /** Enregistrement de la langue dans la liste en tant que tableau de donnée fonctionnelle **/
                $keys[strval($languages->language[$i]->attributes()->LANG)] = strval($languages->language[$i]);

                /** Enregistrement de la langue dans la liste en tant que tableau de donnée **/
                $list[] = Array(
                    "LANG_NAME" => strval($languages->language[$i]),
                    "LANG_KEY" => strval($languages->language[$i]->attributes()->LANG)
                );
            } else {
                throw new \Exception(
                    'Key "LANG" is missing for "' . strval($languages->language[$i]) .
                    '". This language is skipped.'
                );
            }
        }

        /** Finalisation : Sauvegarde au sein de la classe */
        $this->registredLanguages = [
            "KEYS" => $keys,
            "LIST" => $list
        ];
    }

    public function removeLanguage()
    {
    }

    /**
     * Sauvegarde une ressource SimpleXMLElement dans un fichier en respectant la présentation.
     *
     * @param \SimpleXMLElement $sxe Ressource SimpleXMLElement à enregistrer proprement
     * @param string $file Fichier de destination
     * @param int $flag Flag correspondant à la fonction file_put_contents
     *
     * @return bool
     */
    static function saveXml($sxe, $file, $flag = null)
    {
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($sxe->asXML());

        file_put_contents($file, $dom->saveXML(), (($flag) ?: 0));

        // Récupération du fichier XML en tant que chaine
        $str = file_get_contents($file);

        // Restitution des balises CDATA
        $str = preg_replace(
            "#" . self::CDATA_REG_START .
            "\s*(.*)\s*" .self::CDATA_REG_END .
            "#m",
            "<![CDATA[$1]]>",
            $str
        );

        // Restitution des entités texts et numériques
        $str = preg_replace("#::(\#?)([a-zA-Z0-9]+)::#", "&$1$2;", $str);

        // Finalisation
        file_put_contents($file, $str);

        return true;
    }

    /**
     * Défini la langue par défaut.
     * @param string $language Code du language à utilisé par défaut au format xx-XX.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function setDefaultLanguage($language)
    {
        $this->isInstalled(true);

        if (!array_key_exists($language, $this->registredLanguages['KEYS'])) {
            throw new \Exception(
                "The language '$language' is not registered in the list. Nothing done."
            );
        }

        $languages = self::SXEOverhaul(
            file_get_contents($this->workingDirectory . '/' . self::XML_CONFIG_FILE)
        );

        if (gettype($languages->attributes()->default) !== 'NULL') {
            $languages->attributes()->default = $language;
        } else {
            $languages->addAttribute('default', $language);
        }

        self::saveXml($languages, $this->workingDirectory . '/' . self::XML_CONFIG_FILE);

        $this->getDefaultLanguage();

        return true;
    }

    /**
     * Définie le dossier d'export des fichiers INI.
     * @param string $directory Dossier cible pour les exportations de fichiers.
     * @return bool
     */
    public function setExportDirectory($directory)
    {
        $this->exportDirectoryPath = $directory;
        return true;
    }

    /**
     * Défini le dossier source d'importation des fichiers INI.
     * @param string $directory Dossier source pour les importations de fichiers.
     * @return bool
     */
    public function setImportDirectory($directory)
    {
        $this->importDirectoryPath = $directory;
        return true;
    }

    /**
     * Transcrit les balises CDATA pour une gestion totale sous SimpleXMLElement.
     * @param string $xml_str Chaine de text etant de l'XML valide.
     * @return \SimpleXMLElement Retourne une instance SimpleXMLElement exploitable.
     */
    static function SXEOverhaul($xml_str)
    {
        /** Convertir les balises CDATA en [[ ]] */
        $xml_str = preg_replace(
            "#<!\[CDATA\[\s*(.*)\s*\]\]>#m",
            self::CDATA_STR_START . "$1" . self::CDATA_STR_END,
            $xml_str
        );

        /** Remplacer les balises HTML qui se trouvent dans une balise CDATA */
        $xml_str = preg_replace_callback(
            "#(?<=\[\[).*(?=\]\])#mi",
            function ($matches) {
                $return = $matches[0];
                $return = str_replace("<", "&lt;", $return);
                $return = str_replace(">", "&gt;", $return);
                return $return;
            },
            $xml_str
        );

        /** Remplacer toutes les entités de caractères numérique */
        $xml_str = preg_replace("#&(\#?)([a-zA-Z0-9]+);#m", "::$1$2::", $xml_str);

        return new \SimpleXMLElement($xml_str);
    }
}
