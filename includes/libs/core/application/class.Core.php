<?php
namespace core\application
{
	use core\data\SimpleJSON;
	use core\tools\debugger\Debugger;
	use core\application\authentification\AuthentificationHandler;
	use core\db\DBManager;
	use core\application\rewriteurl\RewriteURLHandler;
	use \Exception;
	use Smarty;


	/**
	 * Noyau central
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 2.0
	 * @package application
	 */
	abstract class Core
	{
		/**
		 * Version en cours du framework
		 */
		const VERSION = "2.0";

		/**
		 * @var string
		 */
		const PATH_TO_CONFIG = "/includes/applications/config.json";

		/**
		 * Définit si l'application vise le backoffice
		 * @var Boolean
		 */
		static public $isBackoffice = false;

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
		 * Contient l'url requêtée (sans l'application ni la langue)
		 * @var String
		 */
		static public $url;

		/**
		 * Définit le module en cours - front ou back
		 * @var String
		 */
		static public $module;

		/**
		 * Fait référence &agrave; l'instance du controller en cours
		 * @var FrontController
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

			session_name(Configuration::$site_session);
			session_start();
			set_error_handler('\core\tools\debugger\Debugger::errorHandler');
			set_exception_handler('\core\tools\debugger\Debugger::exceptionHandler');
			self::$request_async = (isset($_SERVER["HTTP_X_REQUESTED_WITH"])&&
					                    $_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");
		}

		/**
		 * Instanciation des objects globlaux de l'application
		 * Gestionnaire de relation &agrave; la base de donnée, gestionnaire d'authentification... ect
		 * @return void
		 */
		static public function defineGlobalObjects()
		{
			if(self::isBot())
				self::deactivateDebug();
			if(is_array(Configuration::$db)&&!empty(Configuration::$db))
			{
				foreach(Configuration::$db as $name=>$info)
				{
					DBManager::set($name, $info);
				}
			}
			call_user_func_array(array(Configuration::$application_authentificationHandler,"getInstance"), array());
			if(self::debug())
				Debugger::prepare();
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
				$pConfigurationFile = Autoload::$folder.self::PATH_TO_CONFIG;
			$configurationData = array();
			try
			{
				$configurationData = SimpleJSON::import($pConfigurationFile);
			}
			catch(Exception $e)
			{
				if ($pConfigurationFile == Autoload::$folder.self::PATH_TO_CONFIG)
					die("Impossible de charger le fichier de configuration de base : <b>".$pConfigurationFile."</b>");
			}
			if (!is_array($configurationData) && $pConfigurationFile == Autoload::$folder.self::PATH_TO_CONFIG)
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
			if(!empty($_SERVER["SERVER_PORT"])&&$_SERVER["SERVER_PORT"]!=80)
				Configuration::$server_domain.=":".$_SERVER["SERVER_PORT"];
			Configuration::$server_folder = preg_replace('/\/(index).php$/', "", $_SERVER["SCRIPT_NAME"]);
			Configuration::$server_folder = preg_replace('/^\//', "", Configuration::$server_folder);
			Configuration::$server_url = "http://".Configuration::$server_domain."/";
			if(!empty(Configuration::$server_folder))
				Configuration::$server_url .= Configuration::$server_folder."/";

			$url = isset($pUrl)&&!is_null($pUrl)?$pUrl:$_SERVER["REQUEST_URI"];
			if (preg_match("/([^\?]*)\?.*$/", $url, $matches))
			{
				$url = $matches[1];
			}

			if(!preg_match('/\/$/',$url,$extract, PREG_OFFSET_CAPTURE)&&
				!preg_match('/\.[a-z]{2,4}$/', $url, $extract, PREG_OFFSET_CAPTURE))
			{
				$url .= "/";
			}

			$application = self::extractApplication($url);
			Configuration::$site_application = $application;
			self::setConfiguration(Autoload::$folder."/includes/applications/".Configuration::$site_application."/config.json");

			$acces = "";
			if (Configuration::$site_application != "main")
			{
				Configuration::$server_url .= Configuration::$site_application."/";
				$acces = "../";
			}
			self::$path_to_components = Configuration::$server_url.$acces.self::$path_to_components;

			self::defineGlobalObjects();

			self::$path_to_application = Autoload::$folder."/includes/applications/".Configuration::$site_application;

			self::$isBackoffice = RewriteURLHandler::checkForBackoffice($url);

			Configuration::$application_rewriteURLHandler = ucfirst(Configuration::$site_application)."RewriteURLHandler";
			$path_to_rewriteURLHandler = self::$path_to_application."/src/application/rewriteurl/class.".Configuration::$application_rewriteURLHandler.".php";
			if(!file_exists($path_to_rewriteURLHandler))
			{
				$path_to_rewriteURLHandler = Autoload::$folder."/includes/libs/core/application/rewriteurl/class.RewriteURLHandler.php";
				Configuration::$application_rewriteURLHandler = 'core\application\rewriteurl\RewriteURLHandler';
			}
			include_once($path_to_rewriteURLHandler);

			Configuration::$site_multilanguage = Configuration::$site_multilanguage && !self::$isBackoffice;

			$language = RewriteURLHandler::extractLanguage($url);
			if (Configuration::$site_multilanguage&&!Core::$isBackoffice)
				Configuration::$site_currentLanguage = $language;
			else
				Configuration::$site_currentLanguage = Configuration::$site_defaultLanguage;
			self::setDictionary();
			$parsedURL = call_user_func_array(array(Configuration::$application_rewriteURLHandler,'parse'), array($url));
			self::$url = $url;

			self::$controller = str_replace("-", "_", $parsedURL["controller"]);
			self::$action = str_replace("-", "_", $parsedURL["action"]);
			if (Core::$isBackoffice)
				self::$module = "back";
			else
				self::$module = "front";

			$_GET = array_merge($parsedURL["parameters"], $_GET);
			self::$path_to_theme = Configuration::$server_url.$acces."themes/".Configuration::$site_application."/".Configuration::$site_theme."/".self::$module;

			self::$path_to_templates = Autoload::$folder."/themes/".Configuration::$site_application."/default/".self::$module."/views";
		}


