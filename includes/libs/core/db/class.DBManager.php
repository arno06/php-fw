<?php
namespace core\db
{
    use core\application\Configuration;
    use Exception;
	/**
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package db
	 */
	class DBManager
	{
		static private array|null $handlers = array();


		static public function get(string $pName = "default"):InterfaceDatabaseHandler|null
		{
			if(!array_key_exists($pName, self::$handlers))
			{
                if(!array_key_exists($pName, Configuration::$db))
                {
                    trigger_error("L'identifiant \"".$pName."\" ne correspond &agrave; aucun gestionnaire stocké.");
                    return null;
                }
                self::set($pName, Configuration::$db[$pName]);
			}
			return self::$handlers[$pName];
		}


		static public function set(string $pName, array $pInfo):void
		{
			if(isset(self::$handlers[$pName]))
				trigger_error("L'identifiant \"".$pName."\" est déj&agrave; utilisé. Impossible de stocker le gestionnaire créé.");
			$d = array("handler", "host", "user", "password", "name");
			foreach($d as $l)
			{
				if(!isset($pInfo[$l]))
					$pInfo[$l] = "";
			}
			try
			{
				$instance = new $pInfo["handler"]($pInfo["host"], $pInfo["user"], $pInfo["password"], $pInfo["name"]);
			}
			catch(Exception)
			{
				trigger_error("Une erreur est apparue lors de l'initialisation du gestionnaire \"".$pName."\". Merci de vérifier les informations saisie.", E_USER_ERROR);
			}
			self::$handlers[$pName] = $instance;
		}


		static public function dispose():void
		{
			foreach(self::$handlers as $name=>$instance)
			{
				unset($instance);
				unset(self::$handlers[$name]);
			}
			self::$handlers = null;
		}
	}
}
