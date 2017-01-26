<?php
	error_reporting(E_ALL);
	require_once '../SYSLangCompilator.class.php';

	$sxe = SYSLangCompilator::SXEOverhaul(file_get_contents("../Languages/fr-FR/generic.xml"));
	$sxe->resource[1] = "TEXT SOURCE MODIFIER";
	$sxe->resource[1]->attributes()->CST = "false";
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