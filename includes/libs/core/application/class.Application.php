<?php
namespace core\application
{
    /**
     * Class Application
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.1
     * @package core\application
     */
    class Application extends Singleton
    {
        const DEFAULT_APPLICATION = "main";

        private string $name = "";

        private Module $module;

        private string $url = "";

        private string $relative_path = "";

        public bool $multiLanguage = false;

        public string $currentLanguage = "fr";

        public string $defaultLanguage = "fr";

        public string $dbHandler = "default";

        public string $authenticationHandler = "core\\application\\authentication\\AuthenticationHandler";

        /**
         * @param PrivateClass $pInstance
         */
        public function __construct(PrivateClass $pInstance)
        {

        }


        public function setup(string $pName = self::DEFAULT_APPLICATION):Application
        {
            $this->name = $pName;
            if($pName != self::DEFAULT_APPLICATION)
            {
                $this->url .= $pName."/";
                $this->relative_path .= "../";
            }
            if(!Configuration::$applications[$this->name])
            {
                trigger_error("L'application ".$this->name." n'a pas été définie dans le fichier de configuration.", E_USER_ERROR);
            }

            $data = Configuration::$applications[$this->name];

            $props = get_class_vars(__CLASS__);
            foreach($props as $n=>$p)
            {
                if(isset($data[$n]))
                {
                    $this->{$n} = $data[$n];
                }
            }
            return $this;
        }


        public function setModule(string $pName = Module::DEFAULT_MODULE):void
        {
            if($pName != Module::DEFAULT_MODULE)
            {
                $this->url .= str_replace('_', '-', $pName)."/";
                $this->relative_path .= "../";
            }
            $data = Configuration::$applications[$this->name]['modules'][$pName];
            $this->module = new Module($pName, $data);
        }


        public function getModulesAvailable():array
        {
            return array_keys(Configuration::$applications[$this->name]['modules']);
        }


        public function getUrlPart():string
        {
            return $this->url;
        }


        public function getPathPart():string
        {
            return $this->relative_path;
        }


        public function getTemplatesCachePath():string
        {
            return $this->getFilesPath()."/_cache/".$this->module->name;
        }


        public function getTemplatesPath():string
        {
            return $this->getFilesPath()."/modules/".$this->module->name."/views";
        }


        public function getFilesPath():string
        {
            return Autoload::$folder."/includes/applications/".$this->name;
        }


        public function getModule():Module
        {
            return $this->module;
        }


        public function __toString()
        {
            return $this->name;
        }

    }

    /**
     * Class Module
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.0
     * @package core\application
     */
    class Module
    {
        const DEFAULT_MODULE = "front";

        /**
         * @var string
         */
        public string $name = self::DEFAULT_MODULE;

        /**
         * @var bool
         */
        public bool $useRoutingFile = true;

        /**
         * @var string
         */
        public string $defaultController = "core\\application\\DefaultController";

        /**
         * @var string
         */
        public string $action404 = "not_found";

        /**
         * @param string $pName
         * @param array $pData
         */
        public function __construct(string $pName, array $pData)
        {
            $this->name = $pName;
            $props = get_class_vars(__CLASS__);
            foreach($props as $n=>$p)
            {
                if(isset($pData[$n]))
                {
                    $this->{$n} = $pData[$n];
                }
            }
        }
    }
}
