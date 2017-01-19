<?php
	// Modification d'une clé
	require_once '../SYSLangCompilator.class.php';
	
	$c = new SYSLangCompilator(".");

	// Compilation #2
	if($c->ini_export(Array("en-EN"), true)){
		$test = "TEST OK";
	} else {
		$test = "TEST KO";
	}
?>
<h1>
	<?php echo $test; ?>
</h1>
<a href="index">Retour</a>