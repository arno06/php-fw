<?php
namespace core\db\handler
{
	use core\db\InterfaceDatabaseHandler;
	use core\tools\debugger\Debugger;
	use SQLite3;

	/**
	 * Couche d'abstraction à la base de données (type sqlite)
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .4
	 * @package core\db\handler
	 */
	class SqliteHandler implements InterfaceDatabaseHandler
	{
        static private array $specials = array(
            "NOW()",
            "NULL"
        );

		protected SQLite3 $sqlite;

		/**
		 * Chemin d'accès à la base de données
		 * @var string
		 */
		protected string $host;

		/**
		 * Nom d'utilisateur
		 * @var string
		 */
		protected string $user;

		/**
		 * Mot de passe d'accès à la base de données
		 * @var string
		 */
		protected string $mdp;

		/**
		 * Nom de la base de données
		 * @var string
		 */
		protected string $bdd;


		public function __construct(string $pHost, string $pUser, string $pPassword, string $pName)
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
		 * Méthode de connexion à la base
		 * Stop l'exécution de l'application si la base n'est pas accessible
		 * @return void
		 */
		protected function connect():void
		{
			if(!$this->sqlite = new SQLite3($this->host, SQLITE3_OPEN_READWRITE))
				trigger_error("Connexion au serveur de gestion de base de données impossible", E_USER_ERROR);
		}

		/**
		 * Méthode permettant de centraliser les commandes à effectuer avant l'excécution d'une requête
		 * @param string $pQuery				Requête à excécuter
         * @param bool   $pRaw
		 * @return array|bool
         */
		public function execute(string $pQuery, bool $pRaw = false): array|bool
        {
			Debugger::query($pQuery, "db", $this->bdd);
            if($pRaw){
                return $this->sqlite->exec($pQuery);
            }
            $result = $this->sqlite->query($pQuery);
            if(!$result){
                trigger_error("Une erreur est apparue lors de la requête <b>".$pQuery."</b>", E_USER_WARNING);
                return false;
            }
            $return = array();
            while($data = $result->fetchArray(SQLITE3_ASSOC))
            {
                $return[] = $data;
            }
            return $return;
		}

		/**
		 * Méthode de récupération de la clé primaire générée à la suite d'une insertion
		 * @return int
		 */
		public function getInsertId():int
		{
			return $this->sqlite->lastInsertRowID();
		}

		/**
		 * Méthode permettant de clore la connexion établie avec la base de données
		 * @return void
		 **/
		protected function close():void
		{
			$this->sqlite->close();
		}


		public function __toString()
		{
			return "Object SqliteHandler";
		}


		public function getErrorNumber():int
		{
            trigger_error("SqliteHandler::getErrorNumber not implemented yet.", E_USER_WARNING);
            return 0;
		}


		public function getError():string
		{
            trigger_error("SqliteHandler::getError not implemented yet.", E_USER_WARNING);
            return "not implemented yet";
		}

        /**
         * Méthode d'échappement de la valeur
         * @param string $pString
         * @return string
         */
        public function escapeValue(string $pString):string
        {
            if(!in_array(strtoupper($pString), self::$specials))
                return "'".SQLite3::escapeString($pString)."'";
            return strtoupper($pString);
        }
    }

}
