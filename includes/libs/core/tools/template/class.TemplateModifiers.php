<?php
namespace core\tools\template
{
    /**
     * Class TemplateModifiers
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .1
     * @package core\tools\template
     */
    class TemplateModifiers
    {
        static private array $list = [];


        static public function get(string $pName):string|null
        {
            if(isset(self::$list[$pName])&&is_callable(self::$list[$pName]))
                return self::$list[$pName];
            return null;
        }


        static public function set(string $pName, string $pMethod):void
        {
            self::$list[$pName] = $pMethod;
        }
    }
}
