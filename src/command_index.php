#!/usr/bin/env php
<?php

require_once(__DIR__ . '/SYSLang/Autoloader.php');

$options = getopt(
    'h',
    [
        "add-languages:",
        "default",
        "directory:",
        "dir:",
        "help",
        "install",
        "remove-languages:",
        "remove-langs:",
        "set-default-lang:",
        "silent",
    ]
);

(new \SYSLang\Command($_SERVER["PWD"], $options))->run();
