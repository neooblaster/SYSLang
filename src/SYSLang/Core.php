<?php
/**
 * File :: Core.php
 *
 * Noyaux principale du moteur SYSLang.
 *
 * @author    Nicolas DUPRE
 * @release   02/12/2017
 * @version   2.0.0-alpha3
 * @package   Index
 *
 * @TODO : [P3] Créer un système d'alias pour les langues proche : en-US pointe vers en-EN.
 * @TODO : [P4] Ajouter un system de backup en cas d'erreur de l'utilisateur.
 * @TODO : [P5] Ajouter un attribut LANG dans resources pour identifier tout de suite la langue du fichier xml visualiser.
 */

namespace SYSLang;


class Core
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
     * Modèle représentant un code de langue : ll-CC
     * ll = Lang Code ISO 639-1
     * CC = Country Code ISO 3166-1
     */
    const LANG_CODE_PATTERN = '#^[a-z]{2}-[A-Z]{2}$#';

    /**
     * Modèle représentant un nom de fichier de langue.
     */
    const LANG_FILE_PATTERN = '/[a-zA-Z-_.]+\.(?i)xml/';

    /**
     * Fichier de contrôle d'intégrité des clés de textes.
     */
    const MD5_FILE_NAME = "languages.md5.xml";

    /**
     * Fichier de configuration du moteur de langue
     */
    const XML_CONFIG_FILE = 'languages.xml';

    /**
     * Entete XML - Commune à tous les fichiers XML.
     */
    const XML_HEADER = '<?xml version="1.0" encoding="utf-8"?>';


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
     * @var \SimpleXMLElement $MD5Report Instance du rappor de contrpole MD5.
     */
    protected $MD5Report = null;

    /**
     * @var null $MD5ReportLang L'ensemble des empruntes de texte pour la langue de référence.
     */
    protected $MD5ReportLang = null;

    /**
     * @var array $refPackageKeys Sauvegarde de toutes les clés existantes dans le package pour avertir
     * le user en cas de doublon.
     */
    protected $refLanguageKeys = [];

    /**
     * @var array $registredLanguages Liste des langues disponibles.
     */
    protected $registredLanguages = [
        'KEYS' => [],
        'LIST' => []
    ];

    /**
     * @var string $refLanguage Langue de référence utilisé pour la compilation (MaJ) des autres langues.
     */
    protected $refLanguage = null;

    /**
     * @var \SimpleXMLElement $refLanguageMD5Report Le rapport MD5 de la langue de référence pour effectuer les deltas.
     */
    protected $refLanguageMD5Report = null;

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
     * @param string $lang Autant d'argument respectant le modèle suivant : xx-XX:LangueName
     * ou xx est le lang code ISO 639-1 et XX le country code ISO 3166-1.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function addLanguages($lang = null)
    {
        $this->isInstalled(true);

        /** Récupération de langue enregistrées */
        $firstLang = null;

        if (func_num_args() === 0) throw new \Exception(
          "At least one language name with code must be provided. It must be like this : xx-XX:Name"
        );

        /** Lecture du fichier */
        $xml = self::SXEOverhaul(
            file_get_contents($this->workingDirectory . '/' . self::XML_CONFIG_FILE)
        );

        /** Parcourir les arguments, les contrôler et vérifier l'existance */
        foreach (func_get_args() as $key => $value) {
            /** Procéder au découpage de l'argument Découpage */
            @list($code,$name) = explode(":", $value);

            if (!self::checkCode($code)) throw new \Exception(
                sprintf('Argument supplied "%1$s" is not valid.' .
                    'It must be like this xx-XX:Name.' .
                    'Argument "%1$s" is skipped.'
                    , $value
                )
            );

            if (is_null($firstLang)) $firstLang = $code;
            if (is_null($name)) $name = $code;

            if (!$this->isRegistered($code)) {
                list($lang,$country) = explode("-", $code);

                $new_lang = $xml->addChild("language", $name);
                $new_lang->addAttribute('LANG-CODE', $lang);
                $new_lang->addAttribute('COUNTRY-CODE', $country);
                $new_lang->addAttribute('LANG', $code);
            } else {
                throw new \Exception(
                    sprintf(
                        'The language "%s" with code "%s" already registered in "%s"',
                        $name, $code, self::XML_CONFIG_FILE
                    )
                );
            }
        }

        /** Sauvegarder les modfications */
        self::saveXml($xml, $this->workingDirectory . '/' . self::XML_CONFIG_FILE);

        /** Mise à jour la liste des langues enregistrées */
        $this->listRegLanguages();

        /** Si pas de langue par défaut, alors l'utiliser comme langue par défaut */
        if (is_null($this->defaultLanguage) && !is_null($firstLang)) {
            $this->setDefaultLanguage($firstLang);

            /** Création du premier pack de langue : Dossier + Fichier */
            if (
                !file_exists($this->workingDirectory . '/' . $firstLang)
                || !is_dir($this->workingDirectory . '/' . $firstLang)
            ) mkdir($this->workingDirectory . '/' . $firstLang);

            if (
                !file_exists($this->workingDirectory . '/' . $firstLang . '/generic.xml')
                || !is_file($this->workingDirectory . '/' . $firstLang . '/generic.xml')
            ) {
                $header = self::XML_HEADER . PHP_EOL;
                $openner = '<resources>' . PHP_EOL;
                $entry = "\t" . '<resource ' .
                    'KEY="your_key_name_here" ' .
                    'CST="false" ' .
                    'SST="true">your_coresponding_text_here</resource>' . PHP_EOL;
                $closer = '</resources>';

                file_put_contents(
                    $this->workingDirectory . '/' . $firstLang . '/generic.xml',
                    $header, FILE_APPEND
                );
                file_put_contents(
                    $this->workingDirectory . '/' . $firstLang . '/generic.xml',
                    $openner, FILE_APPEND
                );
                file_put_contents(
                    $this->workingDirectory . '/' . $firstLang . '/generic.xml',
                    $entry, FILE_APPEND
                );
                file_put_contents(
                    $this->workingDirectory . '/' . $firstLang . '/generic.xml',
                    $closer, FILE_APPEND
                );
            }
        }

        return true;
    }

    /**
     * Vérifie que le code de langue fournit est valide.
     * @param string $argument Argument à contrôler
     * @return bool
     */
    static function checkCode($argument)
    {
        return preg_match(
            self::LANG_CODE_PATTERN, $argument
        );
    }

    /**
     * Effectue la maintenance des langues enregistrée depuis la langue de référence donnée.
     * Si la référence n'est pas définie, elle utilise la langue par défaut.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function deploy()
    {
        /** Identifier la langue de référence. Si non définie, utiliser la langue par défaut */
        if (is_null($this->refLanguage)) $this->setRefLanguage($this->defaultLanguage);

        $refLanguage = $this->refLanguage;
        $refLanguagePath = $this->workingDirectory . '/' . $refLanguage;
        $MD5ReportPath = $this->workingDirectory . '/' . self::MD5_FILE_NAME;

        /** Contrôler la disponibilité de la langue de référence */
        if (!file_exists($refLanguagePath) && !is_dir($refLanguage)) throw new \Exception(
            sprintf("There is no folder name '%s' to deploy.", $refLanguage)
        );

        /** Générer le fichier de rapport MD5 s'il n'existe pas */
        if (!file_exists($MD5ReportPath)) {
            file_put_contents($MD5ReportPath, self::XML_HEADER);
            file_put_contents($MD5ReportPath, "<packs>" . PHP_EOL, FILE_APPEND);
            file_put_contents($MD5ReportPath, "</packs>" . PHP_EOL, FILE_APPEND);
        }

        /** Récupération du rapport */
        $this->MD5Report = self::SXEOverhaul(file_get_contents($MD5ReportPath));

        /** Récupération des empruntes SHA1 des clés pour la langue de référence */
        # Si inexistant, alors créer l'entrée
        if (!isset($this->MD5Report->$refLanguage)) $this->MD5Report->addChild($refLanguage);

        $this->MD5ReportLang = $this->MD5Report->$refLanguage;

        $this->processLang();

        /** Sauvegarde du rapport MD5 */
        self::saveXml($this->MD5Report, $MD5ReportPath);
        return true;
    }

    /**
     * Génère des fichiers INI pour se concentrer sur les textes sans se soucier des structures.
     *
     * @param bool $full Permet de n'extraire que les textes ayant pour attribut TIR valant vrai.
     * (TIR = Translate Is Required)
     *
     * @return bool
     */
    public function export ($full = true)
    {
        $langToExport = $this->registredLanguages["KEYS"];

        /**
         * Prévoir un format fichier et un format ZIP
         *
         * Un fichier .ini par langue.
         *  fr-FR.ini
         *      fichier = code
         *      key     = code
         *
         *
         * Fichier :
         * [HEADERS]
         * 001 =
         *
         * [FILES]
         * 001 = generic.xml
         *
         * [KEYS]
         * 00001 = YOUR_KEY
         *
         * [TEXTS]
         * xxx:yyy:zzzzzz = texte
         *
         */
        foreach ($langToExport as $langCode => $langName) {
            $iniCodes = [
                "files" => [],
                "keys" => [],
                "texts" => []
            ];

            $this->parseToINI($iniCodes, $langCode, $full);

            /**
             * Ecriture des fichiers INI.
             */
            $iniFilePath = $this->exportDirectoryPath . "/$langCode.ini";

            // Si le dossier n'existe pas, le créer
            if (!file_exists($this->exportDirectoryPath)) {
                mkdir($this->exportDirectoryPath, 0775, true);
            }

            // Enregistrement des entêtes.
            file_put_contents($iniFilePath, "[HEADERS]");
            file_put_contents($iniFilePath, PHP_EOL . "lang = $langCode", FILE_APPEND);

            // Enregistrement des codes FILES
            file_put_contents($iniFilePath, PHP_EOL . PHP_EOL . "[FILES]", FILE_APPEND);
            foreach ($iniCodes['files'] as $fileKey => $fileCode) {
                file_put_contents(
                    $iniFilePath,
                    PHP_EOL . sprintf("%03d = %s", $fileCode, $fileKey),
                    FILE_APPEND
                );
            }

            // Enregistrement des codes KEYS
            file_put_contents($iniFilePath, PHP_EOL . PHP_EOL . "[KEYS]", FILE_APPEND);
            foreach ($iniCodes['keys'] as $keyKey => $keyCode) {
                file_put_contents(
                    $iniFilePath,
                    PHP_EOL . sprintf("%05d = %s", $keyCode, $keyKey),
                    FILE_APPEND
                );
            }

            // Enregistrement des codes TEXTS
            file_put_contents($iniFilePath, PHP_EOL . PHP_EOL . "[TEXTS]", FILE_APPEND);
            foreach ($iniCodes['texts'] as $idx => $text) {
                file_put_contents($iniFilePath,PHP_EOL . $text,FILE_APPEND);
            }
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
     * Renvoie la langue définie en tant que référence lors de la compilation.
     *
     * @return string
     */
    public function getRefLanguage()
    {
        return $this->refLanguage;
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

            if ($this->isRegistered($lang)) {
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
            if ($this->isRegistered('en-EN')) {
                $this->defaultLanguage = 'en-EN';
            } else {
                foreach ($this->registredLanguages['KEYS'] as $key => $value) {
                    $this->defaultLanguage = $key;
                    break;
                }
            }
            return true;
        }
    }

    /**
     * Procéde à la maintnance des ressources de langue XML.
     *
     * @param null|string $folderPath Méthode récursive permettant le traitement de sous-dossier.
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected function processLang($folderPath = null)
    {
        /** @var string $refLangFullPath Chemin vers le (sous-)dossier à traiter */
        $refLangFullPath = $this->workingDirectory . '/' . $this->refLanguage;
        $refLangFullPath .= ($folderPath) ?: '';

        /** Lecture du dossier */
        $folder = scandir($refLangFullPath);

        /** Traitement du dossier */
        foreach ($folder as $fkey => $file) {
            /** Ignorer les fichiers qui commencent pas un . */
            if (preg_match("/^\./", $file)) continue;

            /** Si c'est un dossier, alors le traiter par récursivité */
            if (is_dir($refLangFullPath . '/' . $file)) {
                $this->processLang($folderPath . '/' .$file);
            } else {
                /** Si ce n'est pas un fichier XML */
                if (!preg_match(self::LANG_FILE_PATTERN, $file)) continue;

                try {
                    /**
                     * PHASE PREPARATOIRE :: TRAITEMENT DES EMPRUNTES MD5
                     */
                    $resources = self::SXEOverhaul(file_get_contents($refLangFullPath . '/' . $file));

                    /** Lister les clés de la langue de référence */
                    $refLangKeys = [];
                    $refLangKeysValues = [];
                    $index = 0;

                    foreach ($resources as $resKey => $resValue) {
                        /** Si la clé n'existe pas, l'ajouter */
                        $key = strval($resValue->attributes()->KEY);
                        $sKey = "K_$key";

                        /** Celle-ci doit être unique */
                        if (!array_key_exists($sKey, $refLangKeys)) {
                            $refLangKeys[$sKey] = $index;
                            $refLangKeysValues[$sKey] = $resValue;
                        } else {
                            throw new \Exception(
                                sprintf(
                                    'The Key "%s" in "%s" is already used',
                                    $key, $folderPath . '/' . $file
                                )
                            );
                        }

                        /** Enregistrement centrale */
                        if (!array_key_exists($sKey, $this->refLanguageKeys)) {
                            $this->refLanguageKeys[$sKey] = [];
                            $this->refLanguageKeys[$sKey][] = $file;
                        } else {
                            //throw new \Exception(
                            //trigger_error(
                            //    sprintf(
                            //        'The key "%s" in "%s" s already used in file(s) : %s [Ref Language : %s]',
                            //        $key, $folderPath . '/' . $file,
                            //        implode(",", $this->refLanguageKeys[$sKey]),
                            //        $this->refLanguage
                            //    )
                            //    , E_USER_WARNING
                            //);
                            //$this->refLanguageKeys[$sKey][] = $file;
                        }

                        $index++;
                    }


                    /** Récupérer le rapport MD5 du fichier en cours de traitement */
                    $MD5FileName = preg_replace("#\/#", '-', $folderPath . '/' . $file);
                    $MD5FileName = "file$MD5FileName";

                    if (!isset($this->MD5ReportLang->$MD5FileName)) {
                        $this->MD5ReportLang->addChild($MD5FileName);
                    }
                    $MD5ReportFile = $this->MD5ReportLang->$MD5FileName;

                    $MD5ReportFileKeys = [];
                    $MD5ReportFileKeysValues = [];
                    $index = 0;

                    /** Lister les clés connue et le MD5 - Elle dispose déjà du suffixe de sécurisation S_ */
                    foreach ($MD5ReportFile->hash as $rfKey => $rfValue) {
                        $srfKey = strval($rfValue->attributes()->KEY);

                        $MD5ReportFileKeys[$srfKey] = $index;
                        $MD5ReportFileKeysValues[$srfKey] = $rfValue;

                        $index++;
                    }


                    /** Contrôler les MD5 des fichiers connu */
                    $keysToControl = array_intersect_key($refLangKeys, $MD5ReportFileKeys);
                    $keysToUpdate = [];

                    foreach ($keysToControl as $cKey => $cValue) {
                        // @TODO: prévoir la gestion des clés personnalisées
                        $refLangCSTValue = strval($refLangKeysValues[$cKey]->attributes()->CST);
                        $refLangSSTValue = strval($refLangKeysValues[$cKey]->attributes()->SST);
                        $refLangValue = strval($refLangKeysValues[$cKey]);

                        $refLangValueMD5 = md5("$refLangCSTValue::$refLangSSTValue::$refLangValue");

                        if ($refLangValueMD5 !== strval($MD5ReportFileKeysValues[$cKey])) {
                            $keysToUpdate[$cKey] = $cValue;
                        }
                    }



                    /**
                     * PHASE DE TRAITEMENT :: DEPLOIEMENT
                     */
                    foreach ($this->registredLanguages["KEYS"] as $code => $name) {
                        /** La langue ne peut se mettre à jour elle même */
                        if ($code === $this->refLanguage) continue;

                        /** Traitement de l'emplacement de destination */
                        $targetPath = $this->workingDirectory . '/' . $code;
                        $targetPath .= ($folderPath) ?: '';
                        $targetPathFile = $targetPath . '/' . $file;

                        if (!file_exists($targetPath)) mkdir($targetPath, 0775, true);
                        if (!file_exists($targetPathFile)) {
                            file_put_contents($targetPathFile, self::XML_HEADER . PHP_EOL);
                            file_put_contents($targetPathFile, '<resources>' . PHP_EOL, FILE_APPEND);
                            file_put_contents($targetPathFile, '</resources>' . PHP_EOL, FILE_APPEND);
                        }

                        /** Traitement du fichier */
                        $targetXMLRes = self::SXEOverhaul(file_get_contents($targetPathFile));
                        $targetKeys = [];
                        $targetKeysValues = [];
                        $index = 0;

                        /** Lister les clées présentes dans le fichier */
                        foreach ($targetXMLRes as $tXMLResKey => $tXMLResValue) {
                            $tKey = strval($tXMLResValue->attributes()->KEY);
                            $stKey = "K_$tKey";

                            $targetKeys[$stKey] = $index;
                            $targetKeysValues[$stKey] = $tXMLResValue;

                            $index++;
                        }

                        /** Référencement des changements */
                        // Clées qui ont été ajoutées.
                        // Clées qui ont été modifiéess.
                        // Clées qui ont été supprimées.
                        $newsKeys = array_diff_key($refLangKeys, $targetKeys);
                        $updatedKeys = array_intersect_key($targetKeys, $keysToUpdate);
                        $deletedKeys = array_diff_key($targetKeys, $refLangKeys);

                        /** Mise à jour du fichier */
                        // Ajout des clées.
                        foreach ($newsKeys as $nKey => $nVal) {
                            $nodeToCopy = $refLangKeysValues[$nKey];
                            $nodeToAdd = $targetXMLRes->addChild('resource', strval($nodeToCopy));

                            foreach ($nodeToCopy->attributes() as $attKey => $attValue) {
                                $nodeToAdd->addAttribute(strval($attKey), strval($attValue));
                            }

                            $attributes = $nodeToAdd->attributes();

                            if (isset($attributes["TIR"])) {
                                $nodeToAdd->attributes()->TIR = "true";
                            } else {
                                $nodeToAdd->addAttribute("TIR", "true");
                            }
                        }
                        // Mise à jour des clées.
                        foreach ($updatedKeys as $uKey => $uVal) {
                            $targetIndex = $targetKeys[$uKey];

                            // @TODO: prévoir la gestion des clés personnalisées
                            $nodeSrc = $refLangKeysValues[$uKey];

                            $newValue = strval($nodeSrc);
                            $newSSTAtt = strval($nodeSrc->attributes()->SST);
                            $newCSTAtt = strval($nodeSrc->attributes()->CST);

                            $targetXMLRes->resource[$targetIndex]->attributes()->SST = $newSSTAtt;
                            $targetXMLRes->resource[$targetIndex]->attributes()->CST = $newCSTAtt;
                            $targetXMLRes->resource[$targetIndex]->attributes()->TIR = "true";
                            $targetXMLRes->resource[$targetIndex] = $newValue;
                        }

                        // Suppression des clées.
                        foreach (array_reverse($deletedKeys) as $dKey => $dVal) {
                            unset($targetXMLRes->resource[$dVal]);
                        }

                        /** Enregistrement des modifications */
                        self::saveXml($targetXMLRes, $targetPathFile);
                    }


                    /**
                     * PHASE DE FINALISATION :: SAUVEGARDE DES EMPRUNTES POUR LA LANGUE TRAITEE
                     */
                    // Empruntes à ajouter.
                    // Empruntes à modifiées.
                    // Empruntes à supprimées.
                    $hashesToInsert = array_diff_key($refLangKeys, $MD5ReportFileKeys);
                    $hashesToUpdate = $keysToUpdate;
                    $hashesToDelete = array_diff_key($MD5ReportFileKeys, $refLangKeys);

                    foreach ($hashesToInsert as $hiKey => $hiValue) {
                        $refLangKeysValuesSST = strval($refLangKeysValues[$hiKey]->attributes()->SST);
                        $refLangKeysValuesCST = strval($refLangKeysValues[$hiKey]->attributes()->CST);
                        $refLangKeysValuesVal = strval($refLangKeysValues[$hiKey]);

                        $hash = md5($refLangKeysValuesSST . '::'
                            . $refLangKeysValuesCST . '::'
                            . $refLangKeysValuesVal
                        );

                        $hashNodeToAdd = $MD5ReportFile->addChild('hash', $hash);
                        $hashNodeToAdd->addAttribute('KEY', $hiKey);
                    }

                    foreach ($hashesToUpdate as $huKey => $huValue) {
                        $refLangKeysValuesSST = strval($refLangKeysValues[$huKey]->attributes()->SST);
                        $refLangKeysValuesCST = strval($refLangKeysValues[$huKey]->attributes()->CST);
                        $refLangKeysValuesVal = strval($refLangKeysValues[$huKey]);

                        $hash = md5($refLangKeysValuesCST . '::'
                            . $refLangKeysValuesSST . '::'
                            . $refLangKeysValuesVal
                        );

                        $nodeIndex = $MD5ReportFileKeys[$huKey];
                        $MD5ReportFile->hash[$nodeIndex] = $hash;
                    }

                    foreach (array_reverse($hashesToDelete) as $hdKey => $hdValue) {
                        $nodeIndex = $MD5ReportFileKeys[$hdKey];
                        unset($MD5ReportFile->hash[$nodeIndex]);
                    }
                } catch (\Exception $e) {
                    throw new \Exception(
                        sprintf('The XML file "%s" in "%s" can not be parse : %s',
                            $file,
                            $folderPath .'/' . $file,
                            $e->getMessage()
                        )
                    );
                }
            }
        } // END_FOREACH_READ_FOLDER

        return true;
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
     * Importe les textes des différents fichier INI validé présent dans le dossier d'importation
     * spécifié.
     *
     * @param bool $finalize        Permet de finaliser l'import en mettant à jour l'attribut TIR
     * pour les futures exportation différentielles.
     * @param bool $preserveFile    Si finalise=true, permet de demander de concervé les fichiers.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function import ($finalize = false, $preserveFile = false)
    {
        if (!file_exists($this->importDirectoryPath)) throw new \Exception(
            sprintf("Import folder '%s' does not exist.", $this->importDirectoryPath)
        );

        $importDir = opendir($this->importDirectoryPath);
        $tags = ["HEADERS", "FILES", "KEYS", "TEXTS"];

        while ($file = readdir($importDir)) {
            // Contrôle de fichier
            $filePath = $this->importDirectoryPath . '/' . $file;

            if (is_dir($filePath)) continue;

            // Initialisation des données pour le fichier en cours de traitement.
            $iniCodes = [
                "files" => [],
                "keys"  => [],
                "texts" => [],
                "sxe"   => []
            ];

            $iniFile = fopen($filePath, "r");
            $tagsFound = array_fill_keys($tags, false);
            $language = null;
            $processing = null;

            // Lecture du fichier.
            while ($buffer = fgets($iniFile)) {
                $tagFound = false;

                // Recherche des balises
                foreach ($tags as $tKey => $tag) {
                    if (preg_match("/^\[$tag\]$/", $buffer)) {
                        $processing = strtolower($tag);
                        //$$processing = true;
                        $tagFound = true;
                        $tagsFound[$tag] = true;
                        break;
                    }
                }

                if ($tagFound || preg_match("/^\s*$/", $buffer)) continue;

                // Découpage du buffer.
                list($key, $value) = preg_split("/\s*=\s*/", $buffer, 2);
                $value = trim($value);

                // Traitement selon l'ensemble en cours de traitement.
                switch ($processing) {
                    // Traitement des données de l'entête.
                    case "headers":
                        // Recherche de la langue correspondante.
                        if ($key === 'lang') $language = $value;
                        break;

                    case "files":
                    case "keys":
                    case "texts":
                        $iniCodes[$processing][$key] = $value;
                        break;
                }
            }

            // Fermeture du fichier
            fclose($iniFile);

            /**
             * Mise à jour du fichier de langue correspondant
             * si les données collectées sont consistentes.
             */
            if (in_array(false, $tagsFound) ||  is_null($language))  throw new \Exception(
                sprintf("The file '%s' can not be imported.", $file)
            );

            // Ouverture des fichiers concernés
            foreach ($iniCodes['files'] as $fKey => $file) {
                $sxePath = $this->workingDirectory . "/$language/$file";

                $iniCodes['sxe'][$fKey] = [
                    "path" => $sxePath,
                    "sxe" => self::SXEOverhaul(
                        file_get_contents($sxePath)
                    ),
                    "indexs" => []
                ];

                // Indexation des clés
                $index = 0;
                foreach ($iniCodes['sxe'][$fKey]['sxe'] as $rKey => $resource) {
                    $sxeKey = strval($resource->attributes()->KEY);
                    $iniCodes['sxe'][$fKey]['indexs'][$sxeKey] = $index;
                    $index++;
                }
            }

            // Procéder à l'importation à proprement parler.
            foreach ($iniCodes['texts'] as $tKey => $text) {
                // Retrouver le fichier et la clé correspondant au texte.
                list($fileCode, $keyCode) = preg_split("/\./", $tKey);

                // Rechercher l'index pour effectuer la mise à jour
                $index = $iniCodes['sxe'][$fileCode]['indexs'][$iniCodes['keys'][$keyCode]];

                // Mise à jour de la ressource correspondante
                $iniCodes['sxe'][$fileCode]['sxe']->resource[$index] = $text;
                if ($finalize) {
                    $iniCodes['sxe'][$fileCode]['sxe']->resource[$index]->attributes()->TIR = "false";
                }
            }

            // Enregistrement des modifications
            foreach ($iniCodes['sxe'] as $sKey => $sxe) {
                self::saveXml($sxe["sxe"], $sxe["path"]);
            }

            // Une fois l'ensemble du process effectué, supprimer la ressource source
            // Uniquement si finalize avec non préservation
            if ($finalize && !$preserveFile) {
                unlink($filePath);
            }
        }

        closedir($importDir);

        return true;
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
            $header = self::XML_HEADER . PHP_EOL;
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
     * Détermine si le chemin donné est de nature absolue ou relative.
     *
     * @param string $path Chaine représentant un chemin.
     *
     * @return bool
     */
    protected function isAbsolute ($path)
    {
        return preg_match("/^\//", $path);
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
     * Controle l'existance de la langue dans le registre.
     *
     * @param string $language  Code de langue à contrôler au format xx-XX.
     * @param bool $throw       Doit-il renvoyer une exeption
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function isRegistered($language, $throw = false)
    {
        if (!array_key_exists($language, $this->registredLanguages['KEYS'])) {
            if ($throw) throw new \Exception(
                sprintf('The language "%s" is not registred', $language)
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

        return true;
    }

    /**
     * Analyse l'ensemble des fichiers de langues pour générer un ensemble de code fichier, clé et textes.
     *
     * @param array  $codes  Tableau de stockage des codes analysé et généré.
     * @param string $lang   Code de langue à analyser.
     * @param bool   $full   Si vaut vrai, alors l'ensemble des textes est exporté.
     * @param null   $subdir Lorsqu'en récursivité, contient le chemin de sous-dossier en cours de traitement
     *
     * @return bool
     */
    protected function parseToINI (&$codes, $lang, $full, $subdir = null)
    {
        $langPath = $this->workingDirectory . '/' . $lang;
        $fullPath = (is_null($subdir)) ? $langPath : $langPath . '/' . $subdir;

        $dir = opendir($fullPath);

        while ($file = readdir($dir)) {
            if (preg_match("/^\.+$/", $file)) continue;

            $filePath = (is_null($subdir)) ? $file : $subdir . '/' .$file ;

            if (is_dir($fullPath . '/' . $file)) {
                $this->parseToINI($codes, $lang, $full, $filePath);
            } else {
                if (!preg_match(self::LANG_FILE_PATTERN, $file)) continue;

                /** Chercher l'existance du fichier en cours de lecture dans le registre code */
                if (!array_key_exists($filePath, $codes['files'])) {
                    $codes['files'][$filePath] = count($codes['files']);
                }
                $fileCode = $codes['files'][$filePath];

                /** Ouverture du fichier XML */
                $xml = self::SXEOverhaul(
                    file_get_contents($fullPath . '/' . $file)
                );

                /** Lecture du fichier */
                for ($r = 0; $r < count($xml->resource); $r++) {
                    $resource = $xml->resource[$r];
                    $attr = $resource->attributes();

                    $tir = strtolower(strval($resource->attributes()->TIR));
                    $tir = (!isset($attr['TIR']) || $tir === 'true') ? true : false;

                    if ($tir || $full) {
                        $key = strval($resource->attributes()->KEY);
                        $texte = strval($resource);

                        /** Chercher l'existance de la clé en cour de lecture dans le registre code */
                        if (!array_key_exists($key, $codes['keys'])) {
                            $codes['keys'][$key] = count($codes['keys']);
                        }

                        $keyCode = $codes['keys'][$key];

                        $entry = sprintf('%03d.%05d = %s', $fileCode, $keyCode, $texte);

                        $codes['texts'][] = $entry;
                    }
                }

                unset($xml);
            }
        }

        closedir($dir);

        return true;
    }

    /**
     * Supprimer la ou les langues spécifiées du registre avec possibilité de concerver les fichiers.
     *
     * @param bool $preserveFiles Si vrai, conserve les fichiers correspondant à/aux langue(s) supprimée(s).
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function removeLanguages($preserveFiles)
    {
        /** Contrôles préalables */
        $this->isInstalled(true);

        if (func_num_args() < 2) throw new \Exception(
            'At least one language code must be provided after argument $preserveFiles.'
        );

        $xml = self::SXEOverhaul(
            file_get_contents($this->workingDirectory . '/' . self::XML_CONFIG_FILE)
        );

        $langCodes = func_get_args();
        $updateDefaultLang = false;
        array_shift($langCodes);

        foreach ($langCodes as $key => $code) {
            if (self::checkCode($code)) {
                if ($this->isRegistered($code)) {
                    for ($i = 0; $i < count($xml->language); $i++) {
                        // Si c'est la lang par défaut, demander une mise à jour de la langue par defaut.
                        if ($code === $this->defaultLanguage) $updateDefaultLang = true;

                        // Supprimer la langue du registre mémorisé.
                        unset($this->registredLanguages['KEYS'][$code]);

                        // Supprimer la langue dans la ressource SXE.
                        if (strval($xml->language[$i]->attributes()->LANG) === $code) {
                            unset($xml->language[$i]);
                            break;
                        }
                    }
                } else {
                    throw new \Exception(
                        sprintf(
                            'The language code "%1$s" is not registered in "%2$s"',
                            $code, self::XML_CONFIG_FILE
                        )
                    );
                }
            } else {
                throw new \Exception(
                    sprintf(
                        'Argument supplied "%1$s" is not valid. ' .
                        'It must be like this xx-XX. ' .
                        'Argument "%1$s" is skipped.',
                        $code
                    )
                );
            }
        }

        /** Si la langue par défaut est supprimée, en mettre une autre */
        if ($updateDefaultLang) {
            /**
             * Définir la nouvelle langue à null. Si aucune langue n'est enregistrée, alors on ne met rien
             * pour ne pas bloquer le système.
             * S'il reste des langues enregistrées, alors prendre la première
             */
            $newDefautLang = null;

            foreach ($this->registredLanguages['KEYS'] as $lang => $name) {
                $newDefautLang = $lang;
                break;
            }

            // Mise à jour
            $xml->attributes()->default = $newDefautLang;
        }

        /** Sauvegarder les modifications */
        self::saveXml($xml, $this->workingDirectory . '/' . self::XML_CONFIG_FILE);

        /** Mise à jour de la liste des langues enretistrées */
        $this->listRegLanguages();

        return true;
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

        if (!$this->isRegistered($language)) {
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
     *
     * @param string $directory Dossier cible pour les exportations de fichiers.
     * @param bool   $fullAbsolute  Chemin complétement absolu, depuis la racine système /
     * Si vaut false, alors c'est partiellement absolu depuis le CWD (__DIR__)
     *
     * @return bool
     */
    public function setExportDirectory($directory, $fullAbsolute = true)
    {
        if ($this->isAbsolute($directory)) {
            if (!$fullAbsolute) $directory = $_SERVER["PWD"] . $directory;
        } else {
            $directory = $this->workingDirectory . '/' . $directory;
        }

        $this->exportDirectoryPath = $directory;
        return true;
    }

    /**
     * Définie la langue de référence utilisée pour la compilation.
     *
     * @param string $langCode Code de langue de référence au format xx-XX.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function setRefLanguage($langCode)
    {
        if (!self::checkCode($langCode)) throw  new \Exception(
            sprintf(
                'The language code "%s" provided is not valid. It must be like xx-XX.',
                $langCode
            )
        );

        if (!$this->isRegistered($langCode)) throw new \Exception(
            sprintf(
                'The requested language code "%s" is not registered.',
                $langCode
            )
        );

        $this->refLanguage = $langCode;

        return true;
    }

    /**
     * Défini le dossier source d'importation des fichiers INI.
     *
     * @param string $directory     Dossier source pour les importations de fichiers.
     * @param bool   $fullAbsolute  Chemin complétement absolu, depuis la racine système /
     * Si vaut false, alors c'est partiellement absolu depuis le CWD (__DIR__)
     *
     * @return bool
     */
    public function setImportDirectory($directory, $fullAbsolute = true)
    {
        if ($this->isAbsolute($directory)) {
            if (!$fullAbsolute) $directory = $_SERVER["PWD"] . $directory;
        } else {
            $directory = $this->workingDirectory . '/' . $directory;
        }

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
