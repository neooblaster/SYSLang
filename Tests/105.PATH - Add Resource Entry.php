<?php
	// Test Numéro 105 :: Ajouter une clé
	require_once '../SYSLangCompilator.class.php';

	$sxe = SYSLangCompilator::SXEOverhaul(file_get_contents("../Languages/fr-FR/generic.xml"));
	$new = $sxe->addChild("resource", "Nouvelle entrée ajoutée");
	$new->addAttribute("KEY", "KEY_".count($sxe->resource));
	$new->addAttribute("SST", "true");
	$new->addAttribute("CST", "true");
	$new->addAttribute("TIR", "false");
	SYSLang::save_xml($sxe, "../Languages/fr-FR/generic.xml");

	$c = new SYSLangCompilator("..");
	$c->set_ref_language("fr-FR");
	
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