<?php
namespace core\tools\captcha
{
    use Exception;

    class Challenge
    {
        /**
         * @param array|null $pSetup
         * @param array|null $pConfig
         * @throws Exception
         */
        public function __construct(array $pSetup = null, array $pConfig = null){
            $this->initFrom($pConfig);
            if(is_null($pSetup)){
                $this->generateSetup();
            }else{
                $this->initFrom($pSetup);
            }
        }

        /**
         * @return void
         * @throws Exception
         */
        protected function generateSetup():void
        {
            throw new Exception("must be overriden");
        }

        private function initFrom(array $pSetup):void
        {
            foreach($pSetup as $name=>$value){
                if(!property_exists($this, $name)){
                    continue;
                }
                $this->$name = $value;
            }
        }
    }
}