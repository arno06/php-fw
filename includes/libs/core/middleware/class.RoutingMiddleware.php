<?php

namespace core\middleware
{

    use core\application\Application;
    use core\application\Configuration;
    use core\application\Core;
    use core\application\Header;
    use core\application\routing\RoutingHandler;

    class RoutingMiddleware implements InterfaceMiddleware
    {
        static public function execute($pUrl): bool
        {
            Configuration::$server_domain = $_SERVER["SERVER_NAME"];
            $protocol = "http" . ((isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ? 's' : '') . "://";
            Configuration::$server_folder = preg_replace('/\/(index).php$/', "", $_SERVER["SCRIPT_NAME"]);
            Configuration::$server_folder = preg_replace('/^\//', "", Configuration::$server_folder);
            Configuration::$server_url = $protocol . Configuration::$server_domain . "/";
            if (!empty(Configuration::$server_folder))
                Configuration::$server_url .= Configuration::$server_folder . "/";

            /**
             * Définition de l'url + suppression des paramètres GET ?var=value
             */
            $url = isset($pUrl) && !is_null($pUrl) ? $pUrl : $_SERVER["REQUEST_URI"];
            if (preg_match("/([^\?]*)\?.*$/", $url, $matches)) {
                $url = $matches[1];
            }

            $application_name = RoutingHandler::extractApplication($url);

            Core::$application = Application::getInstance()->setup($application_name);
            Core::$application->setModule(RoutingHandler::extractModule($url, Core::$application->getModulesAvailable()));
            Core::$module = Core::$application->getModule()->name;

            Configuration::$server_url .= Core::$application->getUrlPart();

            $access = Core::$application->getPathPart();

            Core::$path_to_components = Configuration::$server_url . $access . Core::$path_to_components;

            if (Core::$application->multiLanguage) {
                Core::$application->currentLanguage = RoutingHandler::extractLanguage($url);

                if (empty(Core::$application->currentLanguage)) {
                    Core::$application->currentLanguage = Core::$application->defaultLanguage;
                    Header::location(Configuration::$server_url . Core::$application->currentLanguage . "/" . $url);
                }
            }

            Core::$path_to_application = Application::getInstance()->getFilesPath();

            Core::setDictionary();

            Core::$url = $url;

            $parsedURL = RoutingHandler::parse($url);

            if(is_null($parsedURL)){
                return false;
            }

            Core::$controller = str_replace("-", "_", $parsedURL["controller"]);
            Core::$action = str_replace("-", "_", $parsedURL["action"]);

            if (isset($parsedURL["parameters"]) && is_array($parsedURL["parameters"]) && is_array($_GET)) {
                $_GET = array_merge($_GET, $parsedURL["parameters"]);
            }

            Core::execute(Core::getController(), Core::getAction(), Core::getTemplate());
            return true;
        }

    }
}