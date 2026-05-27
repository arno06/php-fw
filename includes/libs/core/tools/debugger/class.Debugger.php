<?php
namespace core\tools\debugger
{
	use core\application\PrivateClass;
	use core\application\Singleton;
	use core\application\Core;
	use core\application\Configuration;
    use core\data\SimpleJSON;
    use core\tools\template\RenderingContext;
    use core\utils\CLI;
    use core\utils\Logs;
	use core\application\Autoload;
	use core\application\Header;
    use core\utils\OPCacheHelper;
    use Exception;
    use TypeError;
    use Error;

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
		 * Temps nécessaire à l'excecution de l'ensemble de l'application
		 * @var float
		 */
		private float $timeToGenerate;

		private string $memUsage;

		/**
		 * Variable permettant de définir si le debugger est ouvert par défault ou non
		 * @var bool
		 */
		static private bool $open = false;

		static private string $state = "odd";

		private string $consoles = "";

        private array $tracked = array();

        private FGTrack|null $startedTrack = null;

        private int $totalTracks = 0;

        private string $title = '';

		private array $count = array(
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

        private bool $activated = true;


		static private function addToConsole(string $pClass, string $pMessage, string $pFile, string $pLine):void
		{
            /** @var Debugger $i */
            $i = self::getInstance();
            if(!$i->activated)
                return;
            if(CLI::isCurrentContext()){
                $line = CLI::newLine()->out("\r\n");
                $color = match($pClass){
                    "error"=>CLI::RED,
                    "warning"=>CLI::YELLOW,
                    "notice"=>CLI::LIGHT_YELLOW,
                    default=>null
                };
                if(!is_null($color)){
                    $line->setTextColor($color)->out($pClass." ")->resetAll();
                }

                $line->out($pFile.":".$pLine)->endOfLine();
                $pMessage = str_replace("\n", "\n   ", $pMessage);
                CLI::enrichedOutput(" ".$pMessage);
                CLI::newLine()->endOfLine();
                return;
            }
            $pMessage = preg_replace("/\*([^*]+)\*/", "<b>$1</b>", $pMessage);
            $pMessage = str_replace("\n", "<br/>&nbsp;&nbsp;", $pMessage);
			$time = explode(".", microtime(true));
			if (!isset($time[1])) $time[1] = "000";
			$decalage = (60 * 60) * ((date("I") == 0) ?1:2);
			$i->count[$pClass]++;
			$pClass .= " ".self::$state;
			self::$state = self::$state == "odd"?"even":"odd";
            $context = "";
            if(!is_null($i->startedTrack)){
                $hashes = [];
                $element = $i->startedTrack;
                while($element){
                    $hashes[] = $element->hash;
                    $element = $element->parent;
                }
                $context = ' data-context="'.implode("|", $hashes).'"';
            }
            $i->consoles .= "<tr class='".$pClass."'".$context."><td class='date'>".(gmdate("H:i:s", intval($time[0]) + $decalage).",".$time[1])."</td><td class='".$pClass."'>&nbsp;&nbsp;</td><td class='message'>".$pMessage."</td><td class='file'>".$pFile.":".$pLine."</td></tr>";
		}


        static public function track(string $pId):void
        {
            /** @var Debugger $instance */
            $instance = self::getInstance();
            if(!$instance->activated)
                return;

            if(is_null($instance->startedTrack)){
                $instance->startedTrack = new FGTrack($pId);
            }else{
                if($instance->startedTrack->id == $pId){
                    $instance->totalTracks++;
                    $instance->startedTrack->end();
                    trace($instance->startedTrack->__toString());
                    if($instance->startedTrack->parent === null){
                        $instance->tracked[] = $instance->startedTrack;
                    }
                    $parent = $instance->startedTrack->parent;
                    $instance->startedTrack->parent = null;
                    unset($instance->startedTrack->parent);
                    $instance->startedTrack = $parent;
                }else{
                    $newInstance = new FGTrack($pId);
                    $instance->startedTrack->children[] = $newInstance;
                    $newInstance->parent = $instance->startedTrack;
                    $instance->startedTrack = $newInstance;
                }
            }
        }


		/**
		 * Méthode d'ajout d'une sortie à la variable dédiée à cet effet
		 * @param mixed $pString					Chaine de caractère à afficher
		 * @param bool $pOpen [optional]			Définit si le debugger est ouvert par défault
		 * @return void
		 */
		static public function trace(mixed $pString, bool $pOpen = false):void
		{
            /** @var Debugger $instance */
            $instance = self::getInstance();
            if(!$instance->activated)
                return;
			if(!self::$open&&$pOpen===true)
				self::$open = true;
			if(is_bool($pString))
				$pString = $pString ? "true":"false";
			else{
                if(empty($pString))
                    $pString = '<i>Debugger::trace("");</i>';
            }
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
		 * Méthode permettant d'ajouter le contenu d'un tableau à la liste de sortie du Debugger
		 * @param array $pArray Tableau dont on souhaite afficher le contenu
		 * @param bool $pOpen [optional] Définit si le debugger est ouvert par défault
		 * @return void
		 */
		static public function traceR(array $pArray, bool $pOpen = false):void
		{
			$string = "<pre>".print_r($pArray,true)."</pre>";
			self::trace($string,$pOpen);
		}


		static public function query(string $pQuery, string $pSource, string $pDb):void
		{
			self::addToConsole("query", $pQuery, $pSource, $pDb);
		}


		/**
		 * Méthode d'affichage du debugger
		 * @param bool $pDisplay
		 * @param bool $pError
		 * @return bool|string
		 */
		public function render(bool $pDisplay = true, bool $pError = false):bool|string
		{
            if(!$this->activated)
                return false;
            $ctx = new RenderingContext("includes/libs/core/tools/debugger/templates/template.debugger.php");
            $ctx->assign('is_error', $pError);
            $ctx->assign('dir_to_components', Core::$path_to_components);
            $ctx->assign('title', $this->title);
            $ctx->assign('server_url', Configuration::$server_url);
			$globalVars = $this->getGlobalVars();
			foreach($globalVars as $n=>&$v)
                $ctx->assign($n, $v);
            return $ctx->render($pDisplay);
		}


		public function getGlobalVars():array
		{
			$this->setTimeToGenerate(microtime(true));
			$this->setMemoryUsage(memory_get_usage(MEMORY_REAL_USAGE));
			$this->count["get"] = count($_GET);
			$this->count["post"] = count($_POST);
			$this->count["cookie"] = count($_COOKIE);
			$this->count["session"] = count($_SESSION);
            $this->count['opcache'] = OPCacheHelper::getInstance()->countScripts();
            $vars = array("get"=>print_r($_GET, true),
                "post"=>print_r($_POST, true),
                "cookie"=>print_r($_COOKIE, true),
                "session"=>print_r($_SESSION, true),
                "opcache"=>OPCacheHelper::getInstance()->prettyPrint()
            );
            if($this->totalTracks > 0){
                $this->count["tracks"] = $this->totalTracks;
                $vars["tracks"] = "<div class='debug_flamegraph'></div><script>let flamegraphData = ".SimpleJSON::encode($this->tracked).";document.addEventListener('DEBUGGER_DISPLAY_TRACKS', ()=>{FlameGraph.display(flamegraphData, '.debug_flamegraph')});</script>";
            }
			return array(
				"console"=>$this->consoles,
				"timeToGenerate"=>(round($this->timeToGenerate,3))." sec",
				"memUsage"=>$this->memUsage,
                "vars"=>$vars,
				"count"=>$this->count,
				"open"=>self::$open
			);
		}


		/**
		 * Méthode de définition du temps nécessaire à l'excecution de l'application
         * @param float $pEndTime          Microtime de fin
		 * @return void
		 */
		private function setTimeToGenerate(float $pEndTime):void
		{
            if(!$pEndTime)
				$pEndTime = microtime(true);
			$this->timeToGenerate = ($pEndTime - INIT_TIME);
		}


		private function setMemoryUsage(int $pEndMem):void
		{
            $mem = $pEndMem - INIT_MEMORY;
			$this->memUsage = self::formatMemory($mem);
		}


        static public function formatMemory(int $pValue, int $pPrecision = 2):String
        {
            $units = array("octet", "ko", "Mo", "Go");
            $i = 0;
            while($pValue >= 1024 && $units[$i++])
            {
                $pValue /= 1024;
            }
            return round($pValue, $pPrecision)." ".$units[$i];
        }

		/**
		 * Gestionnaire des erreurs de scripts Php
		 * Peut stopper l'application en cas d'erreur bloquante
		 * @param int $pErrorLevel						Niveau d'erreur
		 * @param string $pErrorMessage						Message renvoyé
		 * @param string $pErrorFile						Adresse du fichier qui a déclenché l'erreur
		 * @param int $pErrorLine						Ligne où se trouve l'erreur
		 * @param string|null $pErrorContext						Contexte - Déprécié en PHP 8
		 * @return void
		 */
		static public function errorHandler(int $pErrorLevel, string $pErrorMessage, string $pErrorFile, int $pErrorLine, string $pErrorContext = null):void
		{
			$stopApplication = false;
			switch($pErrorLevel)
			{
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
                case E_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
				case self::E_USER_EXCEPTION:
                default:
					$stopApplication = true;
					$type = "error";
					break;
			}
			$pErrorFile = pathinfo($pErrorFile);
			$pErrorFile = $pErrorFile["basename"];
			if(str_contains($pErrorMessage, "href="))
				$pErrorMessage = preg_replace('/href=\'([a-z.\-_]*)\'/', 'href=\'https://www.php.net/$1\' target=\'_blank\'', $pErrorMessage);
			self::addToConsole($type, $pErrorMessage, $pErrorFile, $pErrorLine);
			if($stopApplication)
			{
				if(!Core::debug())
				{
					Logs::write($pErrorMessage." ".$pErrorFile." ".$pErrorLine." ".$pErrorContext, $pErrorLevel);
				}
                $exitCode = 0;
                if(!CLI::isCurrentContext()){
                    self::getInstance()->title = 'Une erreur est apparue !';
                    Header::contentType("text/html", Configuration::$global_encoding);
                    self::$open = true;
                    self::getInstance()->render(true, true);
                    self::getInstance()->deactivate();
                }else{
                    $exitCode = 1;
                }
                Core::endApplication($exitCode);
			}
		}

		/**
		 * Gestionnaire d'exceptions soulevées lors de l'exécution du script
		 * @param Exception|TypeError|Error $pException
		 * @return void
		 */
		static public function exceptionHandler(Exception|TypeError|Error $pException):void
		{
			self::errorHandler(self::E_USER_EXCEPTION, $pException->getMessage(), $pException->getFile(), $pException->getLine(), $pException->getFile());
		}


		static public function prepare():void
		{
            if(CLI::isCurrentContext()||Core::isBot()){
                self::getInstance()->deactivate();
                return;
            }
			Autoload::addComponent("Debugger");
		}


        public function activate():void
        {
            $this->activated = true;
        }


        public function deactivate():void
        {
            $this->activated = false;
        }


		public function __construct(PrivateClass $pInstance){}

		/**
		 * ToString()
		 * @return String
		 */
		public function __toString()
		{
			return "[Objet Debugger]";
		}
	}


    class FGTrack{
        public string $id;
        public string $hash;
        public FGTrack|null $parent = null;
        public array $children = [];
        public float|null $startTime = null;
        public float|null $endTime = null;
        public int|null $startMemory = null;
        public int|null $endMemory = null;

        public function __construct(string $pId)
        {
            $this->id = $pId;
            $this->hash = md5($this->id);
            $this->startTime = microtime(true);
            $this->startMemory = memory_get_usage(true);
        }

        public function end():void
        {
            $this->endTime = microtime(true);
            $this->endMemory = memory_get_usage(true);
        }

        public function __toString()
        {
            $executionTime = round($this->endTime - $this->startTime, 3);
            $memoryUsage = Debugger::formatMemory($this->endMemory-$this->startMemory);
            return $this->id."\nexecution time: *".($executionTime)."sec*\nmemory usage: *".($memoryUsage)."*";
        }
    }
}

namespace
{
	use core\tools\debugger\Debugger;

	function trace(string $pString, bool $pOpen = false):void
	{
		Debugger::trace($pString, $pOpen);
	}

	function trace_r(mixed $pArray, bool $pOpen = false):void
	{
		Debugger::traceR($pArray, $pOpen);
	}

    function track(string $pId):void
    {
        Debugger::track($pId);
    }
}
