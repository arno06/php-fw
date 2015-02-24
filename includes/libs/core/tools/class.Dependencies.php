<?php
namespace core\tools
{
    use core\application\Core;
    use core\system\File;
    use core\data\SimpleJSON;

    /**
     * Class Dependencies
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.1
     * @todo minified + cache
     */
    class Dependencies
    {
        const MANIFEST = "includes/components/manifest.json";

        const NEED_SEPARATOR = ',';

        const TYPE_JS = "javascript";

        const TYPE_CSS = "css";

        private $headers;

        private $output = "";

        private $manifest = "";

        private $type;

        public function __construct($pType = self::TYPE_JS)
        {
            $this->type = $pType;
            switch($this->type)
            {
                case self::TYPE_JS:
                    $this->headers = array("Content-Type"=>"application/javascript");
                    break;
                case self::TYPE_CSS:
                    $this->headers = array("Content-Type"=>"text/css");
                    break;
            }
        }

        public function retrieve()
        {
            /**
             * Check get vars
             */
            $need = Core::checkRequiredGetVars("need")?explode(self::NEED_SEPARATOR, $_GET["need"]):array();

            if(empty($need))
                $this->output($this->log("No lib to load", "warn"));

            /**
             * Load manifest
             */
            if(!file_exists(self::MANIFEST))
                $this->output($this->log("Manifest file '".self::MANIFEST."' not found", "error"));

            $this->manifest = SimpleJSON::import(self::MANIFEST);

            $config = isset($this->manifest["config"])?$this->manifest["config"]:array();
            unset($this->manifest["config"]);

            $needs = array();

            $this->calculateNeeds($need, $needs);

            $needs = array_unique($needs);

            /**
             * Check Cache File (not sure this should be done now)
             * TBD
             */

            /**
             * Get lib contents
             */
            foreach($needs as $lib)
            {
                if(isset($this->manifest[$lib]))
                {
                    if(!isset($this->manifest[$lib][$this->type])
                        ||!is_array($this->manifest[$lib][$this->type]))
                    {
                        $this->output .= $this->log($lib." is not available", "warn");
                        continue;
                    }

                    $files = $this->manifest[$lib][$this->type];

                    for($i = 0, $max = count($files); $i<$max;$i++)
                    {
                        $absolute_link = preg_match('/^http\:\/\//', $files[$i], $matches);
                        if(!$absolute_link)
                        {
                            $files[$i] = dirname(self::MANIFEST)."/".$config["relative"].$files[$i];
                            $this->output .= File::read($files[$i])."\r\n";
                        }
                        else
                            $this->output .= Request::load($files[$i]);
                    }
                }
                else
                    $this->output .= $this->log($lib." is not available", "warn");
            }   


            /**
             * Minified / Uglyflied / gzip
             */

            $accept_gzip = preg_match('/gzip/', $_SERVER['HTTP_ACCEPT_ENCODING'], $matches)&&!Core::checkRequiredGetVars("output");
            if(false&&$accept_gzip)
            {
                $this->headers["Content-Encoding"] = "gzip";
                $this->output = gzencode($this->output);
            }

            $this->output($this->output);
        }

        private function calculateNeeds($pNeeded, &$pFinalList)
        {

            foreach($pNeeded as $lib)
            {
                if(isset($this->manifest[$lib]))
                {
                    array_unshift($pFinalList, $lib);
                    if(!isset($this->manifest[$lib]["need"])
                        ||!is_array($this->manifest[$lib]["need"])
                        ||empty($this->manifest[$lib]["need"]))
                        continue;
                    $dep = array_reverse($this->manifest[$lib]["need"]);
                    $this->calculateNeeds($dep, $pFinalList);
                }
                else
                    $this->output .= $this->log($lib." is not available", "warn");
            }
        }

        private function log($pText, $pLevel='log')
        {
            switch($this->type)
            {
                case self::TYPE_JS:
                    return "console.".$pLevel."('Dependencies : ".addslashes($pText)."');".PHP_EOL;
                    break;
                case self::TYPE_CSS:
                    return "#Dependencies -".$pLevel."- : ".$pText.PHP_EOL;
                    break;
            }
            return "";
        }

        private function output($pContent)
        {

            $this->headers["Content-Length"] = strlen($pContent);

            foreach($this->headers as $n=>$v)
            {
                header($n.": ".$v);
            }

            echo $pContent;
            exit();
        }
    }
}