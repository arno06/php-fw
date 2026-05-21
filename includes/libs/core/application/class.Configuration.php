<?php
namespace core\application
{

    use core\utils\Stack;
    use ReflectionClass;

    /**
	 * Class Configuration
	 * Sert de référence aux propriétés de configuration du framework
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.1
	 * @package application
	 */
	abstract class Configuration
	{
		/**
         * Définit les applications disponibles ainsi que leurs configurations associées
		 * @var array
		 */
		static public array $applications;

		static public string $global_encoding = "UTF-8";

		/**
		 * Définit si Query génère automatiquement des requêtes Explain sur les Select
		 * @var bool
		 */
		static public bool $global_explainOnSelect = true;

        /**
		 * Définit l'email de contact du site
		 * @var string
		 */
		static public string $global_emailContact = "";

		/**
		 * Nom attribué à la session de l'application
		 * @var string
		 */
		static public string $global_session = "fw_php";

		/**
		 * Tableau des permissions disponibles sur le site
		 * @var array
		 */
		static public array $global_permissions = [];

        static public bool $global_debug = false;

		/**
		 * Domaine du serveur
		 * @var string
		 */
		static public string $server_domain;

		/**
		 * Dossier de base dans lequel se trouve le framework
		 * @var string
		 */
		static public string $server_folder;

		/**
		 * URL du serveur (concaténation du domaine et du dossier)
		 * @var string
		 */
		static public string $server_url;

		/**
		 * Définit l'adresse du serveur smtp
		 * @var string
		 */
		static public string $server_smtp = "";

		/**
		 * Stock les informations des SGBD
		 * @var array
		 */
		static public array $db = [
			"default"=>[
				"host"=>"localhost",
				"user"=>"root",
				"password"=>"",
				"name"=>"fwphp",
				"handler"=>"\\core\\data\\handler\\MysqliHandler"
            ]
		];


		static public string $authentication_tableName = "%s_users";

		static public string $authentication_tableId = "id_user";

		static public string $authentication_fieldPassword = "password_user";

		static public string $authentication_fieldLogin = "login_user";

		static public string $authentication_fieldPermissions = "permissions_user";

        static private array $_extra = [];


        static public function setExtra(array $pExtra):void
        {
            self::$_extra = $pExtra;
        }


        static public function extra(string $pId):mixed
        {
            $env = getenv(str_replace(".", "_", strtoupper($pId)));
            if($env !== false){
                return $env;
            }
            return Stack::get($pId, self::$_extra);
        }


        static public function fromEnvVars():void
        {
            $ref = new ReflectionClass(Configuration::class);
            $props = $ref->getStaticProperties();
            foreach($props as $name=>$val){
                $env = getenv(strtoupper($name));
                if(!str_contains($name, 'global_') || $env === false){
                    continue;
                }
                $env = in_array($env, ["true", "false"])?$env==="true":$env;
                self::$$name = $env;
            }
        }
	}
}
