<?php
/** ----------------------------------------------------------------------------------------------------------------------- 
/** ----------------------------------------------------------------------------------------------------------------------- 
/** ---																																						---
/** --- 											----------------------------------------------- 											---
/** --- 															{ SYSLang.class.php }																---
/** --- 											----------------------------------------------- 											---
/** ---																																						---
/** ---		AUTEUR 	: Nicolas DUPRE																												---
/** ---																																						---
/** ---		RELEASE	: 12.01.2017																													---
/** ---																																						---
/** ---		VERSION	: 1.4																																---
/** ---																																						---
/** ---																																						---
/** --- 														-----------------------------															---
/** --- 															 { C H A N G E L O G } 																---
/** --- 														-----------------------------															---
/** ---																																						---
/** ---		VERSION 1.4 : 12.01.2017																												---
/** ---		-------------------------																												---
/** ---			-  Ajout de constante pour traiter les balise XML CDATA + traitement associé										---
/** ---																																						---
/** ---																																						---
/** ---		VERSION 1.3 : 06.05.2016																												---
/** ---		-------------------------																												---
/** ---			-  Passage de PRIVATE en PROTECTED de la classe list_languages()														---
/** ---				>  Utilisation possible par la classe fille SYSLangCompilator														---
/** ---																																						---
/** ---			-  Ajout de la classe static save_xml, permettant la sauvegarde structurée de l'XML								---
/** ---																																						---
/** ---		VERSION 1.2 : 06.05.2016																												---
/** ---		-------------------------																												---
/** ---			- Mise à jour complète de la classe :																							---
/** ---				> Amélioration du programme d'analyse de la langue utilisateur à l'aide du navigateur						---
/** ---				> Amélioration du programme d'analyse du ficheir languages.xml														---
/** ---					> Prise en charge de l'attribut "default", langue par défault (faculatif)									---
/** ---				> Ajout d'une système de notification d'erreur officiel PHP (donc masquable @)								---
/** ---				> Sauvegarde de la langue dans un cookie au lieu d'une session														---
/** ---				> "Soignage" des commentaires du script																					---
/** ---																																						---
/** ---		VERSION 1.1 : 30.07.2015																												---
/** ---		-------------------------																												---
/** ---			- Ajout de la fonction addslashes pour échapper les caractère pour JavaScript [CST=true]						---
/** ---																																						---
/** ---		VERSION 1.0 : 23.06.2015																												---
/** ---		-------------------------																												---
/** ---			- Première release																													---
/** ---																																						---
/** --- 										-----------------------------------------------------											---
/** --- 											{ L I S T E      D E S      M E T H O D E S } 												---
/** --- 										-----------------------------------------------------											---
/** ---																																						---
/** ---		GETTERS :																																	---
/** ---	    ---------																																	---
/** ---																																						---
/** ---			- [Pub] get_avail_languages																										---
/** ---			- [Pub] get_lang																														---
/** ---			- [Pri] get_user_language																											---
/** ---																																						---
/** ---		SETTERS :																																	---
/** ---	    ---------																																	---
/** ---																																						---
/** ---			- [Pub] set_lang																														---
/** ---																																						---
/** ---		OUTPUTTERS :																																---
/** ---	    ------------																																---
/** ---																																						---
/** ---			- [Pri] throw_error																													---
/** ---			- [Pub] unpack																															---
/** ---																																						---
/** ---		WORKERS :																																	---
/** ---	    ---------																																	---
/** ---																																						---
/** ---			- [Pro] list_languages																												---
/** ---			- [Sta] save_xml																														---
/** ---																																						---
/** ---																																						---
/** -----------------------------------------------------------------------------------------------------------------------
/** -----------------------------------------------------------------------------------------------------------------------



/** -----------------------------------------------------------------------------------------------------------------------
/** ----------------------------------------------------------------------------------------------------------------------- **/
class SYSLang {
/** -----------------------------------------------------------------------------------------------------------------------
/** -----------------------------------------------------------------------------------------------------------------------
/** ---																																						---
/** ---															{ C O N S T A N T E S }																---
/** ---																																						---
/** -----------------------------------------------------------------------------------------------------------------------
/** ----------------------------------------------------------------------------------------------------------------------- **/
	const XML_CONF_FILE = 'languages.xml';
	const CDATA_STR_START = "[[";
	const CDATA_REG_START = "\[\[";
	const CDATA_STR_END = "]]";
	const CDATA_REG_END = "\]\]";
	
	
	
/** -----------------------------------------------------------------------------------------------------------------------
/** -----------------------------------------------------------------------------------------------------------------------
/** ---																																						---
/** ---															{ P R O P E R T I E S }																---
/** ---																																						---
/** -----------------------------------------------------------------------------------------------------------------------
/** ----------------------------------------------------------------------------------------------------------------------- **/
	protected $_avail_languages = null;		// ARRAY    :: Liste des langues disponible
	protected $_files_repository = null;	// STRING	:: Dossier de dépot des packs de langue [SYSLangCompilator dependant]
	protected $_user_language = null;		// STRING	:: Liste des langue accepté par le navigateur de l'utilisateur
	protected $_default_language = null;	// STRING	:: Langue par défaut
	
	
/** ----------------------------------------------------------------------------------------------------------------------- 
/** -----------------------------------------------------------------------------------------------------------------------
/** ---																																						---
/** ---														{ C O N S T R U C T E U R S }															---
/** ---																																						---
/** -----------------------------------------------------------------------------------------------------------------------
/** ----------------------------------------------------------------------------------------------------------------------- **/
	/** ------------------------------------------------------------- **
	/** --- Méthode de construction - Execution à l'instanciation --- **
	/** ------------------------------------------------------------- **/
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
	
