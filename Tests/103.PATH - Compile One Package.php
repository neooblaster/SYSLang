<?php
	// Test Numéro 103 :: Compiler un package précis - Autre Dossier
	require_once '../SYSLangCompilator.class.php';
	$c = new SYSLangCompilator("..");
	$c->set_ref_language("fr-FR");
	
	if($c->compile("en-EN")){
		$test = "TEST OK";
	} else {
		$test = "TEST KO";
	}
?>
<h1>
	<?php echo $test; ?>
</h1>
<a href="index">Retour</a>