<?php
namespace core\application
{
	use core\data\SimpleJSON;
	use core\tools\debugger\Debugger;
	use core\db\DBManager;
	use core\application\routing\RoutingHandler;
	use \Exception;
	use \Smarty;


	/**
	 * Noyau central
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 2.5
	 * @package application
	 */
	abstract class Core
	{
		/**
		 * Version en cours du framework
		 */
		const VERSION = "2.5";

		/**
		 * @var string
		 */
        static public $config_file = null;

		/**
		 * Définit le chemin vers le dossier de l'application en cours
		 * @var String
		 */
		static public $path_to_application;

		/**
		 * Définit le chemin vers le dossier du th&egrave;me pour l'application en cours
		 * @var String
		 */
		static public $path_to_theme = "themes/main/default/front";

		/**
		 * @var string
		 */
		static public $path_to_components = "includes/components";

		/**
		 * @var string
		 */
		static public $path_to_templates = "themes/main/default/front/views";

		/**
		 * Contient l'url requêtée (sans l'application ni la langue)
		 * @var String
		 */
		static public $url;

		/**
		 * @var Application
		 */
		static public $application;

		/**
		 * Définit le module en cours - front ou back
		 * @var String
		 */
		static public $module;

		/**
		 * Définit le nom du controller
		 * @var String
		 */
		static public $controller;

		/**
		 * Définit le nom de l'action
		 * @var String
		 */
		static public $action;

		/**
		 * Fait référence &agrave; l'instance du controller en cours
		 * @var DefaultController
		 */
		static private $instance_controller;

		/**
		 * @var bool
		 */
		static public $request_async = false;

		/**
		 * @var array
		 */
		static public $headers;

