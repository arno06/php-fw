<?php
namespace core\db\handler
{
	use core\tools\debugger\Debugger;
	use core\db\InterfaceDatabaseHandler;

	/**
	 * Couche d'abstraction &agrave; la base de données (type mysql)
	 * Version spécifique au framework PHP
	 *  - Définition des informations relatives &agrave; la base de données en fonction de la class Configuration
	 *
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .7
	 * @package db
	 * @subpackage handler
	 */
	class MysqlHandler implements InterfaceDatabaseHandler
	{
		/**
		 * Chemin d'acc&egrave;s &agrave; la base de données
		 * @var String
		 */
		protected $host;


		/**
		 * Nom d'utilisateur
		 * @var String
		 */
		protected $user;


		/**
		 * Mot de passe d'acc&egrave;s &agrave; la base de données
		 * @var String
		 */
		protected $mdp;

		/**
		 * Nom de la base de données
		 * @var String
		 */
		protected $bdd;


		/**
		 * Ressource de connexion &agrave; la base de données SQL
		 * @var resource
		 */
		public $connexion;


		/**
		 * @param $pHost
		 * @param $pUser
		 * @param $pPassword
		 * @param $pName
		 */
		public function __construct($pHost, $pUser, $pPassword, $pName)
		{
			$this->host = $pHost;
			$this->user = $pUser;
			$this->mdp = $pPassword;
			$this->bdd = $pName;
			$this->connect();
		}


		/**
		 * Destructor
		 * Clos la connexion en cours avec la base
		 * @return void
		 */
		public function __destruct()
		{
			$this->close();
		}


		/**
		 * Méthode de connexion &agrave; la base
		 * Stop l'exécution de l'application si la base n'est pas accessible
		 * @return void
		 */
		protected function connect()
		{

			if(!$this->connexion = mysql_connect($this->host, $this->user, $this->mdp, true))
				trigger_error("Connexion au serveur de gestion de base de données impossible", E_USER_ERROR);
			if(!@mysql_select_db($this->bdd, $this->connexion))
				trigger_error("Impossible de trouver la base de données demandée", E_USER_ERROR);
			mysql_query("SET NAMES 'utf8'");
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
				trigger_error("Une erreur est apparue lors de la requête<br/><b>".$pQuery."</b><br/>Error ".$this->getErrorNumber()." : <i>".$this->getError()."</i>", E_USER_ERROR);
			$return = array();
			while($donnee = mysql_fetch_assoc($result))
				array_push($return, $donnee);
			mysql_free_result($result);
			return $return;
		}


		/**
		 * Méthode de récupération de la clé primaire générée &agrave; la suite d'une insertion
		 * @return Number
		 */
		public function getInsertId()
		{
			return mysql_insert_id($this->connexion);
		}


		/**
		 * Méthode permettant de clore la connexion établie avec la base de donnée
		 * @return void
		 **/
		protected function close()
		{
			mysql_close($this->connexion);
		}


		/**
		 * Méthode permettant de centraliser les commandes &agrave; effectuer avant l'excécution d'une requête
		 * @param String $pQuery				Requête &agrave; excécuter
		 * @return resource
		 */
		public function execute($pQuery)
		{
			Debugger::query($pQuery, "db", $this->bdd);
			return mysql_query($pQuery, $this->connexion);
		}


		/**
		 * @return int
		 */
		public function getErrorNumber()
		{
			return mysql_errno($this->connexion);
		}


		/**
		 * @return string
		 */
		public function getError()
		{
			return mysql_error($this->connexion);
		}


		/**
		 * Méthode permettant de filtrer les données lorsqu'on les récup&egrave;re via une requête &agrave; la base de données
		 * @param String $pValue				Valeur &agrave; filtrer
		 * @return String
		 */
		static public function filterOut($pValue)
		{
			return stripcslashes($pValue);
		}


		/**
		 * ToString()
		 * @return String
		 */
		public function __toString()
		{
			return '[Object MysqlHandler host="'.$this->host.'" database="'.$this->bdd.'" user="'.$this->user.'"]';
		}
	}

}