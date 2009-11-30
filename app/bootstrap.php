<?php

/**
 * Application bootstrap file.
 *
 * @author     Roman Sklenář
 * @package    Blog
 */


// Step 1: Load Nette Framework
// this allows load Nette Framework classes automatically so that
// you don't have to litter your code with 'require' statements
require LIBS_DIR . '/Nette/loader.php';
require LIBS_DIR . '/dibi/dibi.php';


// Step 2: Configure environment
// 2a) enable Nette\Debug for better exception and error visualisation
$mode = (!Environment::isProduction() && !Environment::getHttpRequest()->isAjax()) ? Debug::DEVELOPMENT : Debug::PRODUCTION;
Debug::enable($mode);
Debug::enableProfiler();
Debug::$strictMode = TRUE;

// 2b) load configuration from config.ini file
$config = Environment::loadConfig();

// 2c) check if needed directories are writable
if (!is_writable(Environment::getVariable('tempDir'))) {
        die("Make directory '" . realpath(Environment::getVariable('tempDir')) . "' writable!");
}

if (!is_writable(Environment::getVariable('logDir'))) {
        die("Make directory '" . realpath(Environment::getVariable('logDir')) . "' writable!");
}

// 2d) Session setup [optional]
if (Environment::getVariable('sessionDir') !== NULL && !is_writable(Environment::getVariable('sessionDir'))) {
        die("Make directory '" . realpath(Environment::getVariable('sessionDir')) . "' writable!");
}
$session = Environment::getSession();
$session->start();


// Step 3: Configure application
// 3a) get and setup a front controller
$application = Environment::getApplication();
$application->onStartup[] = 'BaseModel::initialize';
$application->errorPresenter = 'Error';
$application->catchExceptions = Environment::isProduction();



// Step 4: Setup application router
$router = $application->getRouter();

$router[] = new Route('index.php', array(
	'presenter' => 'Page',
	'action' => 'about',
), Route::ONE_WAY);

$router[] = new Route('articles/<action list|add|delete|show|edit>[/<id [0-9]+>]', array(
	'presenter' => 'article',
	'action' => 'list',
));

$router[] = new Route('<presenter auth|page>/<action>', array(
	'presenter' => 'Page',
	'action' => 'about',
));


// Step 5: Run the application!
$application->run();