	/** ------------------------------------------------------------- **
	/** --- Méthode de déstruction - Execution à la fin du script --- **
	/** ------------------------------------------------------------- **/
	function __destruct(){
		
	}
	
	
	
/** ----------------------------------------------------------------------------------------------------------------------- 
/** ----------------------------------------------------------------------------------------------------------------------- 
/** ---																																						---
/** ---																{ G E T T E R S }																	---
/** ---																																						---
/** -----------------------------------------------------------------------------------------------------------------------
/** ----------------------------------------------------------------------------------------------------------------------- **/
	/** --------------------------------------------------------------------- **
	/** --- Fonction d'affichage des langue disponible dans l'application --- ** 
	/** --------------------------------------------------------------------- **/
	public function get_avail_languages(){
		return $this->_avail_languages;
	}
		
	/** -------------------------------------------------------- **
	/** --- Fonction qui permet d'afficher la langue définie --- **
	/** ------------------------------------------------------- **/
	public function get_lang(){
		return $this->_user_language;
	}
		
	/** ----------------------------------------------------------------- **
	/** --- Fonction de récupération automatique de la langue système --- **
	/** ----------------------------------------------------------------- **/
	private function get_user_language(){
		/** Connexion aux variables globales **/
  			global $_SERVER;	// Superglobale Server
		
		/** Déclaration des variables **/
			$accepted_languages;	// Language admis par le navigateur 
			$user_language;		// Language utilisateur determiné
			$matches;				// Résultat des occurences trouvées
		
		/** Traitement des languages admis **/
			// CHROME : Array ( [0] => fr-FR [1] => fr;q=0.8 [2] => en-US;q=0.6 [3] => en;q=0.4 ) 
			// FIREFO : Array ( [0] => fr [1] => fr-FR;q=0.8 [2] => en-US;q=0.5 [3] => en;q=0.3 ) 
			// OPERA  : Array ( [0] => fr-FR [1] => fr;q=0.8 [2] => en-US;q=0.6 [3] => en;q=0.4 ) 
			// SAFARI : Array ( [0] => fr-FR )
			// IE     : Array ( [0] => fr-FR )
			//
			// Pattern de recherche [a-z]{2}-[A-Z]{2}
		
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
	
	
	
/** ----------------------------------------------------------------------------------------------------------------------- 
/** ----------------------------------------------------------------------------------------------------------------------- 
/** ---																																						---
/** ---																{ S E T T E R S }																	---
/** ---																																						---
/** -----------------------------------------------------------------------------------------------------------------------
/** ----------------------------------------------------------------------------------------------------------------------- **/
	/** --------------------------------------------------- **
	/** --- Méthode pour mettre à jour la langue active --- **
	/** --------------------------------------------------- **/
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
	
	
	
/** ----------------------------------------------------------------------------------------------------------------------- 
/** ----------------------------------------------------------------------------------------------------------------------- 
/** ---																																						---
/** ---															{ O U T P U T E R S }																---
/** ---																																						---
/** -----------------------------------------------------------------------------------------------------------------------
/** ----------------------------------------------------------------------------------------------------------------------- **/
	/** --------------------------------------------------------- **
	/** --- Méthode de génération d'erreur offcielle pour PHP --- **
	/** --------------------------------------------------------- **/
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
	} // Void throw_error([String $message='' [, Integer $error_level=E_USER_NOTICE]])
	
	/** ------------------------------------------------------- **
	/** --- Méthode d'extraction des textes des fichier xml --- **
	/** ------------------------------------------------------- **/
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
	
	
	
/** ----------------------------------------------------------------------------------------------------------------------- 
/** ----------------------------------------------------------------------------------------------------------------------- 
/** ---																																						---
/** ---																{ W O R K E R S }																	---
/** ---																																						---
/** -----------------------------------------------------------------------------------------------------------------------
/** ----------------------------------------------------------------------------------------------------------------------- **/
	/** ---------------------------------------------------------------------------------------- **
	/** --- Méthode d'analyse du fichier de configuration des langues dans le dossier défini --- **
	/** ---------------------------------------------------------------------------------------- **/
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
	
	/** ------------------------------------------------------- **
	/** --- Méthode de sauvegarde formatée des fichiers XML --- **
	/** ------------------------------------------------------- **/
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
		
		file_put_contents($file, preg_replace("#".self::CDATA_REG_START."\s*(.*)\s*".self::CDATA_REG_END."#m", "<![CDATA[$1]]>", file_get_contents($file)));
		
		return true;
	} // Boolean save_xml(SimpleXMLElement $sxe, String $file [, Integer $flag=null])
}

?>