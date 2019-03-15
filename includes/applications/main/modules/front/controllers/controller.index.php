<?php
namespace app\main\controllers\front
{
    use core\application\DefaultController;
    use core\tools\form\Form;

    class index extends DefaultController
    {

        public function __construct()
        {

        }

        public function index()
        {
            $f = new Form("test");
            if($f->isValid()){
                trace_r($f->getValues());
            }
            $this->addForm("test", $f);
        }
    }
}
