<?php
namespace core\application
{
	/**
	 * Class Autoload
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.0
	 * @package core\application
	 */
	class Autoload extends Singleton
	{
		/**
		 * @var string
		 */
		static public $folder = '';

		/**
		 * @type string
		 */
		const FOLDER_CORE = '/includes/libs/core/';

		/**
		 * @var array
		 */
		private $scripts;

		/**
		 * @var array
		 */
		private $scriptDependencies;

		/**
		 * @var array
		 */
		private $styles;

		/**
		 * @var array
		 */
		private $exeptions = array('PHPMailer'=>'/includes/libs/phpMailer/class.phpmailer.php');


		/**
		 * constructor
		 */
		public function __construct()
		{
			$this->scripts = array();
			$this->scriptDependencies = array();
			$this->styles = array();
		}


		/**
		 * Identifie la classe à charger en fonction de son package
		 * @param string $pClassName	Nom de la classe préfixé de son package
		 * @return bool
		 */
		public function load($pClassName)
		{
			$path = '';
			$packages = explode('\\', $pClassName);

			$base = array_shift($packages);

			$className = array_pop($packages);
			$type = 'class';
			if(preg_match('/^(Model|Interface)/', $className, $matches))
				$type = strtolower($matches[1]);

			switch($base)
			{
				case 'core':
					$path = self::$folder.self::FOLDER_CORE.implode('/', $packages).'/'.$type.'.'.$className.'.php';
					break;
				case 'lib':
					$path = self::$folder.'/includes/libs/'.implode('/', $packages).'/'.$type.'.'.$className.'.php';
					break;
				case 'app':
					$appName = array_shift($packages);
					$target = array_shift($packages);
					$package = '';
					if(!empty($packages))
						$package = implode('/', $packages).'/';
					$path = self::$folder.'/includes/applications/'.$appName.'/'.$target.'/'.$package.$type.'.'.$className.'.php';

					break;
			}

			if(!empty($path) && file_exists($path))
			{
				require_once($path);
				return true;
			}

			if(array_key_exists($pClassName, $this->exeptions))
			{
				require_once(self::$folder.$this->exeptions[$pClassName]);
				return true;
			}

			switch($type)
			{
				case 'interface':
					trigger_error('Impossible de charger l\'interface <b>'.$pClassName.'</b>.', E_USER_ERROR);
					break;
				case 'model':
					trigger_error('Impossible de charger le model <b>'.$pClassName.'</b>.', E_USER_ERROR);
					break;
				default:
				case 'class':
					trigger_error('Impossible de charger la classe <b>'.$pClassName.'</b>.', E_USER_ERROR);
					break;
			}

			return false;
		}


		/**
		 *
		 * @param string $pScript
		 * @return void
		 */
		static public function addScript($pScript)
		{
			if(preg_match('/\.js$/', $pScript))
			{
				$script = (strpos($pScript, 'http') === 0) ? $pScript : Core::$path_to_components . '/' . $pScript;
				if(!in_array($script, self::getInstance()->scripts, true))
					self::getInstance()->scripts[] = $script;
			}
			else
			{
				if(!in_array($pScript, self::getInstance()->scriptDependencies, true))
					self::getInstance()->scriptDependencies[] = $pScript;
			}
		}


		/**
		 * @static
		 * @param string $pStyleSheet
		 * @param bool   $pInThemeFolder
		 * @return void
		 */
		static public function addStyle($pStyleSheet, $pInThemeFolder = true)
		{
			if($pInThemeFolder)
				$pStyleSheet = Core::$path_to_theme.'/css/'.$pStyleSheet;
			if(!in_array($pStyleSheet, self::getInstance()->styles, true))
				self::getInstance()->styles[] = $pStyleSheet;
		}


		/**
		 * @static
		 * @return array
		 */
		static public function scripts()
		{
			if(!empty(self::getInstance()->scriptDependencies))
				self::getInstance()->scripts[] = 'statique/dependencies/?need='.implode(',', self::getInstance()->scriptDependencies);
			return self::getInstance()->scripts;
		}


		/**
		 * @static
		 * @return array
		 */
		static public function styles()
		{
			return self::getInstance()->styles;
		}


		/**
		 * @static
		 * @param string $pClass
		 * @return Autoload
		 */
		static public function getInstance($pClass = '')
		{
			return parent::getInstance(__CLASS__);
		}
	}
}