<?php
namespace core\tools\debugger
{
	use core\application\PrivateClass;
	use core\application\Singleton;
	use core\application\Core;
	use core\application\Configuration;
	use core\utils\Logs;
	use core\application\Autoload;
	use core\application\Header;
	use \Smarty;
	use \Exception;

	/**
	 * Class Debugger - Permet de centraliser les éventuelles "sorties" permettant de debugger l'application
	 * 			Gère :
	 * 				- les Erreurs
	 * 				- les Exceptions
	 * 				- les Sorties
	 * 				- la liste des Requêtes SQL
	 * 				- les variables globales $_GET, $_POST, $_SESSION
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .5
	 * @package tools
	 * @subpackage debugger
	 */
	class Debugger extends Singleton
	{
		/**
		 * Constante d'exception soulevée par l'utilisateur
		 */
		const E_USER_EXCEPTION = -1;

		/**
		 * Temps nécessaire &agrave; l'excecution de l'ensemble de l'application
		 * @var Number
		 */
		private $timeToGenerate;

		/**
		 * @var Number
		 */
		private $memUsage;

		/**
		 * Variable permettant de définir si le debugger est ouvert par défault ou non
		 * @var Boolean
		 */
		static private $open = false;

		/**
		 * @var string
		 */
		static private $state = "odd";

		/**
		 * @var string
		 */
		private $consoles = "";

		/**
		 * @var array
		 */
		private $count = array(
			"trace"=>0,
			"notice"=>0,
			"warning"=>0,
			"error"=>0,
			"query"=>0,
			"get"=>0,
			"post"=>0,
			"session"=>0,
			"cookie"=>0
		);

		/**
		 * @static
		 * @param $pClass
		 * @param $pMessage
		 * @param $pFile
		 * @param $pLine
		 * @return void
		 */
		static private function addToConsole($pClass, $pMessage, $pFile, $pLine)
		{
			$time = explode(".", microtime(true));
			if (!isset($time[1])) $time[1] = "000";
			$decalage = (60 * 60) * ((date("I") == 0) ?1:2);
			self::getInstance()->count[$pClass]++;
			$pClass .= " ".self::$state;
			self::$state = self::$state == "odd"?"even":"odd";
			self::getInstance()->consoles .= "<tr class='".$pClass."'><td class='date'>".(gmdate("H:i:s", $time[0] + $decalage).",".$time[1])."</td><td class='".$pClass."'>&nbsp;&nbsp;</td><td class='message'>".$pMessage."</td><td class='file'>".$pFile.":".$pLine."</td></tr>";
		}

		/**
		 * Méthode d'ajout d'une sortie &agrave; la variable dédiée &agrave; cet effet
		 * @param String $pString					Chaine de caract&egrave;re &agrave; afficher
		 * @param Boolean $pOpen [optional]			Définit si le debugger est ouvert par défault
		 * @return void
		 */
		static public function trace($pString, $pOpen = false)
		{
			if(!self::$open&&$pOpen===true)
				self::$open = true;
			if(is_bool($pString))
				$pString = $pString ? "true":"false";
			if(empty($pString))
				$pString = '<i>Debugger::trace("");</i>';
			$context = debug_backtrace();
			$indice = 0;
			for($i=0, $max = count($context);$i<$max;$i++)
			{
				if(!isset($context[$i]["class"])
					&&($context[$i]["function"]==="trace"
						||$context[$i]["function"]==="trace_r"))
				{
					$indice = $i;
					$i = $max;
				}
			}
			$file = pathinfo($context[$indice]["file"]);
			$file = $file["basename"];

			self::addToConsole("trace", $pString, $file, $context[$indice]["line"]);
		}

		/**
		 * Méthode permettant d'ajouter le contenu d'un tableau &agrave; la liste de sortie du Debugger
		 * @param array	 	$pArray					Tableau dont on souhaite afficher le contenu
		 * @param Boolean	$pOpen [optional]		Définit si le debugger est ouvert par défault
		 * @return void
		 */
		static public function trace_r($pArray, $pOpen = false)
		{
			$string = "<pre>".print_r($pArray,true)."</pre>";
			self::trace($string,$pOpen);
		}

		/**
		 * @static
		 * @param $pQuery
		 * @param $pSource
		 * @param $pDb
		 */
		static public function query($pQuery, $pSource, $pDb)
		{
			self::addToConsole("query", $pQuery, $pSource, $pDb);
		}


		/**
		 * Méthode d'affichage du debugger
		 * @param bool $pDisplay
		 * @param bool $pError
		 * @return string
		 */
		static public function render($pDisplay = true, $pError = false)
		{
            $className = "Smarty";
            if(!class_exists('Smarty'))
            {
                /**
                 * 20150827 - Note from me to me : if Smarty is not defined, then we load it manually
                 * And because of namespace handling in PHP, if an error occurs while defining a class,
                 * the current definition namespace will be stuck and there will be no way to load a new
                 * class in another one.
                 * If you dont trust the young you look up to $classes.
                 */
                Autoload::getInstance()->load("Smarty");
                $classes = get_declared_classes();
                $className = end($classes);
            }
            /** @var Smarty $smarty */
            $smarty = new $className();
			$smarty->clear_all_assign();
			$smartyDir = "includes/libs/core/tools/debugger/templates/_cache/";
			$smarty->template_dir = "includes/libs/core/tools/debugger/templates";
			$smarty->cache_dir = $smartyDir;
			$smarty->compile_dir = $smartyDir;
			$globalVars = self::getGlobalVars();
			foreach($globalVars as $n=>&$v)
				$smarty->assign_by_ref($n, $v);
			$smarty->assign("is_error", $pError);
			$smarty->assign("dir_to_theme", "http://".Configuration::$server_domain."/".(isset(Configuration::$server_folder)?Configuration::$server_folder."/":"")."includes/libs/core/tools/debugger");
			$smarty->assign("dir_to_components", Core::$path_to_components);
			$smarty->assign("server_url", Configuration::$server_url);
			return $smarty->fetch("template.debugger.tpl", null, null, $pDisplay);
		}


