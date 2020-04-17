<?php
namespace core\middleware
{

    use core\application\Configuration;
    use core\application\Core;
    use core\tools\debugger\Debugger;

    class CoreMiddleware implements InterfaceMiddleware
    {
        static public function execute($pUrl): bool
        {
            session_name(Configuration::$global_session);
            session_start();
            set_error_handler('\core\tools\debugger\Debugger::errorHandler');
            set_exception_handler('\core\tools\debugger\Debugger::exceptionHandler');
            Core::$request_async = (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
                $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest");
            if (Core::debug())
                Debugger::prepare();
            return false;
        }

    }
}