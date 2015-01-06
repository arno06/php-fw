<?php
namespace core\application
{
	/**
	 * Class Go
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package application
	 */
	class Go
	{
		/**
		 * @static
		 * @return void
		 */
		static public function to404()
		{
			$controller = Core::$isBackoffice?new Configuration::$application_backController:new Configuration::$application_frontController;
			Header::http("1.0 404 Not Found");
			Header::status("404 Not Found");
			Core::execute($controller, null, Configuration::$site_template404);
			Core::endApplication();
		}


		/**
		 * @static
		 * @param string $pController
		 * @param string $pAction
		 * @param array $pParams
		 * @param string $pLangue
		 * @param int $pCode
		 * @return void
		 */
		static public function toFront($pController = "", $pAction = "", $pParams = array(), $pLangue = "", $pCode = 301)
		{
			$rewriteURL = Configuration::$server_url;
			$rewriteURL .= Core::rewriteURL($pController, $pAction, $pParams, $pLangue);
			Header::location($rewriteURL, $pCode);
		}


		/**
		 * @static
		 * @param string $pController
		 * @param string $pAction
		 * @param array $pParams
		 * @return void
		 */
		static public function toBack($pController = "", $pAction = "", $pParams = array())
		{
			$rewriteURL = Configuration::$server_url.Configuration::$site_backoffice."/";
			$rewriteURL .= Core::rewriteURL($pController, $pAction, $pParams);
			Header::location($rewriteURL);
		}
	}
}
