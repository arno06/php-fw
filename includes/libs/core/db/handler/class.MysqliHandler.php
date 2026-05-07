<?php
namespace core\db\handler
{

    use core\db\InterfaceDatabaseHandler;
    use core\tools\debugger\Debugger;
    use mysqli;
    use mysqli_result;
    use Exception;

    /**
     * Couche d'abstraction à la base de données (type mysql improved)
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.2
     * @package core\db\handler
     */
    class MysqliHandler implements InterfaceDatabaseHandler
    {
        static private array $specials = array(
            "NOW()",
            "NULL"
        );

        /**
         * Chemin d'acc&egrave;s à la base de données
         * @var string
         */
        protected string $host;


        /**
         * Nom d'utilisateur
         * @var string
         */
        protected string $user;


        /**
         * Mot de passe d'acc&egrave;s à la base de données
         * @var string
         */
        protected string $mdp;


        /**
         * Nom de la base de données
         * @var string
         */
        protected string $bdd;

        private mysqli $mysqliInstance;


        public function __construct(string $pHost, string $pUser, string $pPassword, string $pName)
        {
            $this->host = $pHost;
            $this->user = $pUser;
            $this->mdp = $pPassword;
            $this->bdd = $pName;
            $this->connect();
        }


        public function __destruct()
        {
            $this->close();
        }


        protected function close():void
        {
            if($this->mysqliInstance->connect_error)
                return;
            try{
                $store_result = $this->mysqliInstance->store_result();
                if($store_result)
                    $store_result->free();
            }catch(Exception){}
            $this->mysqliInstance->close();
        }


        protected function connect():void
        {
            $this->mysqliInstance = new mysqli($this->host, $this->user, $this->mdp, $this->bdd);
            if($this->mysqliInstance->connect_error)
                trigger_error("Connexion au serveur de gestion de base de données impossible (".$this->bdd.")", E_USER_ERROR);
        }


        public function getError():string
        {
            return $this->mysqliInstance->error;
        }


        public function getErrorNumber():int
        {
            return $this->mysqliInstance->errno;
        }


        /**
         * Méthode de récupération de la clé primaire générée à la suite d'une insertion
         * @return int
         */
        public function getInsertId():int
        {
            return $this->mysqliInstance->insert_id;
        }

        /**
         * Méthode permettant de centraliser les commandes à effectuer avant l'excécution d'une requête
         * @param string $pQuery				Requête à excécuter
         * @param bool   $pRaw
         * @return mysqli_result|array|string|bool
         */
        public function execute(string $pQuery, bool $pRaw = false):mysqli_result|array|string|bool
        {
            Debugger::query($pQuery, "db", $this->bdd);
            try{
                $result = $this->mysqliInstance->query($pQuery);
            }
            catch(Exception){
                trigger_error("Une erreur est apparue lors de la requête <b>".$pQuery."</b><br/><a href='https://www.google.com/search?q=mysql+error+".$this->getErrorNumber()."' target='_blank'>Error ".$this->getErrorNumber()."</a> : <i>".$this->getError()."</i>", E_USER_WARNING);
                return false;
            }
            if($pRaw){
                return $result;
            }
            $return = array();
            while($data = $result->fetch_assoc())
            {
                $return[] = $data;
            }
            $result->free();
            return $return;
        }


        public function __toString()
        {
            return '[Object MysqliHandler database="'.$this->bdd.'" user="'.$this->user.'"]';
        }


        public function escapeValue(string $pString):string
        {
            if(!in_array(strtoupper($pString), self::$specials))
                return "'".$this->mysqliInstance->escape_string($pString)."'";
            return strtoupper($pString);
        }
    }
}