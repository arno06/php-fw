<?php
namespace core\tools\captcha
{
    interface InterfaceChallenge
    {
        /**
         * @return array
         */
        public function get():array;

        /**
         * @param string $pValue
         * @return bool
         */
        public function submit(string $pValue):bool;
    }
}