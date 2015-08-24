<?php
namespace core\application
{
    class Application
    {
        const DEFAULT_APPLICATION = "main";

        private $name;

        private $module;

        private $theme;

        private $url = "";

        private $relative_path = "";

        public $multiLanguage = false;

        public $currentLanguage = "fr";

        public $defaultLanguage = "fr";

        public function __construct($pName = self::DEFAULT_APPLICATION)
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
        }

        public function setModule($pName = Module::DEFAULT_MODULE)
        {
            if($pName != Module::DEFAULT_MODULE)
            {
                $this->url .= $pName."/";
                $this->relative_path .= "../";
            }
            $data = Configuration::$applications[$this->name]['modules'][$pName];
            $this->module = new Module($pName, $data);
        }

        public function getModulesAvailable()
        {
            return array_keys(Configuration::$applications[$this->name]['modules']);
        }

        public function getUrlPart()
        {
            return $this->url;
        }

        public function getPathPart()
        {
            return $this->relative_path;
        }

        public function getThemePath()
        {
            return "themes/".$this->name."/".$this->theme."/".$this->module->name;
        }

        public function getModule()
        {
            return $this->module;
        }

        public function __toString()
        {
            return $this->name;
        }

    }

    class Module
    {
        const DEFAULT_MODULE = "front";

        public $name = self::DEFAULT_MODULE;
        public $useRoutingFile = true;
        public $defaultController = "core\\application\\DefaultController";
        public $action404 = "not_found";

        public function __construct($pName, $pData)
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