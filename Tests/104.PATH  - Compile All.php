<?php
	// Test Numéro 104 :: Compiler un package précis - Dossier courant
	require_once '../SYSLangCompilator.class.php';
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