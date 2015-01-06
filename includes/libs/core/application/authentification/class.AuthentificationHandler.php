<?php
namespace core\application\authentification
{
	use core\application\Singleton;
	use core\application\PrivateClass;
	use core\application\Configuration;

	/**
	 * Class AuthentificationHandler gère les différentes authentifications des applications
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .3
	 * @package application
	 * @subpackage authentification
	 */
	class AuthentificationHandler extends Singleton
	{
		const USER      = "USER";

		const ADMIN     = "ADMIN";

		const DEVELOPER = "DEVELOPER";

		const INVITE    = "INVITE";

		/**
		 * Ensemble des permissions acceptées pour l'application
		 * @var array
		 */
		static public $permissions = array(
			self::INVITE    =>  0,
			self::USER		=>	1,
			self::ADMIN		=>	2,
			self::DEVELOPER	=>	4
		);

		/**
		 * Données relatives &agrave; l'utilisateur connecté
		 * @var array
		 */
		static public $data;

		/**
		 * Instance Authenficiation configurée pour un utilisateur
		 * @var Authentification
		 */
		protected $userAuth;


		/**
		 * Constructor
		 * @param PrivateClass $pInstance
		 */
		public function __construct(PrivateClass $pInstance)
		{
			if(!$pInstance instanceOf PrivateClass)
				trigger_error("Il est interdit d'instancier un objet de type <i>Singleton</i> - Merci d'utiliser la méthode static <i>".__CLASS__."::getInstance()</i>", E_USER_ERROR);
			self::$permissions = array_merge(self::$permissions, Configuration::$site_permissions);
			$this->parseUserSession();
		}

		/**
		 * Définit une nouvelle instance Authentification pour un Utilisateur
		 * Définit la variable isUser
		 * @return void
		 */
		protected function parseUserSession()
		{
			$this->userAuth = new Authentification();
			self::$data = $this->userAuth->data;
		}

		/**
		 * Méthode de définition d'une nouvelle session administrateur
		 * Renvoie false si l'administrateur n'existe pas
		 * @param String $pLogin		Login
		 * @param String $pMdp			Mot de passe non hashé
		 * @return boolean
		 */
		static public function setAdminSession($pLogin, $pMdp)
		{
			$i = self::getInstance();
			return $i->userAuth->setAuthentification($pLogin, $pMdp, true);
		}

		/**
		 * Méthode de définition d'une nouvelle session utilisateur
		 * Renvoie false si l'utilisateur n'existe pas
		 * @param String $pLogin		Login
		 * @param String $pMdp			Mot de passe non hashé
		 * @return boolean
		 */
		static public function setUserSession($pLogin, $pMdp)
		{
			$i = self::getInstance();
			return $i->userAuth->setAuthentification($pLogin, $pMdp,  false);
		}

		/**
		 * Méthode de suppression de la session Utilisateur
		 * @return void
		 */
		static public function unsetUserSession()
		{
			$i = self::getInstance();
			$i->userAuth->unsetAuthentification();
		}

		/**
		 * Méthode permettant de savoir si l'utilisateur en cours a le niveau de permission demandé
		 * @param String $pLevel Niveau de permissions &agrave; tester (peuvent être définit dans le fichier de configuration)
		 * @return boolean
		 */
		static public function is($pLevel)
		{
			$i = self::getInstance();

			if(!isset(self::$permissions[$pLevel]))
				return false;
			return $i->userAuth->permissions&self::$permissions[$pLevel];
		}

		static public function isLoggedToBack()
		{
			return (AuthentificationHandler::is(AuthentificationHandler::ADMIN));
		}

		/**
		 * Singleton
		 * @param String $pClassName [optional]
		 * @return AuthentificationHandler
		 */
		static public function getInstance($pClassName = "")
		{
			if(empty($pClassName))
				$pClassName = __CLASS__;
			return parent::getInstance($pClassName);
		}

		/**
		 * ToString()
		 * @return String
		 */
		public function __toString()
		{
			return "[Objet AuthentificationHandler]";
		}

		/**
		 * @return void
		 */
		public function __destruct()
		{
			self::$data = null;
			self::$permissions = null;
		}
	}

}
