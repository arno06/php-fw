<?php

namespace core\middleware
{
    interface InterfaceMiddleware
    {
        /**
         * @param $pUrl
         * @return bool
         */
        static public function execute($pUrl):bool;
    }
}