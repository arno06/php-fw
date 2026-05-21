<?php
namespace core\application
{

    use core\application\routing\RoutingHandler;
    use JetBrains\PhpStorm\NoReturn;

    /**
	 * Class Go
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.1
	 * @package application
	 */
	class Go
	{
        #[NoReturn]
		static public function to404():void
		{
            $defaultController = Core::$application->getModule()->defaultController;
			$controller = new $defaultController();
			Header::http("1.0 404 Not Found");
			Header::status("404 Not Found");
			Core::execute($controller, Core::$application->getModule()->action404);
			Core::endApplication();
		}


        #[NoReturn]
		static public function to(string $pController = "", string $pAction = "", array $pParams = array(), string $pLangue = "", int $pCode = 301):void
		{
			$rewriteURL = Configuration::$server_url;
			$rewriteURL .= RoutingHandler::rewrite($pController, $pAction, $pParams, $pLangue);
			Header::location($rewriteURL, $pCode);
		}
	}
}
