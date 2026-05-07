<?php
namespace core\tools\template
{
    use core\utils\Stack;

    /**
     * Class RenderingContext
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @package core\tools\template
     * @version 1.0
     */
    class RenderingContext
    {
        private string|null $file;

        private array $data;

        private string $templateDir;

        private string $cacheDir;


        public function __construct(string $pFile = null)
        {
            $this->file = $pFile;
            $this->data = array();
        }


        public function prepare(string $pTemplateDir, string $pCacheDir):void
        {
            $this->templateDir = $pTemplateDir;
            $this->cacheDir = $pCacheDir;
        }


        public function setFile(string $pFile):void
        {
            $this->file = $pFile;
        }


        /**
         * Méthode d'ajout de l'assignation d'une variable
         * @param string $pName
         * @param mixed $pValue
         */
        public function assign(string $pName, mixed &$pValue):void
        {
            $this->data[$pName] = $pValue;
        }


        public function includeTpl(string $pName, array $pExtra = array()):void
        {
            $tpl = new Template(array_merge($this->data, $pExtra));
            $tpl->setup($this->templateDir, $this->cacheDir);
            $tpl->render($pName);
        }

        /**
         * @param string $pSeparator    Chaine de caractère servant de glue
         * @param array $pData          Tableau à parcourir
         * @param bool $pEcho           Default "true", définit si on affiche le résultat
         * @return null|string
         */
        public function implode(string $pSeparator, array $pData, bool$pEcho = true):string|null
        {
            $res = implode($pSeparator, $pData);
            if($pEcho)
            {
                echo $res;
                return null;
            }
            return $res;
        }


        /**
         * Méthode de définition du tableau de données
         * @param array $pData
         */
        public function setData(array $pData):void
        {
            $this->data = $pData;
        }


        public function get(string $pName, array $pModifiers = array()):mixed
        {
            $value = Stack::get($pName, $this->data);
            if($value&&!empty($pModifiers))
            {
                foreach($pModifiers as $m)
                {
                    if(is_callable($m)||($m = TemplateModifiers::get($m)))
                        $value = call_user_func($m, $value);
                }
            }
            return $value;
        }


        public function render(bool $pDisplay):bool|string
        {
            if(!file_exists($this->file)){
                trigger_error("Template file '".$this->file."' not found", E_USER_ERROR);
            }
            ob_start();
            include($this->file);
            $rendering = ob_get_contents();
            ob_end_clean();
            if($pDisplay)
            {
                echo $rendering;
                return true;
            }
            return $rendering;
        }
    }
}
