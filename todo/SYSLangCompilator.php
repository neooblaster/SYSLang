<?php
/**
 * File :: SYSLangCompilator.php
 *
 * Extension de SYSLang. Elle est déstinée au développeur. Elle permet d'initialiser le dossier de langue
 * de manière à être compatible pour la classe SYSLang. Elle permet d'ajouter simplement de nouveau package
 * de lang, d'exporter sous des formats plus simple comme le modèle INI et de faire des imports pour mise à jour.
 *
 * @author    Nicolas DUPRE
 * @release   19/08/2017
 * @version   1.1.1
 * @package   SYSLangCompilator
 *
 * @TODO : Ajouter un attribut LANG dans resources pour identifier tout de suite la langue du fichier xml visualiser
 * @TODO : idealement, doit permettre de fournir un portail d'utilisation
 * @TODO : Ajouter un system de backup en cas d'erreur de l'utilisateur
 * @TODO : Utiliser le "default-language" comme package de reférence pour compile
 */
namespace SYSLangCompilator;

use SYSLang\SYSLang;

/**
 * Class SYSLangCompilator
 * @package SYSLangCompilator
 */
class SYSLangCompilator extends SYSLang
{
	const MD5_FILE_NAME = "languages.md5.xml";

    /**
     * @var string $_ref_package Package de référence pour la compilati
     */
	protected $_ref_package = null;

    /**
     * @var string $_workspace Dossier de travail
     */
	protected $_workspace = null;

    /**
     * @var array $_ref_package_keys Sauvegarde de toutes les clé existante dans le package pour avertir le user en cas de doublon
     */
	protected $_ref_package_keys = null;

    /**
     * @var \SimpleXMLElement $_MD5_report Instance SimpleXMLElement stockant le rapport MD5 de controle
     */
	protected $_MD5_report = null;

    /**
     * @var \SimpleXMLElement $_MD5_package_report Focus sur le rapport MD5 correspondant au package de référence
     */
	protected $_MD5_package_report = null;

    /**
     * @var array $_ini_files_code Liste des codes correspondant aux fichier xml de lan
     */
	protected $_ini_files_code = null;

    /**
     * @var array $_ini_keys_code Liste des codes correspondant aux clé de texts de lang
     */
	protected $_ini_keys_code = null;

    /**
     * @var array $_ini_texts Liste des textes associé à leur clé codés
     */
	protected $_ini_texts = null;

    /**
     * @var array $_ini_sxe_resources Liste des ressources SimpleXMLElement pour un traitement dans un ordre aleatoire
     */
	protected $_ini_sxe_resources = null;

    /**
     * @var string $_run_instance L'instanciation de la classe se voit attribué un ID unique
     */
	protected $_run_instance = null;

    /**
     * @var string $_export_folder_path Chemin vers le dossier d'exportation
     */
	protected $_export_folder_path = null;

    /**
     * @var string $_import_folder_path Chemin vers le dossier d'importation
     */
	protected $_import_folder_path = null;


    /**
     * SYSLangCompilator constructor.
     * @param null $working_directory
     */
	function __construct($working_directory){
		/** Instantiation de SYSLang **/
		parent::__construct($working_directory."/Languages");
		
		/** Enregistrement du dossier de travail **/
		$this->_working_directory = $working_directory;
		$this->_export_folder_path = $this->_files_repository."/exports";
		$this->_import_folder_path = $this->_files_repository."/imports";
		
		/** Initialisation des variables **/
		$this->_ref_packages_keys = Array();
		
		$this->_ini_files_code = Array();
		$this->_ini_keys_code = Array();
		$this->_ini_texts = Array();
		$this->_ini_sxe_resources = Array();
		
		/** Génération d'un identifiant d'instance **/
		$number = rand(0, 999999999);
		$this->_run_instance = sha1(time().'.'.$number);
	}

    /**
     * SYSLangCompilator destructor
     */
	function __destruct(){
		parent::__destruct();
	}

    /**
     * Défini le package de référence
     * @param string $package
     * @return bool
     */
	public function set_ref_language($package){
		/** Ne retenir que le code package (dans le cas où l'utilisateur envois à la facon d'une déclaration **/
		preg_match("#[a-z]{2}-[A-Z]{2}#", $package, $matches);
		
		$this->_ref_package = $matches[0];
		
		return true;
	}

    /**
     * Défini le dossier d'export des fichiers INI
     * @param string $repository
     * @return bool
     */
	public function set_export_repository($repository){
		$this->_export_folder_path = $repository;
		
		return true;
	}

    /**
     * Défini le dossier d'importation des fichiers INI
     * @param string $repository
     * @return bool
     */
	public function set_import_repository($repository){
		$this->_import_folder_path = $repository;
		
		return true;
	}

