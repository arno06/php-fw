<?php
namespace core\application
{
	/**
	 * Classe privée de vérification d'un singleton
	 */
	class PrivateClass{}
	/**
	 * Class d'implémentation d'un singleton PHP 5.2.x
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .2
	 * @package application
	 */
	abstract class Singleton
	{
		/**
		 * Tableau contenant les instances des Singletons invoqués
		 * @var Array
		 */
		protected static $instances = array();

		/**
		 * Méthode de récupération de l'instance de la classe en cours
		 * @param string $pClassName
		 * @return Object
		 */
		public static function getInstance($pClassName = "")
		{
			if(empty($pClassName))
				return null;
			if(!isset(self::$instances[$pClassName]))
				self::$instances[$pClassName] = new $pClassName(new PrivateClass());
			return self::$instances[$pClassName];
		}

		/**
		 * Méthode de suppression des instances des différents singletons
		 * Déclenche la méthode __destructor() sur ces instances
		 * @return void
		 */
		public static function dispose()
		{
			foreach(self::$instances as &$i)
				unset($i);
			self::$instances = null;
		}

		/**
		 * Clone
		 * @return void
		 */
		public function __clone()
		{
			trigger_error("Impossible de clôner un object de type Singleton", E_USER_ERROR);
		}
	}
}
