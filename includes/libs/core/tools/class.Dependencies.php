<?php
namespace core\tools
{

    use core\application\Application;
    use core\application\Core;
    use core\application\Dictionary;
    use core\application\Header;
    use core\system\File;
    use core\data\SimpleJSON;
    use core\utils\Stack;
    use Exception;
    use JetBrains\PhpStorm\NoReturn;

    /**
     * Class Dependencies
     * Gère deux types de dépendences JS & CSS
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.4
     */
    class Dependencies
    {
        /**
         * Chemin du fichier manifest
         */
        const MANIFEST = "includes/components/manifest.json";

        /**
         * Séparateur des librairies dans l'url
         */
        const NEED_SEPARATOR = ',';

        const TYPE_JS = "javascript";

        const TYPE_CSS = "css";

        static private string $current_folder;

        private string $output = "";

        private array $manifest;

        private string $type;

        private array $configuration;

        /**
         * Constructor
         * @param string $pType
         * @throws Exception
         */
        public function __construct(string $pType = self::TYPE_JS)
        {
            $this->type = $pType;
            switch($this->type)
            {
                case self::TYPE_JS:
                    Header::contentType("application/javascript");
                    break;
                case self::TYPE_CSS:
                    Header::contentType("text/css");
                    break;
            }

            /**
             * Load manifest
             */
            if(!file_exists(self::MANIFEST))
                $this->output($this->log("Manifest file '".self::MANIFEST."' not found", "error"));

            $this->manifest = SimpleJSON::import(self::MANIFEST);

            $this->configuration = $this->manifest["config"] ?? [];
            unset($this->manifest["config"]);

            /**
             * Cache
             */
            $cacheDuration = Stack::get("cache.duration", $this->configuration);
            if(!empty($cacheDuration))
            {
                $eTag = md5($_GET["need"]);
                Header::handleCache($eTag, $cacheDuration);
            }
        }

        /**
         * @throws Exception
         */
        #[NoReturn]
        public function retrieve():void
        {
            /**
             * Check get vars
             */
            $need = Core::checkRequiredGetVars("need")?explode(self::NEED_SEPARATOR, $_GET["need"]):[];

            if(empty($need))
                $this->output($this->log("No lib to load", "warn"));

            $needs = [];

            $this->calculateNeeds($need, $needs);

            $needs = array_unique($needs);

            if($this->type == self::TYPE_JS){
                $dict = SimpleJSON::encode(Dictionary::term("public"));
                $this->output = <<<DIC
const DICTIONARY = $dict;
DIC;

            }

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
                        $absolute_link = preg_match('/^http(s*):\/\//', $files[$i], $matches);
                        if(!$absolute_link)
                        {
                            $files[$i] = dirname(self::MANIFEST)."/".$this->configuration["relative"].$files[$i];
                            $content = File::read($files[$i]);
                            self::$current_folder = dirname($files[$i]);
                            if($this->type == self::TYPE_CSS)
                            {
                                $content = preg_replace_callback('/(url\(\")([^\"]+)/', 'core\tools\Dependencies::correctUrls', $content);
                            }
                            $this->output .= $content."\r\n";
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
            if($accept_gzip)
            {
                Header::contentEncoding("gzip");
                $this->output = gzencode($this->output);
            }

            $this->output($this->output);
        }


        private function calculateNeeds(array $pNeeded, array &$pFinalList):void
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


        private function log(string $pText, string $pLevel='log'):string
        {
            return match ($this->type) {
                self::TYPE_JS => "console." . $pLevel . "('Dependencies : " . addslashes($pText) . "');" . PHP_EOL,
                self::TYPE_CSS => "/* Dependencies -" . $pLevel . "- : " . $pText . " */" . PHP_EOL,
                default => "",
            };
        }


        #[NoReturn]
        private function output(string $pContent):void
        {
            Header::contentLength(strlen($pContent));
            echo $pContent;
            Core::endApplication();
        }

        /**
         * Méthode de correction des urls des assets utilisés dans les CSS
         * @param array $pMatches
         * @return string
         */
        static private function correctUrls(array $pMatches):string
        {
            if(strpos($pMatches[2], 'data:image')>-1)
            {
                return $pMatches[0];
            }
            return $pMatches[1].Application::getInstance()->getPathPart().'../../'.self::$current_folder.'/'.$pMatches[2];
        }
    }
}
