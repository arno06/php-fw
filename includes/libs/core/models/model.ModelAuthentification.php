<?php
namespace core\models
{
    use core\application\BaseModel;
    use core\application\Configuration;
    use core\db\Query;

    /**
     * Model de gestion des authentifications
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .1
     * @package models
     */
    class ModelAuthentification extends BaseModel
    {
        static private $instance;

        static public $data;

        public function __construct()
        {
            $this->table = sprintf(Configuration::$authentification_tableName,Configuration::$site_application);
            $this->id = Configuration::$authentification_tableId;
        }

        static public function isUser($pLogin, $pMdp)
        {
            if(empty($pLogin)||empty($pMdp))
                return false;

            $instance = self::getInstance();

            if($result = $instance->one(Query::condition()->andWhere(Configuration::$authentification_fieldLogin, Query::EQUAL, $pLogin)))
            {
                if($result[configuration::$authentification_fieldPassword] == $pMdp)
                {
                    self::$data = $result;
                    return true;
                }
            }
            return false;
        }

        /**
         * @return ModelAuthentification
         */
        static public function getInstance()
        {
            if(!self::$instance)
                self::$instance = new ModelAuthentification();
            return self::$instance;
        }
    }
}