    /**
     * Enregistre une nouvelle ou plusieurs langue suivant le modèle suivant xx-XX:LangueName
     * @param null|string $lang
     * @return bool
     */
	public function add_languages($lang=null){// fr-FR:Francais
		/** Controler les paramètres **/
		if($lang === null){
			parent::throw_error('Missing argument 1 ($lang) for SYSLangCompilator::add_languages(). $lang represents the language and it\'s code as this pattern lang_code:lang_name.', E_USER_ERROR);
			return false;
		}
		
		/** Récupérer les langues disponible **/
		$languages = $this->get_avail_languages();
		$languages = $languages['KEYS']; 
		
		/** Ouvrir le fichier languages.xml **/
		//$xmllanguages = new SimpleXMLElement(file_get_contents($this->_files_repository."/".parent::XML_CONF_FILE));
		$xmllanguages = self::SXEOverhaul(file_get_contents($this->_files_repository."/".parent::XML_CONF_FILE));
		
		
		/** Parcourir les arguments, les controler, vérifier l'existance **/
		foreach(func_get_args() as $key => $value){
			/** Controler l'argument **/
			if(preg_match('#^[a-z]{2}-[A-Z]{2}:#', $value)){
				/** S'il n'existe pas déjà l'ajouter **/
				$language = explode(':', $value);
				$code = $language[0];
				
				if(count($language) > 2){
					array_shift($language);
					$lang_name = implode(':', $language);
				} else {
					$lang_name = $language[1];
				}
				
				if($lang_name === ''){
					$lang_name = $code;
				}
				
				if(!array_key_exists($code, $languages)){
					$code = explode('-', $code);
					$base = $code[0];
					$ext = $code[1];
					
					$new_lang = $xmllanguages->addChild("language", $lang_name);
					$new_lang->addAttribute('LANG-BASE', $base);
					$new_lang->addAttribute('LANG-EXT', $ext);
					$new_lang->addAttribute('LANG', implode('-', $code));
				}
				else {
					parent::throw_error(sprintf('Language "%s" with lang code "%s" already exist in "%s".', $lang_name, $code, parent::XML_CONF_FILE), E_USER_WARNING);
					return false;
				}
			} 
			else {
				parent::throw_error(sprintf('Argument supplied "%s" not valide. It must be like this lang-LANG:Lang_name. SYSLangCompilator::add_languages skip this argument.', $value), E_USER_WARNING);
				return false;
			}
		}
		
		/** Sauvegarder les modifications **/
		parent::save_xml($xmllanguages, $this->_files_repository."/".parent::XML_CONF_FILE);
		
		/** Mettre à jour la liste des langues disponible **/
		parent::list_languages();
		
		return true;
	}

    /**
     * Construit l'environnement de fonctionnement du moteur de langue SYSLang
     * @param string $working_directory
     * @param string $first_pack
     * @return bool
     */
	static function build_environnement($working_directory, $first_pack=null){
		/** #1. Gestion des arguments - Erreur si aucune paramètre fournis **/
		if($first_pack === null){
			parent::throw_error('Missing argument 2 ($first_pack) for SYSLangCompilator::build_environnement(). $first_pack will be set as default language pack.', E_USER_ERROR);
			return false;
		} else {
			$first_pack = explode(':', $first_pack);
			$first_pack = $first_pack[0];
			$packs = func_get_args();
			array_shift($packs);
		}
		
		/** #2. Création du dossier, s'il n'existe pas déjà **/
		if(!file_exists($working_directory."/Languages")){
			mkdir($working_directory."/Languages");
		}
		
		/** #3. Création du fichier languages.xml si n'existe pas **/
		if(!file_exists($working_directory.'/Languages/'.parent::XML_CONF_FILE)){
			$header = '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
			$openner = '<languages default="'.$first_pack.'">'.PHP_EOL;
			$closer = '</languages>';
			
			file_put_contents($working_directory.'/Languages/'.parent::XML_CONF_FILE, $header, FILE_APPEND);
			file_put_contents($working_directory.'/Languages/'.parent::XML_CONF_FILE, $openner, FILE_APPEND);
			
			/** Créer autant d'enregistrement qu'il y à de pack spécifié **/
			foreach($packs as $pkey => $pvalue){
				$pvalue = explode(':', $pvalue);
				$lang = explode('-', $pvalue[0]);
				
				$record = sprintf('	<language LANG-BASE="%s" LANG-EXT="%s" LANG="%s">%s</language>'.PHP_EOL, $lang[0], $lang[1], $pvalue[0], $pvalue[1]);
				
				file_put_contents($working_directory.'/Languages/'.parent::XML_CONF_FILE, $record, FILE_APPEND);
			}
			
			file_put_contents($working_directory.'/Languages/'.parent::XML_CONF_FILE, $closer, FILE_APPEND);
			
			
			/** #4. Création des packages (Générique) **/
			foreach($packs as $pkey => $pvalue){
				$pvalue = explode(':', $pvalue);
				
				if(!file_exists($working_directory.'/Languages/'.$pvalue[0])){
					mkdir($working_directory.'/Languages/'.$pvalue[0]);
				}
				
				/** Création d'un fichier de langue générique **/
				if(!file_exists($working_directory.'/Languages/'.$pvalue[0].'/generic.xml')){
					$header = '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
					$openner = '<resources>'.PHP_EOL;
					$record = '	<resource KEY="your_key_name_here" CST="false" SST="true">your_corresponding_text_here</resource>'.PHP_EOL;
					$closer = '</resources>';
					
					file_put_contents($working_directory.'/Languages/'.$pvalue[0].'/generic.xml', $header, FILE_APPEND);
					file_put_contents($working_directory.'/Languages/'.$pvalue[0].'/generic.xml', $openner, FILE_APPEND);
					file_put_contents($working_directory.'/Languages/'.$pvalue[0].'/generic.xml', $record, FILE_APPEND);
					file_put_contents($working_directory.'/Languages/'.$pvalue[0].'/generic.xml', $closer, FILE_APPEND);
				}
			}
			
			return true;
		} else {
			parent::throw_error(sprintf('File "%s" already exist in "%s". SYSLangCompilator::build_environnement done nothing. Use SYSLangCompilator::add_languages($lang) instead', parent::XML_CONF_FILE, $working_directory), E_USER_WARNING);
			return false;
		}
	}

