<pre><?php
    /**
     * File :: ${FILE_NAME}
     *
     * %DESC BLOCK%
     *
     * @author    ${AUTHOR}
     * @release   ${DATE}
     * @version   1.0.0
     * @package   Index
     */

    /** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- ** 
/** ---																																					--- **
/** --- 											-----------------------------------------------											--- **
/** --- {}--- **
/** --- 											-----------------------------------------------											--- **
/** ---																																					--- **
/** ---		AUTEUR 	: Nicolas DUPRE																											--- **
/** ---																																					--- **
/** ---		RELEASE	: xx.xx.2016																												--- **
/** ---																																					--- **
/** ---		VERSION	: 1.0																															--- **
/** ---																																					--- **
/** ---																																					--- **
/** --- 														-----------------------------														--- **
/** --- 															{ C H A N G E L O G } 															--- **
/** --- 														-----------------------------														--- **	
/** ---																																					--- **
/** ---																																					--- **
/** ---		VERSION 1.0 : xx.xx.2016																											--- **
/** ---		------------------------																											--- **
/** ---			- Première release																												--- **
/** ---																																					--- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- **

	Objectif du script :
	---------------------
	
	Description fonctionnelle :
	----------------------------
	
/** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** ---																																					--- **
/** ---													PHASE 1 - INITIALISATION DU SCRIPT													--- **
/** ---																																					--- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** > Chargement des Paramètres **/
/** > Ouverture des SESSIONS Globales **/
/** > Chargement des Classes **/
	require_once 'SYSLangCompilator.class.php';

/** > Chargement des Configs **/
/** > Chargement des Fonctions **/
/** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** ---																																					--- **
/** ---												PHASE 2 - CONTROLE DES AUTORISATIONS													--- **
/** ---																																					--- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- **/
/** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** ---																																					--- **
/** ---												PHASE 3 - INITIALISAITON DES DONNEES													--- **
/** ---																																					--- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- **/
/** > Déclaration des variables **/
	$compilator;	// SYSLangCompilator	:: instance de la classe du même type

/** > Initialisation des variables **/
/** > Déclaration et Intialisation des variables pour le moteur (référence) **/
/** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** ---																																					--- **
/** ---									PHASE 4 - EXECUTION DU SCRIPT DE TRAITEMENT DE DONNEES										--- **
/** ---																																					--- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- **/

/** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** ---																																					--- **
/** ---											PHASE 5 - GENERATION DES DONNEES DE SORTIE												--- **
/** ---																																					--- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- **/

	//$t = new SYSLang('Languages');
	//$t->get_avail_languages();
	//pprint($t);

/** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** ---																																					--- **
/** ---												PHASE 6 - AFFICHER LES SORTIES GENEREE													--- **
/** ---																																					--- **
/** -------------------------------------------------------------------------------------------------------------------- **
/** -------------------------------------------------------------------------------------------------------------------- **/
/** > Possible header pour debbug **/
	//header('Content-Type: text/html; charset=utf-8');
	//header('Content-Type: text/event-stream; charset=utf-8');

/** > Création du moteur **/
	//SYSLangCompilator::build_environnement(".", "en-EN:English");
	//$compilator = new SYSLangCompilator(".");

/** > Configuration du moteur **/
	//$compilator->set_ref_language('en-EN');

/** > Execution du moteur **/
	//$compilator->add_languages('fr-FR:Français');
	//$compilator->compile();
	//$compilator->ini_export(Array(), false);
	//$compilator->ini_import(false);


//echos(file_get_contents("cdata.xml"));

	//$sxe = new SimpleXMLElement(file_get_contents("cdata.xml"), LIBXML_NOENT);
	//$sxeo = new SXEOverhaul(file_get_contents("cdata.xml"));
	//$sxeo->addChild("test");

	//echos($sxe->resource[0], strval($sxe->resource[0]));
	
	// Convert a string into binary
	// Should output: 0101001101110100011000010110001101101011

	//echos((string)$sxe->resource[0]);

	//<![CDATA[your_corresponding_text_here_UPDATE]]>
	//if(preg_match("#CDATA#", $sxe->resource[0])){
	//	echos("text found 001");
	//}

	//$test = new SYSLang('Languages');

//exit();



/** > Process au complet **/
	// Création de l'environnement
	//SYSLangCompilator::build_environnement(".", "fr-FR:Français");

	// Instanciation
	//$compilator = new SYSLangCompilator(".");

	// Déclaration de nouvelle langue
	//$compilator->add_languages("en-EN:English"/*, "de-DE:Deutsh", "es-ES:Espanol", "jp-JP:日本の"*/);
	//$compilator->set_ref_language('fr-FR');
	//$compilator->compile();
	//$compilator->ini_export();

	//$test = "
	//	<resource KEY=\"REASON_ID_DO_NOT_EXIST\" CST=\"false\" SST=\"true\">L'identifiant saisie n'existe pas dans la base de donnée. L'action sera donc créée</resource>
	//	<resource KEY=\"REASON_ID_UNIDENTIFIABLE\" CST=\"false\" SST=\"true\">L'identifiant saisie n'est pas identifiable</resource>
	//	<resource KEY=\"REASON_LASTDATE_BEFORE_FIRSTDATE\" CST=\"false\" SST=\"true\">[[La date de dernière apparition est antérieure à la date de première apparition qui est %s.]]</resource>
	//	<resource KEY=\"REASON_LIST_DATA_NOT_EXIST\" CST=\"false\" SST=\"true\">La donnée saisie n'a pas ou plus de référence dans la base de donnée</resource>
	//	<resource KEY=\"REASON_LIST_DATA_NOT_LISTED\" CST=\"false\" SST=\"true\">La donnée saisie n'existe pas dans le classeur</resource>
	//	<resource KEY=\"REASON_STARTTIME_AFTER_ENDTIME\" CST=\"false\" SST=\"true\">L'heure de démarrage de l'action est postérieure à l'heure de fin qui est %s.</resource>
	//	<resource KEY=\"REASON_TIME_AS_STRING_AND_WRONG\" CST=\"false\" SST=\"true\">[[L'heure est saisie au format chaine de caractère et elle semble invalide.<br />Format attendu pour une chaine de caractère : hh:mm.]]</resource>
	//";
	//
	//$repl = preg_replace_callback("#(?<=\[\[).*(?=\]\])#mi", function($h){
	//	$return = $h[0];
	//	$return = str_replace("<", "&gt;", $return);
	//	$return = str_replace(">", "&lt;", $return);
	//	return $return;
	//}, $test);
	//
	//echos($repl);


	/** Création **/
	//SYSLangCompilator::build_environnement(".", "fr-FR:Français");


	/** Extension **/
	//$compilator = new SYSLangCompilator(".");
	//$compilator->add_languages("en-EN:English");

	/** Compilatior **/
	//$compilator->set_ref_language("fr-FR");
	//$compilator->compile();

	/** Exportation **/
	//$compilator->ini_export(Array("en-EN"));
	//$compilator->ini_import(true);


?>







