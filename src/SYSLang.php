<?php
/**
 * File :: SYSLang.php
 *
 * Fournis une classe de gestion de package de lang
 *
 * @author    Nicolas DUPRE
 * @release   19/08/2017
 * @version   1.1.1
 * @package   SYSLang
 */
namespace SYSLang;

/**
 * Class SYSLang
 * @package SYSLang
 */
class SYSLang {
    /**
     * Fichier de configuration du moteur SYSLang
     */
	const XML_CONF_FILE = 'languages.xml';

    /**
     * Balises CDATA pour le format de fichier INI (STR)
     * Modèle de rechercher RegExp correspondant (REG)
     */
	const CDATA_STR_START = "[[";
	const CDATA_REG_START = "\[\[";
	const CDATA_STR_END = "]]";
	const CDATA_REG_END = "\]\]";

    /**
     * @var arrat $_avail_languages Liste des langues disponible
     */
	protected $_avail_languages = null;

    /**
     * @var string $_files_repository Dossier de dépot des packs de langue
     */
	protected $_files_repository = null;

    /**
     * @var string $_user_language Liste des langues acceptées par le navigateur de l'utilisateur
     */
	protected $_user_language = null;

    /**
     * @var string $_default_language Langue par défaut
     */
	protected $_default_language = null;

    /**
     * SYSLang constructor.
     * @param string $directory Environnement de fonctionnement
     */
	function __construct($directory=null){
		/** Définition du dossier des pack de langues **/
		if($directory === null){
			$this->_files_repository = __DIR__;
		} else {
			$this->_files_repository = $directory;
		}
			
		/** Lister les pack de langue disponible et identifier le pack par défaut **/
		$this->list_languages();
		
		/** Si la langue n'est pas définie à l'aide d'une session, alors à la création, la valeur est celle de l'utilisateur **/
		if(!isset($_COOKIE['SYSLang_LANG'])){
			$this->get_user_language();
			setcookie('SYSLang_LANG', $this->_user_language, time()+365*24*60*60, '/');
		} else {
			//$this->_user_language = $_COOKIE['SYSLang_LANG'];
			$this->set_lang($_COOKIE['SYSLang_LANG']);
		}
	}

    /**
     * SYSLang destructor
     */
	function __destruct(){
		
	}

    /**
     * Affiche les langues disponible dans l'application
     * @return array Liste des langues disponible
     */
	public function get_avail_languages(){
		return $this->_avail_languages;
	}

    /**
     * Affiche la langue définie
     * @return string Langue définie
     */
	public function get_lang(){
		return $this->_user_language;
	}

    /**
     * Récupère la langue du système utilisateur
     * @return void
     */
	private function get_user_language(){
		/** Connexion aux variables globales **/
  			global $_SERVER;	// Superglobale Server
		
		/** Déclaration des variables **/
			$accepted_languages;	// Language admis par le navigateur 
			$user_languages;		// Language utilisateur determiné
			$matches;				// Résultat des occurences trouvées
		
		/** Initialisation **/
			$user_languages = Array();
		
		/** Traitement des languages admis **/
			// CHROME : Array ( [0] => fr-FR [1] => fr;q=0.8 [2] => en-US;q=0.6 [3] => en;q=0.4 ) 
			// FIREFO : Array ( [0] => fr [1] => fr-FR;q=0.8 [2] => en-US;q=0.5 [3] => en;q=0.3 ) 
			// OPERA  : Array ( [0] => fr-FR [1] => fr;q=0.8 [2] => en-US;q=0.6 [3] => en;q=0.4 ) 
			// SAFARI : Array ( [0] => fr-FR )
			// IE     : Array ( [0] => fr-FR )
			//
			// Pattern de recherche [a-z]{2}-[A-Z]{2}
			if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
				$accepted_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
				$user_languages = Array();
				foreach($accepted_languages as $key => $value){
					if(preg_match('#^[a-z]{2}-[A-Z]{2}#', $value)){
						preg_match_all('#^[a-z]{2}-[A-Z]{2}#', $value, $matches);
						if(!array_key_exists($matches[0][0], $user_languages)){
							$user_languages[] = $matches[0][0];
						}
					}
				}
			}
		
