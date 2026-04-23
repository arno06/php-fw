<?php
namespace core\tools\captcha
{
    class Challenge
    {
        public function __construct(array $pSetup = null, array $pConfig = null){
            $this->initFrom($pConfig);
            if(is_null($pSetup)){
                $this->generateSetup();
            }else{
                $this->initFrom($pSetup);
            }
        }

        /**
         * @return mixed
         * @throws \Exception
         */
        protected function generateSetup(){
            throw new \Exception("must be overriden");
        }

        private function initFrom($pSetup){
            foreach($pSetup as $name=>$value){
                if(!property_exists($this, $name)){
                    continue;
                }
                $this->$name = $value;
            }
        }
    }
}