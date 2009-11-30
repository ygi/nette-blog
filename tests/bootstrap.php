<?php

/**
 * My Application test bootstrap file.
 *
 * @copyright  Copyright (c) 2009 John Doe
 * @package    MyApplication
 */

// absolute filesystem path to the libraries
define('LIBS_DIR', realpath(dirname(__FILE__) . '/../libs'));

// absolute filesystem path to the application root
define('APP_DIR', realpath(dirname(__FILE__) . '/../app'));

// load Nette Framework
require_once LIBS_DIR . '/Nette/loader.php';
require_once LIBS_DIR . '/dibi/dibi.php';

// load configuration from config.ini file
Environment::loadConfig();

Debug::$showLocation = TRUE;