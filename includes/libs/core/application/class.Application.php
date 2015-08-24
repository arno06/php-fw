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

        public function __construct($pName = self::DEFAULT_APPLICATION)
        {
            $this->name = $pName;
            if($pName != self::DEFAULT_APPLICATION)
            {
                $this->url .= $pName."/";
                $this->path .= "../";
            }
            if(!Configuration::$applications[$this->name])
            {
                trigger_error("L'application ".$this->name." n'a pas été définie dans le fichier de configuration.", E_USER_ERROR);
            }

            $data = Configuration::$applications[$this->name];
            $this->theme = $data['theme'];
        }

        public function setModule($pName = Module::DEFAULT_MODULE)
        {
            if($pName != Module::DEFAULT_MODULE)
            {
                $this->url .= $pName."/";
                $this->path .= "../";
            }
            $data = Configuration::$applications[$this->name]['modules'][$pName];
            $this->module = new Module($pName, $data);
        }

        public function getModulesAvailable()
        {
            return array_keys(Configuration::$applications[$this->name]);
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

        public function getName()
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
    }
}