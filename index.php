<?php
date_default_timezone_set("Europe/Paris");
define("MEMORY_REAL_USAGE", true);
$timeInit = microtime(true);
$memInit = memory_get_usage(MEMORY_REAL_USAGE);

require_once(__DIR__."/includes/libs/core/application/class.Singleton.php");
require_once(__DIR__."/includes/libs/core/application/class.Header.php");
require_once(__DIR__."/includes/libs/core/application/class.Autoload.php");

use core\application\Autoload;

Autoload::$folder = __DIR__;
spl_autoload_register(array(Autoload::getInstance(), "load"));

require_once(__DIR__."/includes/libs/core/tools/debugger/class.Debugger.php");
require_once(__DIR__."/includes/libs/core/application/class.Core.php");
require_once(__DIR__."/includes/libs/core/data/interface.InterfaceData.php");
require_once(__DIR__."/includes/libs/core/data/class.SimpleJSON.php");
require_once(__DIR__."/includes/libs/core/application/class.Configuration.php");
require_once(__DIR__."/includes/libs/core/system/class.File.php");
require_once(__DIR__."/includes/libs/core/application/class.Dictionary.php");
require_once(__DIR__."/includes/libs/core/application/event/class.EventDispatcher.php");
require_once(__DIR__."/includes/libs/core/application/class.DefaultController.php");
require_once(__DIR__ . "/includes/libs/core/application/routing/class.Routing.php");

use core\application\Core;
Core::checkEnvironment();
Core::init();
Core::parseURL();
Core::execute(Core::getController(), Core::getAction(), Core::getTemplate());
Core::endApplication();
