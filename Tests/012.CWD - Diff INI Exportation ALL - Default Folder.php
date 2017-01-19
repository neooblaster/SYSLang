<?php
	// Modification d'une clé
	require_once '../SYSLangCompilator.class.php';
	
	$c = new SYSLangCompilator(".");

	// Exportation Full ALL dans dossier imports
	$c->set_export_repository("Languages/imports");
	$c->ini_export(Array(), true);

	// Modification par importation en finalise
	$c->ini_import(true);
	
	// Modification du pack de ref
	$sxe = SYSLangCompilator::SXEOverhaul(file_get_contents("Languages/fr-FR/generic.xml"));
	$sxe->resource[0] = "TEXT SOURCE MODIFIER - DOIT ETRE UNIQUE DANS L'EXPORT INI EN DIFF";
	SYSLang::save_xml($sxe, "Languages/fr-FR/generic.xml");

	// Compilation pour appliquer aux autre packs
	$c->set_ref_language("fr-FR");
	$c->compile();
	
	// Exportation differentielle

	if($c->ini_export(Array(), false)){
		$test = "TEST OK";
	} else {
		$test = "TEST KO";
	}
?>
<h1>
	<?php echo $test; ?>
</h1>
<a href="index">Retour</a>