<?php

define('CHARSET', 'UTF-8');
define('CLI_CALL', true);

$GLOBALS['ERRORS_VERBOSE'] = false;

require __DIR__ . '/../functions.php';

spl_autoload_register('autoload');
