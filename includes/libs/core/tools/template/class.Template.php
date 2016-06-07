<?php
namespace core\tools\template
{

    use core\system\File;

    class Template
    {
        public $cacheDir;
        public $templateDir;
        public $templateFile;
        private $templatePath;

        private $cacheFile;
        private $cachePath;

        public $safeMode = true;
        public $cacheEnabled = true;

        private $context;

        public function __construct($pDefaultData = null)
        {
            $this->context = new RenderingContext();
            if(!is_null($pDefaultData))
            {
                $this->context->setData($pDefaultData);
            }
        }

        public function assign($pName, &$pValue)
        {
            $this->context->assign($pName, $pValue);
        }

        public function setup($pTemplateDir, $pCacheDir)
        {
            $currentDir = dirname($_SERVER['SCRIPT_FILENAME']).'/';
            $this->templateDir = $currentDir.$pTemplateDir;
            $this->cacheDir = $pCacheDir;
        }

        public function render($pTemplateFile, $pDisplay = true)
        {
            $this->templateFile = $pTemplateFile;
            $this->templatePath = $this->templateDir."/".$this->templateFile;
            $this->cacheFile = str_replace("/", "%", $this->templateFile).".php";
            $this->cachePath = $this->cacheDir."/".$this->cacheFile;

            $this->context->setFile($this->cachePath);

            if($this->pullFromCache())
            {
                $this->execute($pDisplay);
                return;
            }

            $this->evaluate();
            $this->execute($pDisplay);
        }

        private function pullFromCache()
        {
            if(!$this->cacheEnabled)
                return false;

            if(!file_exists($this->cachePath))
                return false;

            $cacheTime = filemtime($this->cachePath);

            /** Cache is no more valid */
            if($cacheTime < filemtime($this->templatePath))
                return false;

            return true;
        }

        private function storeInCache($pContent)
        {
            if(!$this->cacheEnabled)
                return;

            if(file_exists($this->cachePath))
            {
                unlink($this->cachePath);
            }

            file_put_contents($this->cachePath, $pContent);
        }

        private function execute($pDisplay = true)
        {
            trace("execute");
            $this->context->render($pDisplay);
        }

