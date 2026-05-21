<?php
namespace core\application\authentication
{
    use core\application\Configuration;
    use core\application\Core;
    use core\models\ModelAuthentication;

    /**
     * Class Authentication
     * Permet de gérer les différentes sessions d'identifications via un Login, un Mot de passe et un jeton "unique"
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .2
     * @package application
     * @subpackage authentication
     */
    class Authentication
    {
        /**
         * Nom de base de la variable de session
         * @var String
         */
        protected string $sessionVar = "Authentication_";

        /**
         * Indique la valeur des permissions alouées &agrave; l'utilisateur
         * @var int
         */
        public int $permissions = 0;

        /**
         * Mot de passe
         * @var string
         */
        protected string $mdp_user;

        /**
         * Login
         * @var string
         */
        protected string $login_user;

        /**
         * Jeton
         * @var string
         */
        protected string $token;

        /**
         * Données de l'utilisateur si son authentication est vérifiée
         * @var	array
         */
        public array $data = [];


        public function __construct()
        {
            $this->sessionVar .= Core::$application;
            if(!isset($_SESSION[$this->sessionVar])
                ||!is_array($_SESSION[$this->sessionVar]))
            {
                return;
            }
            $this->parseSessionVar();
            $this->checkIfLogged();
        }

        /**
         * Méthode de vérification de l'identité de l'utilisateur (dans le cas où on aura detecté une session correspondante)
         * @return void
         */
        public function checkIfLogged():void
        {
            if(!$this->login_user||!$this->mdp_user||!$this->token)
            {
                $this->checkIfNoLogged();
                return;
            }
            $token = $this->getToken($this->mdp_user);
            if(ModelAuthentication::checkLoginAndHash($this->login_user, $this->mdp_user)&&$token==$this->token)
            {
                $this->permissions = ModelAuthentication::$data[Configuration::$authentication_fieldPermissions];
                $this->data = ModelAuthentication::$data;
            }
            else
                $this->unsetAuthentication();
        }


        private function checkIfNoLogged():void
        {
            ModelAuthentication::checkLoginAndHash($this->login_user, $this->mdp_user);
            $this->data = ModelAuthentication::$data;
        }


        /**
         * Méthode de définition des variables de session pour l'instance d'authentication en cours
         * @param string $pLogin
         * @param string $pMdp
         * @param bool $pAdmin
         * @return bool
         */
        public function setAuthentication(string $pLogin, string $pMdp, bool $pAdmin = false):bool
        {
            if(ModelAuthentication::isUser($pLogin, $pMdp))
            {
                $lvl = AuthenticationHandler::$permissions[AuthenticationHandler::USER];
                if($pAdmin)
                    $lvl = AuthenticationHandler::$permissions[AuthenticationHandler::ADMIN];
                $isAuthorized = $lvl&ModelAuthentication::$data[Configuration::$authentication_fieldPermissions];

                if($isAuthorized)
                {
                    $pass = ModelAuthentication::$data[Configuration::$authentication_fieldPassword];
                    $token = $this->getToken($pass);
                    $_SESSION[$this->sessionVar] = array("login_user"=>$pLogin, "mdp_user"=>$pass,"token"=>$token);
                    return true;
                }
            }
            return false;
        }


        /**
         * Méthode de parsing des variables de la session d'authentication en cours
         * @return void
         */
        protected function parseSessionVar():void
        {
            foreach($_SESSION[$this->sessionVar] as $name=>$value)
            {
                if(property_exists("core\\application\\Authentication\\Authentication",$name))
                    $this->$name = $value;
            }
        }


        /**
         * Méthode de suppression des variables de session pour l'instance d'authentication en cours
         * @return void
         */
        public function unsetAuthentication():void
        {
            $_SESSION[$this->sessionVar] = array();
            unset($_SESSION[$this->sessionVar]);
        }


        /**
         * Méthode de définition du jeton
         * @param string $pMdp		Mot de passe hashé
         * @return string
         */
        protected function getToken(string $pMdp):string
        {
            return md5($_SERVER["REMOTE_ADDR"].$pMdp);
        }
    }
}