		/**
		 * @static
		 * @return array
		 */
		static public function getGlobalVars()
		{
			global $timeInit;
			global $memInit;
			$i = self::getInstance();
			$i->setTimeToGenerate($timeInit, microtime(true));
			$i->setMemoryUsage($memInit, memory_get_usage(MEMORY_REAL_USAGE));
			$i->count["get"] = count($_GET);
			$i->count["post"] = count($_POST);
			$i->count["cookie"] = count($_COOKIE);
			$i->count["session"] = count($_SESSION);
			return array(
				"console"=>$i->consoles,
				"timeToGenerate"=>(round($i->timeToGenerate,3))." sec",
				"memUsage"=>$i->memUsage,
				"vars"=>array("get"=>print_r($_GET, true),
					"post"=>print_r($_POST, true),
					"cookie"=>print_r($_COOKIE, true),
					"session"=>print_r($_SESSION, true)
				),
				"count"=>$i->count,
				"open"=>self::$open
			);
		}


		/**
		 * Méthode de définition du temps nécessaire &agrave; l'excecution de l'application
		 * @param String $pStartTime		Microtime de début
		 * @param String $pEndTime          Microtime de fin
		 * @return void
		 */
		static public function setTimeToGenerate($pStartTime, $pEndTime)
		{
			$i = self::getInstance();
			if(!$pEndTime)
				$pEndTime = microtime(true);
			$i->timeToGenerate = ($pEndTime - $pStartTime);
		}

		/**
		 * @static
		 * @param $pStartMem
		 * @param $pEndMem
		 */
		static public function setMemoryUsage($pStartMem, $pEndMem)
		{
			$i = self::getInstance();
			$mem = $pEndMem - $pStartMem;
			$units = array("o", "ko", "Mo", "Go");
			$k = 0;
			while($units[$k++] && $mem>1024)
				$mem /= 1024;
			$mem = round($mem*100)/100;
			$i->memUsage = $mem." ".$units[--$k];
		}

		/**
		 * Gestionnaire des erreurs de scripts Php
		 * Peut stopper l'application en cas d'erreur bloquante
		 * @param Number $pErrorLevel						Niveau d'erreur
		 * @param String $pErrorMessage						Message renvoyé
		 * @param String $pErrorFile						Adresse du fichier qui a déclenché l'erreur
		 * @param Number $pErrorLine						Ligne où se trouve l'erreur
		 * @param String $pErrorContext						Contexte
		 * @return void
		 */
		static public function errorHandler($pErrorLevel, $pErrorMessage, $pErrorFile, $pErrorLine, $pErrorContext)
		{
			$stopApplication = false;
			switch($pErrorLevel)
			{
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:
					$stopApplication = true;
					$type = "error";
					break;
				case E_WARNING:
				case E_CORE_WARNING:
				case E_COMPILE_WARNING:
				case E_USER_WARNING:
					$type = "warning";
					break;
				case E_NOTICE:
				case E_USER_NOTICE:
					$type = "notice";
					break;
				default:
				case self::E_USER_EXCEPTION:
					$stopApplication = true;
					$type = "error";
					break;
			}
			$pErrorFile = pathinfo($pErrorFile);
			$pErrorFile = $pErrorFile["basename"];
			if(preg_match('/href=/', $pErrorMessage, $matches))
				$pErrorMessage = preg_replace('/href=\'([a-z\.\-\_]*)\'/', 'href=\'http://www.php.net/$1\' target=\'_blank\'', $pErrorMessage);
			self::addToConsole($type, $pErrorMessage, $pErrorFile, $pErrorLine);
			if($stopApplication)
			{
				if(!Core::debug())
				{
					Logs::write($pErrorMessage." ".$pErrorFile." ".$pErrorLine." ".$pErrorContext, $pErrorLevel);
				}
				Header::content_type("text/html", Configuration::$global_encoding);
				self::$open = true;
				self::render(true, true);
                Core::endApplication();
			}
		}

		/**
		 * Gestionnaire d'exceptions soulevées lors de l'exécution du script
		 * @param Exception $pException
		 * @return void
		 */
		static public function exceptionHandler($pException)
		{
			self::errorHandler(self::E_USER_EXCEPTION, $pException->getMessage(), $pException->getFile(), $pException->getLine(), $pException->getFile());
		}

		/**
		 * @static
		 * @return void
		 */
		static public function prepare()
		{
			Autoload::addComponent("Debugger");
		}

		/**
		 * Construct
		 * @param $pInstance	PrivateClass
		 */
		public function __construct($pInstance)
		{
			if(!$pInstance instanceOf PrivateClass)
				trigger_error("Il est interdit d'instancier un objet de type <i>Singleton</i> - Merci d'utiliser la méthode static <i>".__CLASS__."::getInstance()</i>", E_USER_ERROR);
		}

		/**
		 * ToString()
		 * @return String
		 */
		public function __toString()
		{
			return "[Objet Debugger]";
		}
	}
}

namespace
{
	use core\tools\debugger\Debugger;

	function trace($pString, $pOpen = false)
	{
		Debugger::trace($pString, $pOpen);
	}

	function trace_r($pArray, $pOpen = false)
	{
		Debugger::trace_r($pArray, $pOpen);
	}
}