		/**
		 * Méthode public d'extraction de l'application appelée via l'URL
		 * Renvoi "main" par défault
		 * @param String $pURL		URL récupérée via son adresse
		 * @return String
		 */
		static private function extractApplication(&$pURL)
		{
			$folder = preg_replace('/(\/)/', "\/", Configuration::$server_folder);
			$pURL = preg_replace('/^(\/'.(!empty($folder)?$folder.'\/':"").")/","",$pURL);
			if(preg_match('/^('.Configuration::$site_application.')\//', $pURL, $extract, PREG_OFFSET_CAPTURE))
			{
				$application = str_replace("/","",$extract[0][0]);
				$pURL = preg_replace("/^".$extract[1][0].'\//',"",$pURL);
				if($application=="main")
					Go::toFront();
			}
			else
				$application = "main";
			return $application;
		}


		/**
		 * Méthode vérifiant l'existance et retournant une nouvelle instance du controller récupéré
		 * Renvoie vers la page d'erreur 404 si le fichier contenant le controller n'existe pas
		 * Stop l'application et renvoie une erreur si le fichier existe mais pas la classe demandée
		 * @return FrontController
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
			$controller = 'app\\'.Configuration::$site_application.'\\controllers\\'.self::$module.'\\'.self::$controller;
			if (!file_exists($controller_file))
			{
				if (call_user_func_array(array(Configuration::$application_frontController, "isFromDB"), array(self::$controller, self::$action, self::$url))) {
					$controller = self::$controller = Configuration::$application_frontController;
					self::$action = "prepareFromDB";
				} else
					Go::to404();
			} else
				include_once ($controller_file);
			if (!class_exists($controller))
			{
				if (Configuration::$application_debug)
					trigger_error("Controller <b>".self::$controller."</b> introuvable", E_USER_ERROR);
				else
					Go::to404();
			}
			self::$instance_controller = new $controller();
			if(!self::$isBackoffice)
			{
				if(isset($seo["title"]))
					self::$instance_controller->setTitle($seo["title"]);
				if(isset($seo["description"]))
					self::$instance_controller->setDescription($seo["description"]);
			}
			return self::$instance_controller;
		}

		/**
		 * Méthode permettant de définir le dictionnaire en fonction d'un fichier de langue
		 * @return void
		 */
		static public function setDictionary()
		{
			$dictionary_path = self::$path_to_application."/localization/".Configuration::$site_currentLanguage.".json";
			try
			{
				$donneesLangue = SimpleJSON::import($dictionary_path);
			}
			catch(Exception $e)
			{
				if(Configuration::$application_debug)
					trigger_error('Fichier de langue "<b>'.$dictionary_path.'</b>" introuvable', E_USER_ERROR);
				else
				{
					Configuration::$site_currentLanguage = Configuration::$site_defaultLanguage;
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
			Dictionary::defineLanguage(Configuration::$site_currentLanguage, $terms, $seo, $alias);
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
		 * Méthode de vérification si l'application est disponible en mode développeur (en fonction du config.json et de l'authentification)
		 * @return bool
		 */
		static public function debug()
		{
			return Configuration::$application_debug||AuthentificationHandler::is(AuthentificationHandler::DEVELOPER);
		}


		/**
		 * @static
		 * @return void
		 */
		static public function deactivateDebug()
		{
			Configuration::$application_debug = false;
			AuthentificationHandler::$permissions = array();
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
			if(Core::$isBackoffice)
				$module = "back";
			else
				$module = "front";
			$pSmarty->template_dir = Core::$path_to_templates;
			$smartyDir = Core::$path_to_application."/_cache/".$module;
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
		 * @param FrontController|null $pController
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
				$pController->renderHTML($smarty);
				if(Core::debug())
					Debugger::renderHTML($smarty);
			}
			else
			{
				$return = $pController->getGlobalVars();
				$return = array_merge($return, Debugger::getGlobalVars());
				if((isset($_POST)&&isset($_POST["o_html"])&&$_POST["o_html"]!="false"))
					$return["html"] = $pController->renderHTML($smarty, false);
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
			self::$isBackoffice = null;
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