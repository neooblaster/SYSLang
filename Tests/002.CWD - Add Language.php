<?php
	// Test Numéro 002 :: Ajouter une nouvelle langue - Autre Dossier
	require_once '../SYSLangCompilator.class.php';
	$c = new SYSLangCompilator(".");
	
	if($c->add_languages("en-EN:English") && $c->add_languages("de-DE:Deutch")){
		$test = "TEST OK";
	} else {
		$test = "TEST KO";
	}
?>
<h1>
	<?php echo $test; ?>
</h1>
<a href="index">Retour</a>