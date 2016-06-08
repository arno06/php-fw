<?php
namespace core\tools\template
{
    class TemplateModifiers
    {
        static private $list = [];

        static public function get($pName)
        {
            if(isset(self::$list[$pName])&&is_callable(self::$list[$pName]))
                return self::$list[$pName];
            return null;
        }

        static public function set($pName, $pMethod)
        {
            self::$list[$pName] = $pMethod;
        }
    }
}