		/** Finalisation - Choisir la langue **/
			/** Si aucune langue n'à été trouvé, alors choisir la langue par défault définie **/
			if(count($user_languages) <= 0){
				$this->_user_language = $this->_default_language;
			}
			/** Sinon vérifier la disponibilité des langues admise **/
			else {
				$setted = false;
				
				foreach($user_languages as $key => $value){
					if(array_key_exists($value, $this->_avail_languages['KEYS'])){
						$this->_user_language = $value;
						$setted = true;
						break;
					}
				}
				
				/** Si aucune langue n'est disponible (not setted), alors c'est la langue par défaut **/
				if(!$setted){
					$this->_user_language = $this->_default_language;
				}
			}
			
		/** Mise à jour de la langue définie (durablement) **/
		//setcookie('SYSLang_LANG', $this->_user_language, time()+365*24*60*60, '/');
	}

    /**
     * Définie la langue souhaitée
     * @param string|null $lang Langue désirée
     */
	public function set_lang($lang=null){
		/** Si $lang = null, alors cela revient à demander d'utiliser la langue d'origine (si disponible) **/
		if($lang === null || !array_key_exists($lang, $this->_avail_languages['KEYS'])){
			$this->get_user_language();
		} else {
			/** Mise à jour de la langue définie (localement) **/
			$this->_user_language = $lang;
			/** Mise à jour de la langue définie (durablement) **/
			setcookie('SYSLang_LANG', $lang, time()+365*24*60*60, '/');
		}
	}

    /**
     * Génère un message d'erreur présenté en HTML
     * @param string $message Message à afficher
     * @param int $error_level Niveau d'erreur souhaité
     */
	static function throw_error($message='', $error_level=E_USER_NOTICE){
		/** Taille manixmal des sorties : 1024 bit **/
		$traces = debug_backtrace();
		$backtrace_message = null;
		
		/** Commencer à 1 afin de ne pas tenir compte de la trace de throw_error **/
		for($i = (count($traces) - 1); $i > 0; $i--){
			$trace = $traces[$i];
			$class = $trace['class'];
			$function = $trace['function'];
			$file = $trace['file'];
			$line = $trace['line'];
			
			$trace_message = "<b style='color: #336699;'>$class->$function</b> in <b>$file</b> on line <b style='color: #336699'>$line</b>";
			
			$backtrace_message .= "<br /><b> • FROM  ::</b> $trace_message;";
		}
		
		$backtrace_message = "<b>BACKTRACE ::</b> $backtrace_message";
		
		/** Utiliser un PRE pour l'affichage soigné, mais sécurisé la dispo HTML si l'operateur de control d'erreur @ est utilisé **/
		echo "<pre style='margin: 0 !important; padding: 0 !important; display: inline !important;'>";
		trigger_error("$backtrace_message", E_USER_NOTICE);
		
		// ln = 63 > <b>MESSAGE ::</b>\n • ERROR ::</b> <b style='color: red;'>$message</b>
		if(mb_strlen($message) > (1024 - 56)){
			$message = substr($message, 0, (1024 - 63 - 5));
			$message .= '(...)';
		}
		
		trigger_error("<b>MESSAGE ::</b>\n • ERROR ::</b> <b style='color: red;'>$message</b>", $error_level);
		echo "</pre>";
	}

    /**
     * Récupére l'ensemble des textes d'une langue depuis les fichiers XML
     * @param string $lang_files Nom du fichier à extraite (autant d'argument que désirée)
     * @return array
     */
	public function unpack($lang_files){
		/** Récupérer tout les fichiers donnée en paramètre (1 minimum) **/
		$lang_files = func_get_args();
		
		/** Initialiser les tableaux de sortie **/
		$client_side_target = Array();
		$server_side_target = Array();
		
		foreach($lang_files as $key => $file){
			if($file !== ''){
				/** Chemin complet vers le fichier **/
				$path = $this->_files_repository.'/'.$this->_user_language.'/'.$file;
				
				/** Vérifier que le fichier existe **/
				if(file_exists($path)){
					/** Ouvrir le fichier demandé **/
					$content = file_get_contents($path);
					
					/** Parser le contenu (XML) **/
					$resources = new SimpleXMLElement($content);
					
					/** Parcourir les ressources **/
					for($i = 0; $i < count($resources); $i++){
						/** Extraction des attributs **/
						$key = strval($resources->resource[$i]->attributes()->KEY);
						$cst = strval($resources->resource[$i]->attributes()->CST);
						$sst = strval($resources->resource[$i]->attributes()->SST);
						$value = strval($resources->resource[$i]);
						
						if($cst === 'true'){
							$client_side_target[] = Array("VAR_KEY" => $key, "VAR_VALUE" => addslashes($value));
						}
						
						if($sst === 'true'){
							$server_side_target[$key] = $value;
						}
					}
				} else {
					$this->throw_error('File "'.$file.'" not found in "'.($this->_files_repository.'/'.$this->_user_language.'/').'".', E_USER_WARNING);
				}
			}
		}
		
		/** > Renvoyer les données triée **/
		return Array("Client" => $client_side_target, "Serveur" => $server_side_target);
	}

    /**
     * Scan et mémorise les langues disponible pour l'application
     */
	protected function list_languages(){
		/** Chercher l'existance du fichier "languages.xml" **/
		//if(file_exists($this->_files_repository.'/languages.xml')){
		if(file_exists($this->_files_repository.'/'.self::XML_CONF_FILE)){
			/** Charger le fichier **/
			//$languages = new SimpleXMLElement(file_get_contents($this->_files_repository.'/languages.xml'));
			$languages = new SimpleXMLElement(file_get_contents($this->_files_repository.'/'.self::XML_CONF_FILE));
			
			
			/** Lister les languages **/
			for($i = 0; $i < count($languages); $i++){
				
				if(gettype($languages->language[$i]->attributes()->LANG) !== 'NULL'){
					/** Enregistrement de la langue dans la liste en tant que tableau de donnée fonctionnelle **/
					$keys[strval($languages->language[$i]->attributes()->LANG)] = strval($languages->language[$i]);
					
					/** Enregistrement de la langue dans la liste en tant que tableau de donnée **/
					$list[] = Array(
						"LANG_NAME" => strval($languages->language[$i]),
						"LANG_KEY" => strval($languages->language[$i]->attributes()->LANG)
					);
				} else {
					$this->throw_error('Key "LANG" is missing for "'.strval($languages->language[$i]).'". Ignoring language.', E_USER_WARNING);
				}
			}
			
			
			/** Finalisation : Sauvegarde au sein de la classe **/
			$this->_avail_languages = Array("KEYS"=>$keys, "LIST"=>$list);
			
			
			/** Controler la langue définie par défaut **/
			if(gettype($languages->attributes()->default) !== 'NULL'){
				/** vérifier que le pack existe **/
				if(array_key_exists(strval($languages->attributes()->default), $this->_avail_languages['KEYS'])){
					$this->_default_language = strval($languages->attributes()->default);
				} else {
					foreach($this->_avail_languages['KEYS'] as $key => $value){
						$this->_default_language = $key;
						break;
					}
					
					$this->throw_error('The default language "'.strval($languages->attributes()->default).'" is not available. "'.$this->_default_language.'" used instead.', E_USER_WARNING);
				}
			}
			/** Sinon en définir une **/
			else {
				// Définir par défaut la langue internationnale
				// Si indisponible, alors choisir la premier disponible
				if(array_key_exists('en-EN', $this->_avail_languages['KEYS'])){
					$this->_default_language = 'en-EN';
				} else {
					foreach($this->_avail_languages['KEYS'] as $key => $value){
						$this->_default_language = $key;
						break;
					}
				}
			}
		} else {
			$this->throw_error('File "languages.xml" not found. Use SYSLangCompilator::build_environnement(".", "en-EN:English") to generate the Language environnement.', E_USER_WARNING);
		}
	}

    /**
     * Sauvegarde une ressource SimpleXMLElement dans un fichier en respectant la présentation
     * @param \SimpleXMLElement $sxe Ressource SimpleXMLElement à enregistrer proprement
     * @param string $file Fichier de destination
     * @param int $flag Flag correspondant à la fonction file_put_contents
     * @return bool
     */
	static function save_xml($sxe, $file, $flag=null){
		$dom = new DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($sxe->asXML());
		
		if($flag !== null){
			file_put_contents($file, $dom->saveXML(), $flag);
		} else {
			file_put_contents($file, $dom->saveXML());
		}
		
		// Récupération du fichier XML en tant que chaine
		$str = file_get_contents($file);
		
		// Restitution des balises CDATA
		$str = preg_replace("#".self::CDATA_REG_START."\s*(.*)\s*".self::CDATA_REG_END."#m", "<![CDATA[$1]]>", $str);
		
		// Resitution des entités texts et numériques
		$str = preg_replace("#::(\#?)([a-zA-Z0-9]+)::#", "&$1$2;", $str);
		
		// Finalisation
		file_put_contents($file, $str);
		
		return true;
	}
}
?>