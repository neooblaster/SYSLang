<?php
use SYSLang\SYSLang;

class SYSLangCompilator extends SYSLang
{
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
        $this->_ini_sxe_resources = Array();
	}

    /**
     * Importe les fichiers de langue INI dans l'environnement de fonctionnement SYSLang
     * @param bool $finalise Indique si on "commit", donc sauvegarde et met à jour le référentiel MD5
     * @return bool
     */
}
?>