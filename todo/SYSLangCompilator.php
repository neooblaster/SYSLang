<?php
use SYSLang\SYSLang;

class SYSLangCompilator extends SYSLang
{
    /**
<<<<<<< Updated upstream
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

}
?>