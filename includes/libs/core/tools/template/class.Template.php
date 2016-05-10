<?php
namespace core\tools\template
{

    use core\system\File;

    class Template
    {
        public $cacheDir;
        public $templateDir;

        public $templateFile;

        public function __construct()
        {

        }

        public function render($pTemplateFile, $pDisplay = true)
        {
            $this->templateFile = $this->templateDir."/".$pTemplateFile;

            //Check cache existence
            //Check cache validity
            //Check template existence

            try
            {
                $content = File::read($this->templateFile);
            }
            catch (\Exception $e)
            {
                trigger_error("Le fichier '".$this->templateFile."' n'existe pas.", E_USER_WARNING);
                return;
            }

            $this->evaluate($content);
        }

        private function evaluate($pContent)
        {
            $startTime = microtime(true);

            trace_r(htmlentities($pContent));

            $to = TemplateDictionary::$TAGS[0];
            $tc = TemplateDictionary::$TAGS[1];

            $blocks = "[a-z]+";

            $re_block = "/(\\".$to."(".$blocks.")|\\".$to."\/(".$blocks."))([^\\".$tc."]*)\\".$tc."/i";

            trace($re_block);

            preg_match_all($re_block, $pContent, $matches);

            trace_r($matches);

            for($i = 0, $max = count($matches[0]); $i<$max;$i++)
            {
                $opener = !empty($matches[2][$i]);

                if($opener)
                {
                    $params = trim($matches[4][$i]);
                    switch($matches[2][$i])
                    {
                        case "if":
                            $pContent = str_replace($matches[0][$i], "if (".$params."):", $pContent);
                            break;
                    }
                }
                else
                {
                    switch($matches[3][$i])
                    {
                        case "if":
                            $pContent = str_replace($matches[0][$i], "endif;", $pContent);
                            break;
                    }
                }
            }


            $endTime = microtime(true);
            trace("evaluate duration : ".($endTime-$startTime)." ");

            trace_r(htmlentities($pContent));
        }

        private function parseBlock()
        {

        }
    }

    class TemplateDictionary
    {
        static public $TAGS = ["{", "}"];
        static public $BLOCKS = [
            "foreach",
            "if"
        ];
        static public $NEUTRALS = [
            "foreachelse",
            "else"
        ];
    }
}