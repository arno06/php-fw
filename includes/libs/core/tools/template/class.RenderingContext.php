<?php
namespace core\tools\template
{
    use core\utils\Stack;

    class RenderingContext
    {

        private $file;
        private $data;

        public function __construct($pFile)
        {
            $this->file = $pFile;
            $this->data = array();
        }

        public function assign($pName, $pValue)
        {
            $this->data[$pName] = $pValue;
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