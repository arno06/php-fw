<?php
namespace app\main\controllers\front
{
    use core\application\DefaultController;
    use core\tools\docs\Documentor;

    class index extends DefaultController
    {

        public function __construct()
        {

        }

        public function index()
        {
            $doc = new Documentor();
            $doc->parsePackage('includes/libs/core', 'core');
            $doc->output('files/docs/');
        }
    }
}