    /**
     * Compile le ou les langues spécifiées au format xx-XX
     * @param string $packages Pack de langue à compiler
     * @return bool
     */
	public function compile($packages=null){
		/** La compilation n'est possible que si le package de référence est spécifié **/
		if($this->_ref_package !== null){
			/** Controler l'existance du package **/
			if(file_exists($this->_files_repository.'/'.$this->_ref_package)){
				/** #1. Lire le fichier languages.xml **/
				$avail_languages = $this->get_avail_languages();
				$avail_languages = $avail_languages['KEYS'];
				
				/** #2. Lister les packages à compiler **/
				$packages_to_compile = Array();
				
				/** Si $package n'est pas null, alors lister les package de destination **/
				if($packages !== null){
					/** Sauvegarder les packages selon s'ils sont enregistré **/
					$packages = func_get_args();
					
					foreach($packages as $key => $value){
						if(array_key_exists($value, $avail_languages)){
							$packages_to_compile[$value] = $avail_languages[$value];
						}
					}
				} 
				else {
					$packages_to_compile = $avail_languages;
				}
				
				/** #3. Obtenir le rapport MD5 de controle **/
					// #3.1. Vérifier que le fichier existe, sinon le créer
					if(!file_exists($this->_files_repository.'/'.self::MD5_FILE_NAME)){
						file_put_contents($this->_files_repository.'/'.self::MD5_FILE_NAME, '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL, FILE_APPEND);
						file_put_contents($this->_files_repository.'/'.self::MD5_FILE_NAME, '<packs>'.PHP_EOL, FILE_APPEND);
						file_put_contents($this->_files_repository.'/'.self::MD5_FILE_NAME, '</packs>'.PHP_EOL, FILE_APPEND);
					}
					
					// #3.2. Récupération du rapport MD5
					$this->_MD5_report = self::SXEOverhaul(file_get_contents($this->_files_repository.'/'.self::MD5_FILE_NAME));
					
					// #3.3. Vérifier qu'un rapport MD5 pour le pack de référence existe
					$sxe_name = $this->_ref_package;
					
					if(!isset($this->_MD5_report->$sxe_name)){
						$this->_MD5_report->addChild($sxe_name);
					}
					
					$this->_MD5_package_report = $this->_MD5_report->$sxe_name;
				
				/** #4. Lancer la compilation **/
				// RAZ pour execution multiple de compile par la meme instance
				$this->_ref_packages_keys = Array();
				$this->_ini_files_code = Array();
				$this->_ini_keys_code = Array();
				$this->_ini_texts = Array();
				$this->_ini_sxe_resources = Array();
				
				$this->resources_builder($packages_to_compile);
				
				/** #5. Sauvegarder le rapport MD5 **/
				parent::save_xml($this->_MD5_report, $this->_files_repository.'/'.self::MD5_FILE_NAME);
				return true;
			} 
			else {
				parent::throw_error('The reference package "'.$this->_ref_package.'" does not exist.', E_USER_ERROR);
				return false;
			}
		} 
		else {
			parent::throw_error('Can not compile because the reference package is not defined. Use SYSLangCompilator->set_ref_language($package="xx-XX") first.', E_USER_ERROR);
			return false;
		}
	}

    /**
     * Contrôle l'existance de l'environnement de fonctionnement de SYSLang
     * @param $working_directory
     */
    static function environnement_exists($working_directory){
		
	}

    /**
     * Exporte l'ensemble des textes de(s) langue(s) spécifiée(s)
     * @param array $lang Liste des packes de langue à exportée
     * @param bool $complete Indique si l'on exporte complétement le pack où si on exporte un différentiel
     * @return bool
     */
	public function ini_export($lang=array(), $complete=false){
		/** Récupération des languages disponible **/
		$languages = $this->get_avail_languages();
		$languages = $languages['KEYS']; 
		
		/** Controler les langues à extraire **/
		$langs_to_export = Array();
		if(count($lang) > 0){
			foreach($lang as $lkey => $lvalue){
				if(array_key_exists($lvalue, $languages)){
					$langs_to_export[$lvalue] = $lkey;
				} else {
					parent::throw_error(sprintf('The argument supplied "%s" not found in "%s". Argument is ignored.', $lvalue, parent::XML_CONF_FILE), E_USER_WARNING);
				}
			}
		}
		else {
			$langs_to_export = $languages;
		}
		
		
		/**
		
			Si une seule langue >>> Fichier ini
			Si plusieurs langue >>> Zip
			
			>>> lang+keys
			
			L'import doit identifier l'extension
			
			A l'importe, on ne créer pas de balise, si elle n'existe pas alors fausse manipe quite à crypter le manifest
			
			un fichier ini par langue :
				fr-FR.ini
					file = code
					key = code
					
					permet d'avoir plusieurs clé de meme nom / meme code mais pas au mm fichier
				
		
		
		__MANIFEST__
		[PACKS_CODE]
		001 = dsfsdf
		
		[FILES_CODE]
		001 = sdfsdfsdf
		
		[KEYS_CODE]
		00001 = fdsfsdf
		
		
		un fichier ini par langue
		et  regroupé les clé donc un fichier manifest fichier/clé = code
		fr-FR
			file
				xxx:yyy:zzzzz = texte
				
		**/
		/** Executer la lecture des dossier de langues **/
		
		/** Création du dossier temporaire **/
		//mkdir($this->_workspace.'/'.$this->_run_instance);
		
		foreach($langs_to_export as $xkey => $xvalue){
			/** RAZ des variables **/
			$this->_ini_files_code = Array();
			$this->_ini_keys_code = Array();
			$this->_ini_texts = Array();
			$this->_ini_sxe_resources = Array();
			
			$this->ini_read_folder($xkey, $complete);
			
			//echo "Files Codes :";
			//print_r($this->_ini_files_code);
			//echo "Keys Codes :";
			//print_r($this->_ini_keys_code);
			//echo "Texts :";
			//print_r($this->_ini_texts);
			//exit();
			// Meme si pas de text, genere le fichier, que les balise
			
			/** Ecritures des fichiers INI **/
			foreach($this->_ini_texts as $lkey => $lvalue){
				/** Déterminer le chemin vers le fichier de destination "xx-XX.ini" **/
				$file_path = $this->_export_folder_path."/$lkey.ini";
				
				if(!file_exists($this->_export_folder_path)){
					mkdir($this->_export_folder_path, 0775, true);
				}
				
				/** Enregistrement des entêtes **/
				file_put_contents($file_path, "[HEADERS]");
				file_put_contents($file_path, PHP_EOL."lang = $lkey", FILE_APPEND);
				
				/** Enregistrement des Codes FILES **/
				file_put_contents($file_path, PHP_EOL.PHP_EOL.'[FILES]', FILE_APPEND); // Debut section des codes fichiers
				
				foreach($this->_ini_files_code as $fkey => $fvalue){
					file_put_contents($file_path, PHP_EOL.sprintf("%03d = %s", $fvalue, $fkey), FILE_APPEND);
				}
				
				/** Enregistrement des Codes KEYS **/
				file_put_contents($file_path, PHP_EOL.PHP_EOL.'[KEYS]', FILE_APPEND); // Debut section des codes fichiers
				
				foreach($this->_ini_keys_code as $kkey => $kvalue){
					file_put_contents($file_path, PHP_EOL.sprintf("%05d = %s", $kvalue, $kkey), FILE_APPEND);
				}
				
				/** Ecritures des textes TEXTS **/
				file_put_contents($file_path, PHP_EOL.PHP_EOL.'[TEXTS]', FILE_APPEND); // Debut section des textes (Sans FILE_APPEND, permet le reset)
				
				/** Saisie des textes **/
				foreach($lvalue as $tkey => $tvalue){
					file_put_contents($file_path, PHP_EOL.$tvalue, FILE_APPEND);
				}
			}
		}
		
		return true;
	}

    /**
     * Importe les fichiers de langue INI dans l'environnement de fonctionnement SYSLang
     * @param bool $finalise Indique si on "commit", donc sauvegarde et met à jour le référentiel MD5
     * @return bool
     */
	public function ini_import($finalise=false){
		/** Scanner le dossier **/
		if(file_exists($this->_import_folder_path)){
			$to_import = scandir($this->_import_folder_path);
			
			/** Parcourir les fichiers trouvé **/
			foreach($to_import as $fkey => $fvalue){
				/** S'assurer qu'il s'agit bien d'un fichier **/
				if(is_file($this->_import_folder_path."/".$fvalue)){
					/** RAZ des variables **/
					$this->_ini_files_code = Array();
					$this->_ini_keys_code = Array();
					$this->_ini_texts = Array();
					$this->_ini_sxe_resources = Array();
					
					/** Ouvrir le fichier **/
					$ini = fopen($this->_import_folder_path."/".$fvalue, "r");
					
					/** Lire le fichier pour temporisé les données **/
					$balises = Array("HEADERS", "FILES", "TEXTS", "KEYS");
					$found = array_fill_keys($balises, false);
					$package = null; // Package correspondant
					
					while($buffer = fgets($ini)){
						/** Recherche des balise ini SYSLang **/
						$continue = false;
						
						foreach($balises as $bkey => $bname){
							if(preg_match("#^\[$bname\]$#i", $buffer)){
								$process = strtolower($bname);
								$$process = true;
								$continue = true;
								$found[$bname] = true;
								break;
							}
						}
						
						/** Si Balise trouvée ou si ligne vide alors suivant **/
						if($continue || preg_match("#^\s*$#i", $buffer)) continue;
						
						/** Découper l'entrée **/
						$buffer = preg_split("#\s*=\s*#i", $buffer, 2);
						$key = $buffer[0];
						$value = $buffer[1];
						$value = preg_replace("#\s*$#i", "", $value);
						
						switch($process){
							/** Traitement de l'entête **/
							case "headers":
								// Recherche du package correspondant 
								if($key === 'lang'){
									$package = $value;
								}
							break;
							
							/** Traitement des codes fichiers **/
							case "files":
								$this->_ini_files_code[$key] = $value;
							break;
							
							/** Traitements des codes de clé **/
							case "keys":
								$this->_ini_keys_code[$key] = $value;
							break;
							
							/** Traitements des texts **/
							case "texts":
								$this->_ini_texts[$key] = $value;
							break;
							
							/** Si aucun process et pas une balise, on skip **/
							default:
								continue;
							break;
						}
					}
					
					
					/** Mise à jour du package si les données collectées sont consistente **/
					if(!in_array(false, $found) && $package !== null){
						/** Parcourir les fichiers et ouvrir avec SXE **/
						foreach($this->_ini_files_code as $fkey => $file){
							$this->_ini_sxe_resources[$fkey] = Array(
								"path" => $this->_files_repository."/$package/$file",
								"resources" => self::SXEOverhaul(file_get_contents($this->_files_repository."/$package/$file")),
								"indexs" => Array()
							);
							
							// Indexation des clés
							$index = 0;
							foreach($this->_ini_sxe_resources[$fkey]["resources"] as $rkey => $resource){
								$i_sxe_key = strval($resource->attributes()->KEY);
								
								$this->_ini_sxe_resources[$fkey]["indexs"][$i_sxe_key] = $index;
								$index++;
							}
						}
						
						/** Parcourir les textes **/
						foreach($this->_ini_texts as $tkey => $text){
							/** Découper le code **/
							$code = preg_split("#\.#i", $tkey);
							$file_code = $code[0];
							$key_code = $code[1];
							
							/** Déterminer l'index correspondant **/
							$index = $this->_ini_sxe_resources[$file_code]["indexs"][$this->_ini_keys_code[$key_code]];
							
							/** mise à jour **/
							$this->_ini_sxe_resources[$file_code]["resources"]->resource[$index] = $text;
							if($finalise) $this->_ini_sxe_resources[$file_code]["resources"]->resource[$index]->attributes()->TIR = "false";
						}
						
						/** Sauvegarder les fichiers **/
						foreach($this->_ini_sxe_resources as $sxe_key => $sxe){
							parent::save_xml($sxe["resources"], $sxe["path"]);
						}
					} else {
						// Erreur Warning, N'interrompt pas le traitement des autres fichiers
						parent::throw_error(sprintf("File '%s' can't be import.", $fvalue), E_USER_WARNING);
					}
				}
			}
			
			return true;
		} else {
			parent::throw_error(sprintf("Import folder '%s' not exist.", $this->_import_folder_path), E_USER_ERROR);
			return false;
		}
	}

    /**
     * Lecture récursive des fichiers XML pour obtenir les codes et texte pour l'exportation INI
     * @param string $active_language Langue de référence pour procédé au référencement.
     * @param bool $complete Indique si on référence tout ou seulement les modifications depuis la dernière importation
     * @param string $subfolder
     */
	private function ini_read_folder($active_language, $complete=false, $subfolder=null){
		//$lang_path = $this->_workspace.'/Languages/'.$active_language;
		$lang_path = $this->_files_repository.'/'.$active_language;
		$full_path = ($subfolder !== null) ? ($lang_path.'/'.$subfolder) : ($lang_path);
		$files = scandir($full_path);
		
		/** Parcourir les fichiers **/
		foreach($files as $fkey => $fvalue){
			$file_path = ($subfolder !== null) ? ($subfolder.'/'.$fvalue) : ($fvalue);
			
			/** Ne pas traité les fichiers commencant par un . **/
			if(!preg_match('#^\.#', $fvalue)){
				/** S'il s'agit d'un dossier, on le scan à son tour **/
				if(is_dir($full_path.'/'.$fvalue)){
					$this->ini_read_folder($active_language, $complete, $file_path);
				}
				/** Sinon, vérifier que c'est un fichier XML **/
				if(preg_match('#\.xml$#i', $fvalue)){
					/** Chercher l'existance du fichier dans la liste de référence de fichier **/
					if(!array_key_exists($file_path, $this->_ini_files_code)){
						$this->_ini_files_code[$file_path] = count($this->_ini_files_code);
					}
					$file_code = $this->_ini_files_code[$file_path];
					
					/** Ouvrir le fichier XML **/
					$xml = self::SXEOverhaul(file_get_contents($full_path.'/'.$fvalue));
					
					
					/** Parcourir le fichier **/
					foreach($xml as $xmlkey => $xmlvalue){
						$attr = $xmlvalue->attributes();
						$tir = strtolower(strval($xmlvalue->attributes()->TIR));
						$tir = ($tir === 'true' || !isset($attr['TIR'])) ? true : false;
						
						/** Si complete, tout extraire, sinon seul les TIR true **/
						if($complete || $tir){
							$key = strval(html_entity_decode($xmlvalue->attributes()->KEY));
							$value = strval($xmlvalue);
							
							/** Vérifier la présence de la clé dans la liste de référence des key **/
							if(!array_key_exists($key, $this->_ini_keys_code)){
								$this->_ini_keys_code[$key] = count($this->_ini_keys_code);
							}
							$key_code = $this->_ini_keys_code[$key];
							
							$line = sprintf('%03d.%05d = %s', $file_code, $key_code, $value);
							
							$this->_ini_texts[$active_language][] = $line;
						}
					}
					
					$xml = null;
				}
			}
		}
	}

    /**
     * Supprime le(s) pack(s) de langue(s) spécifié(s)
     * @param bool$preserve_files Indique si l'on concerve les fichiers ou non
     * @param null $package
     */
    public function remove_languages($preserve_files, $package=null){
		
	}

    /**
     * Construction récursive des fichiers de ressources XML
     * @param array $target_packages Liste des packages de langue désiré
     * @param null $folder_path
     * @return bool
     */
	private function resources_builder($target_packages, $folder_path=null){
		/** #1. Définition du chemin vers le dossier à lire **/
		$full_path = $this->_files_repository.'/'.$this->_ref_package;
		$full_path .= ($folder_path !== null) ? ('/'.$folder_path) : ('');
		
		/** #2. Lecture du dossier **/
		$folder = scandir($full_path);
		
		/** #3. Parcourir le dossier **/
		foreach($folder as $fkey => $file){
			/** ne pas traiter les dossiers et fichier commencant pas . **/
			if(!preg_match('#^\.#', $file)){
				/** S'il s'agit d'un dossier, alors entrer en récursivité **/
				if(is_dir($full_path.'/'.$file)){
					$this->resources_builder($target_packages, $folder_path.'/'.$file);
				} 
				/** Sinon traiter le fichier **/
				else {
					/** Effectuer les vérifications d'usage : XML FILE **/
					if(preg_match('#\.xml$#i', $file)){
						/** Controler que le contenu est du contenu XML valide **/
						try {
							$resources = self::SXEOverhaul(file_get_contents($full_path.'/'.$file));
							/** Lister les clées du pack de référence **/
							$ref_keys = Array();
							$ref_keys_values = Array();
							$index = 0;
							
							foreach($resources as $res_key => $res_value){
								/** Ajouter la clé si elle n'existe pas déjà **/
								$key = strval($res_value->attributes()->KEY);
								$s_key = 'K_'.$key;
								
								if(!array_key_exists($s_key, $ref_keys)){
									$ref_keys[$s_key] = $index;
									$ref_keys_values[$s_key] = $res_value;
								}
								/** Dans le cas contraire, il y à un soucis, on ne sais pas quelle ressource manipuler **/
								else {
									parent::throw_error('The key "'.$key.'" in "'.($folder_path.'/'.$file).'" is already used', E_USER_ERROR);
									return false;
								}
								
								/** Enregistrer la clé auprès de la base pour controler les doublons (non bloquant) **/
								if(array_key_exists($s_key, $this->_ref_packages_keys)){
									parent::throw_error('The key "'.$key.'" in "'.($folder_path.'/'.$file).'" is already used in file(s) '.implode(', ', $this->_ref_packages_keys[$s_key]).' [Ref package : '.$this->_ref_package.']', E_USER_WARNING);
									$this->_ref_packages_keys[$s_key][] = $file;
								} else {
									$this->_ref_packages_keys[$s_key] = Array();
									$this->_ref_packages_keys[$s_key][] = $file;
								}
								
								$index++;
							}
							
							/** Récupérer l'ensemble MD5 correspondant au fichier **/
							$MD5_file_name = preg_replace('#\/#', '-', $folder_path.'/'.$file);
							$MD5_file_name = 'file'.$MD5_file_name;
							
							if(!isset($this->_MD5_package_report->$MD5_file_name)){
								$this->_MD5_package_report->addChild($MD5_file_name);
							}
							
							$MD5_file_report = $this->_MD5_package_report->$MD5_file_name;
							
							/** Lister les clée connu et le MD5 - Elle dispose déjà du suffixe de sécurisation **/
							$rep_keys = Array();
							$rep_keys_values = Array();
							$index = 0;
							
							foreach($MD5_file_report->hash as $rep_key => $rep_value){
								$rkey = strval($rep_value->attributes()->KEY);
								
								$rep_keys[$rkey] = $index;
								$rep_keys_values[$rkey] = $rep_value;
								
								$index++;
							}
							
							/** Controler les MD5 des fichiers connu **/
							$keys_to_control = array_intersect_key($ref_keys, $rep_keys);
							$keys_to_update = Array();
							
							foreach($keys_to_control as $ckey => $cvalue){
								$ref_value_cst = strval($ref_keys_values[$ckey]->attributes()->CST);
								$ref_value_sst = strval($ref_keys_values[$ckey]->attributes()->SST);
								$ref_value = strval($ref_keys_values[$ckey]);
								
								$ref_value_hash = md5($ref_value_cst.'::'.$ref_value_sst.'::'.$ref_value);
								
								if($ref_value_hash !== strval($rep_keys_values[$ckey])){
									$keys_to_update[$ckey] = $cvalue; 
								}
							}
							
							//echo "REF KEYS :: "; print_r($ref_keys);
							//echo "MD5 KEYS :: "; print_r($rep_keys);
							//echo "INTERSECT KEY(REF,MD5) :: "; print_r($keys_to_control);
							//echo "!= MD5 KEYS :: "; print_r($keys_to_update);
							
							/** Parcourir les packages à construire **/
							foreach($target_packages as $pkey => $pvalue){
								/** Lorsqu'aucun package n'est spécifié, tous sont analysé, y compris celui de référence. Si c'est le cas on ignore **/
								if($pkey === $this->_ref_package) continue;
								
								//$target_package_path = $this->_files_repository.'/'.$pvalue.'/';
								$target_package_path = $this->_files_repository.'/'.$pkey;
								$target_package_path .= ($folder_path !== null) ? ($folder_path) : ('');
								
								/** Si l'emplacement n'existe pas, alors on le créer **/
								if(!file_exists($target_package_path)){mkdir($target_package_path, 0775, true);}
								
								/** Si le fichier XML n'existe pas on le créer **/
								if(!file_exists($target_package_path.'/'.$file)){
									file_put_contents($target_package_path.'/'.$file, '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL, FILE_APPEND);
									file_put_contents($target_package_path.'/'.$file, '<resources>'.PHP_EOL, FILE_APPEND);
									file_put_contents($target_package_path.'/'.$file, '</resources>', FILE_APPEND);
								} 
								
								
								/** Ouvrir le fichier **/
								//$target_resources = new SimpleXMLElement(file_get_contents($target_package_path.'/'.$file));
								$target_resources = self::SXEOverhaul(file_get_contents($target_package_path.'/'.$file));
								$target_keys = Array();
								//$target_keys_values = Array();
								$index = 0;
								
								/** Lister les clées du pack cible **/
								foreach($target_resources as $tres_key => $tres_value){
									$tkey = strval($tres_value->attributes()->KEY);
									$s_tkey = 'K_'.$tkey;
									
									$target_keys[$s_tkey] = $index;
									//$target_keys_values[$s_tkey] = $tres_value; 
									
									$index++;
								}
								
								//echo "TARGET KEYS :: "; print_r($target_keys);
								
								/** Procéder au traitement **/
									// #1.1. Chercher les nouvelles clées
									$news_keys = array_diff_key($ref_keys, $target_keys);
									
									// #1.2. Ajouter les nouvelles clée
									foreach($news_keys as $nkey => $nval){
										/** Récupération de l'élément à copier **/
										$node = $ref_keys_values[$nkey];
										
										/** SimpleXMLElement ne permet pas l'ajout par copy **/
										$clone_node = $target_resources->addChild('resource', strval($node));
										
										/** Parcourir les attributes **/
										foreach($node->attributes() as $akey => $avalue){
											$clone_node->addAttribute(strval($akey), strval($avalue));
										}
										
										/** Translate Is Required**/
										$attr = $clone_node->attributes();
										
										if(isset($attr["TIR"])){
											$clone_node->attributes()->TIR = "true";
										} else {
											$clone_node->addAttribute('TIR', 'true'); 
										}
									}
									
									// #2.2.1 Chercher les clés inconnues -> ???
										//$unknow_keys = array_intersect_key($ref_keys, $target_keys); echo "INTERSECT REF,TARGET :: "; print_r($unknow_keys);
										//$unknow_keys = array_diff_key($unknow_keys, $keys_to_control); echo "DIFF UNKNOW,(REF,MD5)"; print_r($unknow_keys);
										//$unknow_keys = array_diff_key($unknow_keys, $keys_to_control); echo "DIFF UNKNOW,(REF,MD5)"; print_r($unknow_keys);
										//$target_keys_to_update = array_merge($unknow_keys, $keys_to_update);
										
									// #2.2.2. Chercher les clés disparues :: Impossible de mettre à jour une clé modifié si dans la cible elle n'existe pas. (supprimée accidentellement)
										$target_keys_to_update = array_intersect_key($target_keys, $keys_to_update);//echo "TARGET KEY TO UPDATE :: ";print_r($target_keys_to_update);
										
									// #2.3. Parcourir les clées à mettre à jour pour maintenir le fichier 
									foreach($target_keys_to_update as $ukey => $uvalue){//si cle update mais n"existe pas ....
										$target_index = $target_keys[$ukey];
										
										$new_value = strval($ref_keys_values[$ukey]);
										$new_cst = strval($ref_keys_values[$ukey]->attributes()->CST);
										$new_sst = strval($ref_keys_values[$ukey]->attributes()->SST);
										
										$target_resources->resource[$target_index]->attributes()->CST = $new_cst;
										$target_resources->resource[$target_index]->attributes()->SST = $new_sst;
										$target_resources->resource[$target_index]->attributes()->TIR = "true";
										$target_resources->resource[$target_index] = $new_value;
									}
										
									// #3.1. Chercher les clées supprimées
									$deleted_keys = array_diff_key($target_keys, $ref_keys);
									
									// #3.2. Supprimer les clée (de la fin vers le début)
									foreach(array_reverse($deleted_keys) as $dkey => $dval){
										unset($target_resources->resource[$dval]);
									}
								
								/** Sauvegarder les modifications (à la fin lorsque plus aucune erreur est possible) **/
								//$target_resources->asXML($target_package_path.'/'.$file);
								parent::save_xml($target_resources, $target_package_path.'/'.$file);
							}
							
							/** Enregistrer le rapport MD5 du fichier parcouru **/
								// Créer des nouveau enregistrement pour les clée inconnues
								$hashs_to_insert = array_diff_key($ref_keys, $rep_keys);
								
								foreach($hashs_to_insert as $hikey => $hivalue){
									/** Calcule du hash **/
									$ref_value_cst = strval($ref_keys_values[$hikey]->attributes()->CST);
									$ref_value_sst = strval($ref_keys_values[$hikey]->attributes()->SST);
									$ref_value = strval($ref_keys_values[$hikey]);
									
									$ref_value_hash = md5($ref_value_cst.'::'.$ref_value_sst.'::'.$ref_value);
									
									$child = $MD5_file_report->addChild('hash', $ref_value_hash);
									$child->addAttribute('KEY', $hikey);
								}
							
								// Mettre à jour, celle qui reste
								$hashs_to_update = $keys_to_update;
							
								foreach($hashs_to_update as $hikey => $huvalue){
									/** Récupération de l'élément à scanner **/
									/** Calcule du hash **/
									$ref_value_cst = strval($ref_keys_values[$hikey]->attributes()->CST);
									$ref_value_sst = strval($ref_keys_values[$hikey]->attributes()->SST);
									$ref_value = strval($ref_keys_values[$hikey]);
									
									$ref_value_hash = md5($ref_value_cst.'::'.$ref_value_sst.'::'.$ref_value);
									
									$rep_index = $rep_keys[$hikey];
									
									$MD5_file_report->hash[$rep_index] = $ref_value_hash;
								}
							
								// Supprimer les clées qui n'existent plus 
								$hashs_to_delete = array_diff_key($rep_keys, $ref_keys);
							
								foreach(array_reverse($hashs_to_delete) as $hdkey => $hdvalue){
									$hash_index = $rep_keys[$hdkey];
									unset($MD5_file_report->hash[$hash_index]);
								}
						}
						/** Le fichier XML n'est pas valide et on ne peux pas le traiter **/
						catch (Exception $e) {
							parent::throw_error('The XML file "'.$file.'" in "'.($folder_path.'/'.$file).'" can not be parsed : '.$e->getMessage(), E_USER_ERROR);
							return false;
						}
					} 
					else {
						parent::throw_error('The file "'.$file.'" is not a XML file.', E_USER_WARNING);
						return false;
					}
				}
			}
		} // END_FOREACH_READ_FOLDER
		
		return true;
	}
	
	/** ----------------------------------------------------------------------------------------------- **
	/** --- Méthode de transcription des balise CDATA pour une gestion totale sous SimpleXMLElement --- **
	/** ----------------------------------------------------------------------------------------------- **/

    /**
     * Transcrit les balies CDATA pour une gestion totale sous SimpleXMLElement
     * @param string $xml_str
     * @return SimpleXMLElement
     */
	static function SXEOverhaul($xml_str){
		// Convertir les balises CDATA en [[ ]]
		$xml_str = preg_replace("#<!\[CDATA\[\s*(.*)\s*\]\]>#m", parent::CDATA_STR_START."$1".parent::CDATA_STR_END, $xml_str);
		
		// Remplacer toutes les balise HTML qui se trouve dans une balise CDATA
		$xml_str = preg_replace_callback("#(?<=\[\[).*(?=\]\])#mi", function($matches){
			$return = $matches[0];
			$return = str_replace("<", "&lt;", $return);
			$return = str_replace(">", "&gt;", $return);
			return $return;
		}, $xml_str);
		
		// Remplacer toutes les entités de caractère numérique
		$xml_str = preg_replace("#&(\#?)([a-zA-Z0-9]+);#m", "::$1$2::", $xml_str);
		
		return new SimpleXMLElement($xml_str);
	}
}
?>