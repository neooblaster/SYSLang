<?php
	error_reporting(E_ALL);
	require_once '../SYSLangCompilator.class.php';

	// Ajouter une clé PRECISE
	$sxe = SYSLangCompilator::SXEOverhaul(file_get_contents("Languages/fr-FR/generic.xml"));
	$new = $sxe->addChild("resource", "Nouvelle entrée ajoutée");
	$new->addAttribute("KEY", "KEY_PRECISE");
	$new->addAttribute("SST", "true");
	$new->addAttribute("CST", "true");
	$new->addAttribute("TIR", "false");
	SYSLang::save_xml($sxe, "Languages/fr-FR/generic.xml");

	// Compilation #1
	$c = new SYSLangCompilator(".");
	$c->set_ref_language("fr-FR");
	$c->compile();

	// Supprimer clé précise 
	$sxe = SYSLangCompilator::SXEOverhaul(file_get_contents("Languages/en-EN/generic.xml"));
	$index = 0;
	foreach($sxe->resource as $k => $v){
		$key = strval($v->attributes()->KEY);
		if($key === "KEY_PRECISE"){
			break;
		}
		$index++;
	}
	unset($sxe->resource[$index]);
	SYSLang::save_xml($sxe, "Languages/en-EN/generic.xml");
	
	// Modifier la clé PRECISE pack REF
	$sxe = SYSLangCompilator::SXEOverhaul(file_get_contents("Languages/fr-FR/generic.xml"));
	$index = 0;
	foreach($sxe->resource as $k => $v){
		$key = strval($v->attributes()->KEY);
		if($key === "KEY_PRECISE"){
			break;
		}
		$index++;
	}
	$sxe->resource[$index] ="Modification pour faire MAJ sur clé inexistante, donc assimilable à une création";
	SYSLang::save_xml($sxe, "Languages/fr-FR/generic.xml");

	// Compilation #2
	if($c->compile()){
		$test = "TEST OK";
	} else {
		$test = "TEST KO";
	}
?>
<h1>
	<?php echo $test; ?>
</h1>
<a href="index">Retour</a>