        private function evaluate()
        {
            try
            {
                $content = File::read($this->templatePath);
            }
            catch (\Exception $e)
            {
                trigger_error("Le fichier '".$this->templateFile."' n'existe pas.", E_USER_WARNING);
                return;
            }
            
            $startTime = microtime(true);

            trace_r(htmlentities($content));

            $otag = TemplateDictionary::$TAGS[0];
            $etag = TemplateDictionary::$TAGS[1];

            $content = $this->escapeBlock($content, $otag."*", "*".$etag);

            if($this->safeMode)
            {
                $content = $this->escapeBlock($content, "<?php", "?>");
            }

            $to = TemplateDictionary::$TAGS[0];
            $tc = TemplateDictionary::$TAGS[1];

            $blocks = "[a-z]+";

            $re_block = "/(\\".$to."(".$blocks.")|\\".$to."\/(".$blocks."))([^\\".$tc."]*)\\".$tc."/i";


//            $re_vars = "/\\$([a-z\_\.]+)/i";

            /**
            $content = preg_replace_callback($re_vars, function($pMatches)
            {
                trace_r($pMatches);
                return '$toto1';
            }, $content);*/

            trace($re_block);

            $step = 0;
            $opened = [];
            $content = preg_replace_callback($re_block, function($pMatches){
                global $step;
                global $opened;
                global $re_object;

                $opener = !empty(trim($pMatches[2]));
                $name = $opener?$pMatches[2]:$pMatches[3];
                $params = trim($pMatches[4]);

                switch($name)
                {
                    case "if":
                        if($opener)
                        {
                            $params = preg_replace('/\\$([a-z0-9\.\_]+)/i', '$this->get("$1")', $params);
                            return "<?php if(".$params."): ?>";
                        }
                        else
                        {
                            return "<?php endif; ?>";
                        }
                        break;
                    case "foreach":
                        if($opener)
                        {
                            $step++;
                            $opened[$step] = true;

                            $default = ["key"=>'key', "item"=>'value'];

                            $this->parseParameters($params, $default);

                            $table = str_replace('$', '', $default["from"]);

                            $array_var = 'data_'.$step;
                            $var = '$'.$array_var.'='.$this->extractVar($table).';';

                            return '<?php '.$var.' if($'.$array_var.'&&is_array($'.$array_var.')&&!empty($'.$array_var.')):
foreach($'.$array_var.' as $'.$default['key'].'=>$'.$default['item'].'): $this->assign("'.$default['item'].'", $'.$default['item'].'); $this->assign("'.$default['key'].'", $'.$default['key'].'); ?>';
                        }
                        else
                        {
                            $array_var = 'data_'.$step;
                            $extra = isset($opened[$step])?"endforeach; unset(\$".$array_var."); ":"";
                            unset($opened[$step--]);
                            return "<?php ".$extra."endif; ?>";
                        }
                        break;
                    case "foreachelse":
                        unset($opened[$step]);
                        $array_var = 'data_'.$step;
                        return "<?php endforeach; unset(\$".$array_var."); else: ?>";
                        break;
                    case "else":
                        return "<?php else: ?>";
                        break;
                    case "include":
                        $default = array();
                        $this->parseParameters($params, $default);
                        return "<?php \$this->include_tpl('".$default["file"]."'); ?>";
                        break;
                    default:

                        $re_object = "/\\".TemplateDictionary::$TAGS[0]."([a-z0-9\\.\\_]+)(\\-\\>[a-z\\_]+)*\\".TemplateDictionary::$TAGS[1]."/i";
                        preg_match($re_object, $pMatches[0], $matches);

                        trace_r($matches);

                        if(isset($matches)&&!empty($matches))
                        {
                            return "<?php ".$this->extractVar($matches[1]).$matches[2]."(); ?>";
                        }

                        trace_r($pMatches);

                        return $pMatches[0];
                        break;
                }
            }, $content);
            unset($step);
            unset($opened);

            $re_vars = $otag."\\$([a-z0-9\\.\\_]+)([a-z\\_\\\\|]+)*".$etag;

            $content = preg_replace_callback("/".$re_vars."/i", function($pMatches){
                $var = $this->extractVar($pMatches[1]);
                if(isset($pMatches[2])&&!empty($pMatches[2]))
                {
                    $modifiers = explode("|", $pMatches[2]);
                    $modifiers = array_reverse($modifiers);
                    foreach($modifiers as $m)
                    {
                        $m = trim($m);
                        if(empty($m))
                            continue;
                        $var = $m."(".$var.")";
                    }
                }
                if(isset($pMatches[3])&&!empty($pMatches[3]))
                {
                    trace_r($pMatches[3]);
                }
                return '<?php echo '.$var.'; ?>';
            }, $content);

            $endTime = microtime(true);

            trace("evaluate duration : ".($endTime-$startTime)." ");

            trace_r(htmlentities($content));

            $this->storeInCache($content);
        }

        private function escapeBlock($content, $pStartTag, $pEndTag)
        {
            while(($s = strpos($content, $pStartTag))!==false)
            {
                $e = strpos($content, $pEndTag)+2;
                $length = $e-$s;
                $content = substr_replace($content, "", $s, $length);
            }
            return $content;
        }

        private function parseParameters($pString, &$pParams)
        {
            $p = explode(" ", $pString);
            foreach($p as $pv)
            {
                $v = explode("=", $pv);
                $pParams[trim($v[0])] = str_replace("'", "", str_replace('"', '', trim($v[1])));
            }
        }

        private function extractVar($pId)
        {
            return '$this->get("'.$pId.'")';
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