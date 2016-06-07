<?php
namespace core\tools\template
{

    use core\application\Core;
    use core\utils\Stack;

    class RenderingContext
    {

        private $file;
        private $data;

        public function __construct($pFile = null)
        {
            $this->file = $pFile;
            $this->data = array();
        }

        public function setFile($pFile)
        {
            $this->file = $pFile;
        }

        public function assign($pName, &$pValue)
        {
            $this->data[$pName] = $pValue;
        }

        public function include_tpl($pName)
        {
            $tpl = new Template($this->data);
            Core::setupRenderer($tpl);
            $tpl->render($pName, true);
            trace("include : ".$pName);
        }

        public function setData($pData)
        {
            $this->data = $pData;
        }

        public function get($pName)
        {
            return Stack::get($pName, $this->data);
        }

        public function render($pDisplay)
        {
            ob_start();
            include_once($this->file);
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