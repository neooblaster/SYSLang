#!/usr/bin/env php
<?php

/**
 * Script d'interface pour l'utilisation de SYSLang en CLI.
 *
 * HowTo :
 *      - chmod +x command_index.php
 *      - sudo ln -s /path/to/projet/src/SYSLang/command_index.php /usr/local/bin/SYSLang
 *
 * @author    marvin255 from GitHub, Adjust Nicolas DUPRE
 * @release   23/11/2017
 * @version   2.0.0-beta1
 * @package   Index
 */

use SYSLang\Command;

require_once(__DIR__ . '/SYSLang/Autoloader.php');

$options = getopt(
    Command::OPTIONS['shortopt'],
    Command::OPTIONS['longopt']
);

$commandName = basename($_SERVER['SCRIPT_NAME']);

(new Command($_SERVER["PWD"], $options, $commandName))->run();