		/**
		 * Initialisation du Core applicatif du framework
		 * @return void
		 */
		static public function init()
		{
			ini_set("session.use_trans_sid", 0);

			session_name(Configuration::$global_session);
			session_start();
			set_error_handler('\core\tools\debugger\Debugger::errorHandler');
			set_exception_handler('\core\tools\debugger\Debugger::exceptionHandler');
			self::$request_async = (isset($_SERVER["HTTP_X_REQUESTED_WITH"])&&
					                    $_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");
		}

		/**
		 * Instanciation des objects globlaux de l'application
		 * Gestionnaire de relation &agrave; la base de donnée, gestionnaire d'authentication... ect
		 * @return void
		 */
		static public function defineGlobalObjects()
		{
			if(self::isBot())
				self::deactivateDebug();
//			call_user_func_array(array(Configuration::$application_authenticationHandler,"getInstance"), array());
			if(self::debug())
				Debugger::prepare();
		}

		static public function checkEnvironment($pFile = "includes/applications/setup.json")
		{
			$setup = SimpleJSON::import($pFile);
			self::$config_file = "/includes/applications/dev.config.json";
			if(!$setup)
			{
				return;
			}
			foreach($setup as $env=>$domains)
			{
				if(in_array($_SERVER["SERVER_NAME"], $domains))
				{
					self::$config_file = "/includes/applications/".$env.".config.json";
				}
			}
			self::setConfiguration();
		}

		/**
		 * Méthode statique de définition de l'objet Configuration via le fichier JSON
		 * Récupération + parsing du fichier JSON
		 * Défintition des propriétés statiques de l'objet Configuration
		 * @param String $pConfigurationFile			Url du fichier de configuration
		 * @return Void
		 */
		static public function setConfiguration($pConfigurationFile = null)
		{
			if($pConfigurationFile == null)
				$pConfigurationFile = Autoload::$folder.self::$config_file;
			$configurationData = array();
			try
			{
				$configurationData = SimpleJSON::import($pConfigurationFile);
			}
			catch(Exception $e)
			{
				if ($pConfigurationFile == Autoload::$folder.self::$config_file)
					die("Impossible de charger le fichier de configuration de base : <b>".$pConfigurationFile."</b>");
			}
			if (!is_array($configurationData) && $pConfigurationFile == Autoload::$folder.self::$config_file)
				trigger_error('Impossible de parser le fichier de configuration de base <b>includes/applications/config.json</b>. Veuillez vérifier le formatage des données (guillements, virgules, accents...).', E_USER_ERROR);
			foreach ($configurationData as $prefix=>$property)
			{
				if (property_exists('core\application\Configuration', $prefix))
				{
					Configuration::$$prefix = $property;
					continue;
				}
				if (is_array($property))
				{
					foreach ($property as $name=>$value)
					{
						$n = $prefix."_".$name;
						if (property_exists('core\application\Configuration', $n))
							Configuration::$$n = $value;
					}
				}
			}
		}

		/**
		 * @static
		 * @param string $pController
		 * @param string $pAction
		 * @param array $pParams
		 * @param string $pLangue
		 * @return mixed
		 */
		static public function rewriteURL($pController = "", $pAction = "", $pParams = array(), $pLangue = "")
		{
			return call_user_func_array(array(Configuration::$application_rewriteURLHandler, "rewrite"), array($pController, $pAction, $pParams, $pLangue));
		}

		/**
		 * Méthode de parsing de l'url en cours
		 * récup&egrave;re le controller, l'action, la langue (si multilangue) ainsi que les param&egrave;tres $_GET
		 * @param $pUrl
		 * @return void
		 */
		static public function parseURL($pUrl = null)
		{
			Configuration::$server_domain = $_SERVER["SERVER_NAME"];
			$protocol = "http".((isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')?'s':'')."://";
			Configuration::$server_folder = preg_replace('/\/(index).php$/', "", $_SERVER["SCRIPT_NAME"]);
			Configuration::$server_folder = preg_replace('/^\//', "", Configuration::$server_folder);
			Configuration::$server_url = $protocol.Configuration::$server_domain."/";
			if(!empty(Configuration::$server_folder))
				Configuration::$server_url .= Configuration::$server_folder."/";

            /**
             * Définition de l'url + suppression des paramètres GET ?var=value
             */
			$url = isset($pUrl)&&!is_null($pUrl)?$pUrl:$_SERVER["REQUEST_URI"];
			if (preg_match("/([^\?]*)\?.*$/", $url, $matches))
			{
				$url = $matches[1];
			}

            $application_name = RoutingHandler::extractApplication($url);

			self::$application = new Application($application_name);
			self::$application->setModule(RoutingHandler::extractModule($url, self::$application->getModulesAvailable()));
            self::$module = self::$application->getModule()->name;

            Configuration::$server_url .= self::$application->getUrlPart();

			$access = self::$application->getPathPart();

			self::$path_to_components = Configuration::$server_url.$access.self::$path_to_components;

			self::defineGlobalObjects();

			self::$path_to_application = Autoload::$folder."/includes/applications/".$application_name;

            /**
             * should handle multilanguage here
             */

			self::setDictionary();
			$parsedURL = RoutingHandler::parse($url);
			self::$url = $url;

			self::$controller = str_replace("-", "_", $parsedURL["controller"]);
			self::$action = str_replace("-", "_", $parsedURL["action"]);

			$_GET = array_merge($parsedURL["parameters"], $_GET);
			self::$path_to_theme = Configuration::$server_url.$access.self::$application->getThemePath();
			self::$path_to_templates = self::$application->getThemePath()."/views";
		}

		/**
		 * Méthode vérifiant l'existance et retournant une nouvelle instance du controller récupéré
		 * Renvoie vers la page d'erreur 404 si le fichier contenant le controller n'existe pas
		 * Stop l'application et renvoie une erreur si le fichier existe mais pas la classe demandée
		 * @return DefaultController
		 */
		static public function getController()
		{
			if(Core::$controller==="statique")
			{
				include_once("includes/libs/core/application/controller.statique.php");
				self::$instance_controller = new statique();
				return self::$instance_controller;
			}
			$seo = Dictionary::seoInfos(self::$controller, self::$action);
			$controller_file = self::$path_to_application."/modules/".self::$module."/controllers/controller.".self::$controller.".php";
			$controller = 'app\\'.self::$application->getName().'\\controllers\\'.self::$module.'\\'.self::$controller;
			if (!file_exists($controller_file))
			{
                $defaultController = self::$application->getModule()->defaultController;
				if (call_user_func_array(array($defaultController, "isFromDB"), array(self::$controller, self::$action, self::$url))) {
					$controller = self::$controller = $defaultController;
					self::$action = "prepareFromDB";
				} else
					Go::to404();
			} else
				include_once ($controller_file);
			if (!class_exists($controller))
			{
				if (self::debug())
					trigger_error("Controller <b>".self::$controller."</b> introuvable", E_USER_ERROR);
				else
					Go::to404();
			}
			self::$instance_controller = new $controller();
            if(isset($seo["title"]))
                self::$instance_controller->setTitle($seo["title"]);
            if(isset($seo["description"]))
                self::$instance_controller->setDescription($seo["description"]);
			return self::$instance_controller;
		}

		/**
		 * Méthode permettant de définir le dictionnaire en fonction d'un fichier de langue
		 * @return void
		 */
		static public function setDictionary()
		{
			$dictionary_path = self::$path_to_application."/localization/".Configuration::$global_currentLanguage.".json";
			try
			{
				$donneesLangue = SimpleJSON::import($dictionary_path);
			}
			catch(Exception $e)
			{
				if(self::debug())
					trigger_error('Fichier de langue "<b>'.$dictionary_path.'</b>" introuvable', E_USER_ERROR);
				else
				{
					Configuration::$global_currentLanguage = Configuration::$global_defaultLanguage;
					Go::to404();
				}
			}
			$seo = array();
			$terms = array();
			$alias = array();
			if(isset($donneesLangue["terms"])&&is_array($donneesLangue["terms"]))
				$terms = $donneesLangue["terms"];
			if(isset($donneesLangue["seo"])&&is_array($donneesLangue["seo"]))
				$seo = $donneesLangue["seo"];
			if(isset($donneesLangue["alias"])&&is_array($donneesLangue["alias"]))
				$alias = $donneesLangue["alias"];
			Dictionary::defineLanguage(Configuration::$global_currentLanguage, $terms, $seo, $alias);
		}


		/**
		 * Méthode vérifiant l'existance de la méthode action dans la classe controller précédemment instanciée
		 * @return String
		 */
		static public function getAction() {
			if (!method_exists(self::$instance_controller, self::$action))
				Go::to404();
			return self::$action;
		}


		/**
		 * Méthode de récupération du template par défault en fonction du controller et de l'action demandée
		 * @return String
		 */
		static public function getTemplate()
		{
			return self::$controller."/template.".self::$action.".tpl";
		}


		/**
		 * Méthode de vérification si l'application est disponible en mode développeur (en fonction du config.json et de l'authentication)
		 * @return bool
		 */
		static public function debug()
		{
            $authHandler = Configuration::$application_authenticationHandler;
            $isDev = call_user_func_array(array($authHandler, "is"), array($authHandler::DEVELOPER));
            return Configuration::$global_debug||$isDev;
        }


		/**
		 * @static
		 * @return void
		 */
		static public function deactivateDebug()
		{
			Configuration::$global_debug = false;
            $authHandler = Configuration::$application_authenticationHandler;
            $authHandler::$permissions = array();
		}


		/**
		 * @static
		 * @return bool
		 */
		static public function isBot()
		{
			$ua = $_SERVER["HTTP_USER_AGENT"];
			$UA_bots = array('Googlebot\/', 'bingbot\/', "Yahoo");
			for($i = 0, $max = count($UA_bots); $i<$max;$i++)
			{
				if(preg_match("/".$UA_bots[$i]."/i", $ua, $matches))
					return true;
			}
			return false;
		}


		/**
		 * Méthode de configuration de Smarty
		 * @param Smarty $pSmarty				Instance de smarty
		 * @return void
		 */
		static public function setupSmarty(Smarty &$pSmarty)
		{
			$pSmarty->template_dir = Core::$path_to_templates;
			$smartyDir = Core::$path_to_application."/_cache/".self::$module;
			$pSmarty->cache_dir = $smartyDir;
			$pSmarty->compile_dir = $smartyDir;
		}

		/***
		 * Méthode permettant d'afficher simplement un contenu sans passer par le syst&egrave;me de templating
		 * Sert notamment dans le cadre de requêtes asychrones (avec du Flash ou du JS par exemple)
		 * @param string $pContent			Contenu &agrave; afficher
		 * @param string $pType [optional]	Type de contenu &agrave; afficher - doit être spécifié pour assurer une bonne comptatilité &agrave; l'affichage
		 * @return void
		 */
		static public function performResponse($pContent, $pType="text")
		{
			$pType = strtolower($pType);
			switch($pType)
			{
				case "json":
					$content = "application/json";
					break;
				case "xml":
					$content = "application/xml";
					break;
				case "text":
				default:
					$content = "text/plain";
					break;
			}
			Header::content_type($content);
			echo $pContent;
			self::endApplication();
		}

		/**
		 * Méthode de vérification de l'existance de variables GET
		 * @return bool
		 */
		static public function checkRequiredGetVars()
		{
			$gets = func_get_args();
			for($i = 0, $max=count($gets); $i<$max;$i++)
			{
				if(!isset($_GET[$gets[$i]])||empty($_GET[$gets[$i]]))
					return false;
			}
			return true;
		}

		/**
		 * @static
		 * @param DefaultController|null $pController
		 * @param null $pAction
		 * @param string $pTemplate
		 * @return void
		 */
		static public function execute($pController = null, $pAction = null, $pTemplate = "")
		{
			if($pController != "statique")
				$pController->setTemplate(self::$controller, self::$action, $pTemplate);
			if($pAction!=null)
				$pController->$pAction();
			$smarty = new Smarty();
			if(!Core::$request_async)
			{
				Header::content_type("text/html");
				$pController->render();
				if(Core::debug())
					Debugger::renderHTML($smarty);
			}
			else
			{
				$return = $pController->getGlobalVars();
				$return = array_merge($return, Debugger::getGlobalVars());
				if((isset($_POST)&&isset($_POST["render"])&&$_POST["render"]!="false"))
					$return["html"] = $pController->render(false);
				$response = SimpleJSON::encode($return);
				$type = "json";
				self::performResponse($response, $type);
			}
			$smarty = null;
		}


		/**
		 * Méthode appelée afin de clore l'application
		 * @return void
		 */
		static public function endApplication()
		{
			self::$instance_controller = null;
			self::$action = null;
			self::$controller = null;
            self::$application = null;
			self::$module = null;
			self::$path_to_application = null;
			self::$path_to_components = null;
			self::$path_to_theme = null;
			self::$path_to_templates = null;
			Singleton::dispose();
			DBManager::dispose();
			exit();
		}
	}
}