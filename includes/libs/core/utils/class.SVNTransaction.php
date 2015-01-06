<?php
namespace core\utils
{

	/**
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 0.1
	 * @package core\utils
	 * @todo about everything
	 */
	class SVNTransaction
	{
		/**
		 * Nom d'utilisateur
		 * @var string
		 */
		private $username = "";

		/**
		 * Mot de passe
		 * @var string
		 */
		private $password = "";

		/**
		 * @var bool
		 */
		private $isAvailable;

		/**
		 * @var mixed
		 */
		private $returnVar;

		/**
		 * @param $pUserName
		 * @param $pPassword
		 */
		public function __construct($pUserName, $pPassword)
		{
			$this->username = $pUserName;
			$this->password = $pPassword;

			$r = shell_exec("svn --help");
			$r = trim($r);
			$this->isAvailable = strlen($r) > 0;
		}

		/**
		 * @param $pFile
		 * @return mixed
		 */
		public function add($pFile)
		{
			$this->execute("add", array($pFile), true);
		}

		/**
		 * @param $pMessage
		 */
		public function commit($pMessage)
		{
			$this->execute("commit", array("username"=>$this->username,
				"password"=>$this->password,
				"message"=>'"'.$pMessage.'"',
				"quiet"));
		}

		/**
		 * @return bool
		 */
		public function isAvailable()
		{
			return $this->isAvailable===true;
		}

		/**
		 * TDB
		 * @param $pFile
		 */
		public function delete($pFile)
		{
			$this->execute("delete", array("force-log", '"'.$pFile.'"'));
		}

		/**
		 * @param $pCommand
		 * @param $pParams
		 * @param $pNoArgs
		 * @return void
		 */
		private function execute($pCommand, $pParams, $pNoArgs = false)
		{
			if(isset($this->isAvailable) && !$this->isAvailable)
				return;
			$parameters = "";
			foreach($pParams as $n=>$v)
			{
				$p = $v;
				if(!is_numeric($n))
					$p = $n." ".$p;
				if($pNoArgs == false && !preg_match("/^\"/", $p))
					$parameters.= "--";
				$parameters .= $p." ";
			}
			exec("svn ".$pCommand." ".$parameters, $output, $this->returnVar);
		}

		/**
		 * @return String
		 */
		public function getReturnVar()
		{
			return $this->returnVar;
		}
	}
}