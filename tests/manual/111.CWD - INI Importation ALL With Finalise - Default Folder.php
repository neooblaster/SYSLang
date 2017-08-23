<?php
	error_reporting(E_ALL);
	require_once '../SYSLangCompilator.class.php';
	
	$c = new SYSLangCompilator("..");

	$c->set_export_repository("../Languages/imports");
	$c->ini_export(Array(), true);

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