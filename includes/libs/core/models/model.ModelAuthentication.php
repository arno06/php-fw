<?php
namespace core\models
{
    use core\application\BaseModel;
    use core\application\Configuration;
    use core\application\Core;
    use core\db\Query;

    /**
     * Model de gestion des authentifications
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.0
     * @package models
     */
    class ModelAuthentication extends BaseModel
    {
        static private ModelAuthentication|null $instance = null;

        static public array $data;


        public function __construct()
        {
            parent::__construct(sprintf(Configuration::$authentication_tableName,Core::$application), Configuration::$authentication_tableId);
        }


        static public function checkLoginAndHash(string $pLogin, string $pHash):bool
        {
            if(empty($pLogin)||empty($pHash))
                return false;

            $instance = self::getInstance();

            if($result = $instance->one(Query::condition()->andWhere(Configuration::$authentication_fieldLogin, Query::EQUAL, $pLogin)))
            {
                if($result[Configuration::$authentication_fieldPassword] == $pHash)
                {
                    self::$data = $result;
                    return true;
                }
            }
            return false;
        }


        static public function isUser(string $pLogin, string $pMdp):bool
        {
            if(empty($pLogin)||empty($pMdp))
                return false;

            $instance = self::getInstance();

            if($result = $instance->one(Query::condition()->andWhere(Configuration::$authentication_fieldLogin, Query::EQUAL, $pLogin)))
            {
                if(password_verify($pMdp, $result[configuration::$authentication_fieldPassword]))
                {
                    self::$data = $result;
                    return true;
                }
            }
            return false;
        }


        public function createUser(string $pLogin, string $pPassword, int $pPermissions = 1):mixed
        {
            $data = array(Configuration::$authentication_fieldLogin=>$pLogin,
                Configuration::$authentication_fieldPassword=>password_hash($pPassword, PASSWORD_BCRYPT, array("cost"=>10)),
                Configuration::$authentication_fieldPermissions=>$pPermissions,
            );
            return $this->insert($data);
        }


        static public function getInstance():ModelAuthentication
        {
            if(!self::$instance)
                self::$instance = new ModelAuthentication();
            return self::$instance;
        }
    }
}
