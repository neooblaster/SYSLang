<?php
	// Modification d'une clé
	require_once '../SYSLangCompilator.class.php';
	
	$c = new SYSLangCompilator("..");

	$c->set_export_repository("../Languages/imports");
	$c->ini_export(Array(), true);

	/** Modifier les fichier ini **/
	$f = file_get_contents("../Languages/imports/en-EN.ini");

	file_put_contents("../Languages/imports/en-EN.ini", preg_replace("#([0-9]{3}\.[0-9]{5})\s*=\s*(.)*#i", "$1 = TRANSLATED", $f));

	if($c->ini_import(true)){
		$test = "TEST OK";
	} else {
		$test = "TEST KO";
	}
?>
<h1>
	<?php echo $test; ?>
</h1>
<a href="index">Retour</a>