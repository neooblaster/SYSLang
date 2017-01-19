<?php
	// Test Numéro 101 :: Création de l'environnement - Autre Dossier
	require_once '../SYSLangCompilator.class.php';
	
	if(SYSLangCompilator::build_environnement("..", "fr-FR:Français")){
		$test = "TEST OK";
	} else {
		$test = "TEST KO";
	}
?>
<h1>
	<?php echo $test; ?>
</h1>
<a href="index">Retour</a>