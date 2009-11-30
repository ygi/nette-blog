<?php

/**
 * My Application test bootstrap file.
 *
 * @copyright  Copyright (c) 2009 John Doe
 * @package    MyApplication
 */

// absolute filesystem path to the libraries
define('LIBS_DIR', realpath(__DIR__ . '/../libs'));

// absolute filesystem path to the application root
define('APP_DIR', realpath(__DIR__ . '/../app'));

// load Nette Framework
require_once LIBS_DIR . '/Nette/loader.php';
require_once LIBS_DIR . '/dibi/dibi.php';

// load configuration from config.ini file
Environment::loadConfig();

Debug::$showLocation = TRUE;
/*
foreach (Environment::getService('Nette\Loaders\RobotLoader')->scanDirs as $dir) {
	Debug::dump(is_dir($dir));
}

Debug::dump(is_readable('D:\Web\www\nette-blog\libs\dibi\dibi.php'));
Debug::dump(is_file('D:\Web\www\nette-blog\libs\dibi\libs\DibiConnection.php'));
*/