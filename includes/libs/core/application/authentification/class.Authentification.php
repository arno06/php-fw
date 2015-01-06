<?php
namespace core\application\authentification
{
	use \core\application\Configuration;
	use core\models\ModelAuthentification;
	use core\tools\debugger\Debugger;

	/**
	 * Class Authentification
	 * Permet de gérer les différentes sessions d'identifications via un Login, un Mot de passe et un jeton "unique"
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .2
	 * @package application
	 * @subpackage authentification
	 */
	class Authentification
	{
		/**
		 * Nom de base de la variable de session
		 * @var String
		 */
		protected $sessionVar = "authentification_";

		/**
		 * Indique la valeur des permissions alouées &agrave; l'utilisateur
		 * @var int
		 */
		public $permissions;

		/**
		 * Mot de passe
		 * @var String
		 */
		protected $mdp_user;

		/**
		 * Login
		 * @var String
		 */
		protected $login_user;

		/**
		 * Jeton
		 * @var String
		 */
		protected $token;

		/**
		 * Données de l'utilisateur si son authentification est vérifiée
		 * @var	Array
		 */
		public $data;

		/**
		 * Constructor
		 */
		public function __construct()
		{
			$this->sessionVar .= Configuration::$site_application;
			if(!isset($_SESSION[$this->sessionVar])
				||!is_array($_SESSION[$this->sessionVar]))
			{
				$this->checkIfNoLogged();
				return;
			}
			$this->parseSessionVar();
			$this->checkIfLogged();
		}

		/**
		 * Méthode de vérification de l'identité de l'utilisateur (dans le cas où on aura detecté une session correspondante)
		 * @return boolean
		 */
		public function checkIfLogged()
		{
			if(!$this->login_user||!$this->mdp_user||!$this->token)
			{
				$this->checkIfNoLogged();
				return;
			}
			$token = $this->getToken($this->mdp_user);
			if(ModelAuthentification::isUser($this->login_user, $this->mdp_user)&&$token==$this->token)
			{
				$this->permissions = ModelAuthentification::$data[Configuration::$authentification_fieldPermissions];
				$this->data = ModelAuthentification::$data;
			}
			else
				$this->unsetAuthentification();
		}

		/**
		 * @return void
		 */
		private function checkIfNoLogged()
		{
			ModelAuthentification::isUser($this->login_user, $this->mdp_user);
			$this->data = ModelAuthentification::$data;
		}


		/**
		 * Méthode de définition des variables de session pour l'instance d'authentification en cours
		 * @param  $pLogin
		 * @param  $pMdp
		 * @param bool $pAdmin
		 * @return bool
		 */
		public function setAuthentification($pLogin, $pMdp, $pAdmin = false)
		{
			$pMdp = md5($pMdp);
			if(ModelAuthentification::isUser($pLogin, $pMdp))
			{
				$lvl = AuthentificationHandler::$permissions[AuthentificationHandler::USER];
				if($pAdmin)
					$lvl = AuthentificationHandler::$permissions[AuthentificationHandler::ADMIN];
				$isAutorized = $lvl&ModelAuthentification::$data[Configuration::$authentification_fieldPermissions];

				if($isAutorized)
				{
					$token = $this->getToken($pMdp);
					$_SESSION[$this->sessionVar] = array("login_user"=>$pLogin, "mdp_user"=>$pMdp,"token"=>$token);
					return true;
				}
			}
			return false;
		}


		/**
		 * Méthode de parsing des variables de la session d'authentification en cours
		 * @return void
		 */
		protected function parseSessionVar()
		{
			foreach($_SESSION[$this->sessionVar] as $name=>$value)
			{
				if(property_exists("core\\application\\authentification\\Authentification",$name))
					$this->$name = $value;
			}
		}


		/**
		 * Méthode de suppression des variables de session pour l'instance d'authentification en cours
		 * @return void
		 */
		public function unsetAuthentification()
		{
			$_SESSION[$this->sessionVar] = array();
			unset($_SESSION[$this->sessionVar]);
		}


		/**
		 * Méthode de définition du jeton
		 * @param String $pMdp		Mot de passe hashé
		 * @return String
		 */
		protected function getToken($pMdp)
		{
			return md5($_SERVER["REMOTE_ADDR"].$pMdp);
		}
	}
}
