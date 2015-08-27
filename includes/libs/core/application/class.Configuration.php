<?php
namespace core\application
{
	/**
	 * Class Configuration
	 * Sert de référence pour n'importe quelle propriété de configuration du framework
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
		static public $applications;

		/**
		 * @var string
		 */
		static public $global_encoding = "UTF-8";

		/**
		 * Définit si Query génère automatiquement des requêtes Explain sur les Select
		 * @var bool
		 */
		static public $global_explainOnSelect = true;

        /**
		 * Définit l'email de contact du site
		 * @var string
		 */
		static public $global_emailContact = "";

		/**
		 * Nom attribué à la session de l'application
		 * @var String
		 */
		static public $global_session = "fw_php";

		/**
		 * Tableau des permissions disponibles sur le site
		 * @var array
		 */
		static public $global_permissions = array();

		/**
		 * Définit si Smarty supprime les retours à la ligne à l'écriture des fichiers de cache des templates
		 * @var bool
		 */
		static public $global_inlineHTMLCode = false;

        /**
         * @var bool
         */
        static public $global_debug = false;

		/**
		 * Domaine du serveur
		 * @var string
		 */
		static public $server_domain;

		/**
		 * Dossier de base dans lequel se trouve le framework
		 * @var string
		 */
		static public $server_folder;

		/**
		 * URL du serveur (concaténation du domaine et du dossier)
		 * @var string
		 */
		static public $server_url;

		/**
		 * Définit l'adresse du serveur smtp
		 * @var string
		 */
		static public $server_smtp = "";

		/**
		 * Stock les informations des SGBD
		 * @var array
		 */
		static public $db = array(
			"default"=>array(
				"host"=>"localhost",
				"user"=>"root",
				"password"=>"",
				"name"=>"php-framework",
				"handler"=>"MysqliHandler"
			)
		);

		/**
		 * @var string
		 */
		static public $authentication_tableName = "%s_user";

		/**
		 * @var string
		 */
		static public $authentication_tableId = "id_user";

		/**
		 * @var string
		 */
		static public $authentication_fieldPassword = "mdp_user";

		/**
		 * @var string
		 */
		static public $authentication_fieldLogin = "login_user";

		/**
		 * @var string
		 */
		static public $authentication_fieldPermissions = "permissions_user";
	}
}
