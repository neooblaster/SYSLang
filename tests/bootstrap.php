<?php
/**
 * bootstrap.php
 *
 * Fixe l'erreur "date(): It is not safe to rely on the system's timezone settings(...)"
 * sur les environnements Docker.
 *
 * @author    Nicolas DUPRE
 * @release   18/10/2017
 * @version   1.0.0
 * @package   Index
 */
date_default_timezone_set('Europe/Paris');
