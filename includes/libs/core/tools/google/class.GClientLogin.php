<?php

namespace core\tools\google
{
	use core\tools\Request;
	use \Exception;

	/**
	 * Class GClientLogin
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package core\tools
	 * @subpackage google
	 */
	class GClientLogin
	{
		const TYPE_GOOGLE = "GOOGLE";

		const URL_LOGIN = "https://www.google.com/accounts/ClientLogin";

		const SOURCE = "Php-GClientLogin";

		/**
		 * @var string
		 */
		protected $account_type;

		/**
		 * @var string
		 */
		protected $email;

		/**
		 * @var string
		 */
		protected $password;

		/**
		 * @var string
		 */
		protected $service;

		/**
		 * @var string
		 */
		private $SID = "";

		/**
		 * @var string
		 */
		private $LSID = "";

		/**
		 * @var string
		 */
		private $Auth = "";


		/**
		 * constructor
		 * @param string $pEmail
		 * @param string $pPasswd
		 * @param string $pService
		 * @param string $pAccountType
		 */
		public function __construct($pEmail, $pPasswd, $pService, $pAccountType = self::TYPE_GOOGLE)
		{
			$this->email = $pEmail;
			$this->password = $pPasswd;
			$this->service = $pService;
			$this->account_type = $pAccountType;
			$this->login();
		}


		/**
		 * Méthode de demande d'authentification pour le service google souhaité
		 * @return void
		 */
		private function login()
		{
			$r = new Request(self::URL_LOGIN);
			$r->setOption(CURLOPT_FOLLOWLOCATION, true);
			$r->setOption(CURLOPT_SSL_VERIFYPEER, 0);
			$data = array('accountType' => $this->account_type,
				'Email' => $this->email,
				'Passwd' => $this->password,
				'source'=> self::SOURCE,
				'service'=> $this->service);
			$r->setDataPost($data);
			$retour = $r->execute();
			$retour = preg_replace("/(\n|\r)/", "", $retour);
			$toParse = array("Auth", "LSID", "SID");
			foreach($toParse as $var)
			{
				if(preg_match("/".$var."=(.*)$/", $retour, $extract, PREG_OFFSET_CAPTURE))
				{
					try
					{
						$this->$var = $extract[1][0];
						$retour = preg_replace("/".$var."=.*$/", "", $retour);
					}
					catch(Exception $e){return;}
				}
				else
					return;
			}
		}


		/**
		 * @return bool
		 */
		public function isAuth()
		{
			return !empty($this->Auth);
		}


		/**
		 * @return string
		 */
		public function getAuth()
		{
			return $this->Auth;
		}


		/**
		 * @return string
		 */
		public function getSID()
		{
			return $this->SID;
		}


		/**
		 * @return string
		 */
		public function getLSID()
		{
			return $this->LSID;
		}
	}
}