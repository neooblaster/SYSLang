<?php
	// Test Numéro 001 :: Création de l'environnement - Même dossier d'execution
	require_once '../SYSLangCompilator.class.php';
	
	if(SYSLangCompilator::build_environnement(".", "fr-FR:Français")){
		$test = "TEST OK";
	} else {
		$test = "TEST KO";
	}
?>
<h1>
	<?php echo $test; ?>
</h1>
<a href="index">Retour</a>