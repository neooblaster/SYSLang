<?php
use SYSLang\SYSLang;

class SYSLangCompilator extends SYSLang
{
    /**
     * @var \SimpleXMLElement $_MD5_package_report Focus sur le rapport MD5 correspondant au package de référence
     */
	protected $_MD5_package_report = null;

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
     * Compile le ou les langues spécifiées au format xx-XX
     * @param string $packages Pack de langue à compiler
     * @return bool
     */
	public function compile($packages=null){
                /** #2. Lister les packages à compiler **/
                //$packages_to_compile = Array();

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

                // #3.3. Vérifier qu'un rapport MD5 pour le pack de référence existe

                /** #4. Lancer la compilation **/
                // RAZ pour execution multiple de compile par la meme instance
                $this->_ref_packages_keys = Array();
                $this->_ini_files_code = Array();
                $this->_ini_keys_code = Array();
                $this->_ini_texts = Array();
                $this->_ini_sxe_resources = Array();
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
}
?>