<?php
namespace core\db\handler
{
	use core\tools\debugger\Debugger;
	use \mysqli_result;
	use \mysqli;

	/**
	 * Couche d'abstraction &agrave; la base de données (type mysql improved)
	 * Version spécifique au framework cbi
	 *  - Définition des informations relatives &agrave; la base de données en fonction de la class Configuration
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .2
	 * @package core\db\handler
	 */
	class MysqliHandler extends MysqlHandler
	{
		/**
		 * Instance mysqli
		 * @var mysqli
		 */
		private $mysqliInstance;

		protected function close()
		{
            if($this->mysqliInstance->connect_error)
                return;
			if($this->mysqliInstance->store_result())
				$this->mysqliInstance->store_result()->free();
			$this->mysqliInstance->close();
		}

		protected function connect()
		{
            $this->mysqliInstance = new mysqli($this->host, $this->user, $this->mdp, $this->bdd);
			if($this->mysqliInstance->connect_error)
				trigger_error("Connexion au serveur de gestion de base de données impossible", E_USER_ERROR);
		}

		/**
		 * Méthode permettant de récupérer les donnée d'une requêtes SQL
		 * Renvoie les données renvoyées sous forme d'un tableau associatif
		 * @param String $pQuery				Requête SQL brute
		 * @return array
		 */
		public function getResult($pQuery)
		{
			$result = $this->execute($pQuery);
			if(!$result)
				trigger_error("Une erreur est apparue lors de la requête <b>".$pQuery."</b><<br/>Error ".$this->getErrorNumber()." : <i>".$this->getError()."</i>", E_USER_ERROR);
			$return = array();
			while($donnee = $result->fetch_assoc())
			{
				array_push($return, $donnee);
			}
			$result->free();
			return $return;
		}

		/**
		 * @return string
		 */
		public function getError()
		{
			return $this->mysqliInstance->error;
		}

		/**
		 * @return int
		 */
		public function getErrorNumber()
		{
			return $this->mysqliInstance->errno;
		}


		/**
		 * Méthode de récupération de la clé primaire générée &agrave; la suite d'une insertion
		 * @return Number
		 */
		public function getInsertId()
		{
			return $this->mysqliInstance->insert_id;
		}

		/**
		 * Méthode permettant de centraliser les commandes &agrave; effectuer avant l'excécution d'une requête
		 * @param String $pQuery				Requête &agrave; excécuter
		 * @return mysqli_result
		 */
		public function execute($pQuery)
		{
			Debugger::query($pQuery, "db", $this->bdd);
			return $this->mysqliInstance->query($pQuery);
		}

		/**
		 * ToString()
		 * @return String
		 */
		public function __toString()
		{
			return '[Object MysqliHandler database="'.$this->bdd.'" user="'.$this->user.'"]';
		}
	}
}