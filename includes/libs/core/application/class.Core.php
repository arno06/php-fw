<?php
namespace core\application {

    use core\data\SimpleJSON;
    use core\tools\debugger\Debugger;
    use core\db\DBManager;
    use core\application\routing\RoutingHandler;
    use core\utils\CLI;
    use Exception;
    use JetBrains\PhpStorm\NoReturn;


    /**
     * Noyau central
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 5.0
     * @package application
     */
    abstract class Core
    {
        /**
         * Version en cours du framework
         */
        const VERSION = "5.0";

        /**
         * Erreur de configuration
         */
        const ERROR_CONFIG = "Unable to parse the base configuration file \"includes/applications/config.json\". Please check the data formatting (quotation marks, commas, accents ...).";

        /**
         * @var string
         */
        static public string $config_file;

        /**
         * Définit le chemin vers le dossier de l'application en cours
         * @var string
         */
        static public string $path_to_application;

        /**
         * Définit le chemin vers le dossier des composants js/css
         * @var string
         */
        static public string $path_to_components = "includes/components";

        /**
         * Contient l'url requêtée (sans l'application ni la langue)
         * @var string
         */
        static public string $url;

        static public Application|null $application;

        /**
         * Définit le module en cours - front ou back
         * @var string
         */
        static public string $module;

        /**
         * Définit le nom du controller
         * @var string
         */
        static public string $controller;

        /**
         * Définit le nom de l'action
         * @var string
         */
        static public string $action;

        /**
         * Fait référence &agrave; l'instance du controller en cours
         * @var DefaultController|null
         */
        static private DefaultController|null $instance_controller;

        /**
         * Détermine sur la requête est déclenchée par une requête asynchrone (type XMLHttpRequest)
         * @var bool
         */
        static public bool $request_async = false;

        /**
         * Initialisation du Core applicatif du framework
         * @return void
         */
        static public function init():void
        {
            session_name(Configuration::$global_session);
            session_start();
            set_error_handler('\core\tools\debugger\Debugger::errorHandler');
            set_exception_handler('\core\tools\debugger\Debugger::exceptionHandler');
            self::$request_async = (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
                $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest");
        }

        /**
         * Instanciation des objects globlaux de l'application
         * @return void
         */
        static public function defineGlobalObjects():void
        {
            if (self::debug())
                Debugger::prepare();
        }

        /**
         * Méthode d'identification de l'environnement en fonction du domaine
         * @param string $pFile
         */
        static public function checkEnvironment(string $pFile = "includes/applications/setup.json"):void
        {
            try{
                $setup = SimpleJSON::import($pFile);
            }
            catch(Exception $e){
                trigger_error("Une erreur est apparue lors du chargement du fichier d'environnements ".$e->getMessage(), E_USER_WARNING);
                $setup = false;
            }
            self::$config_file = "/includes/applications/dev.config.json";
            if (!$setup) {
                return;
            }
            foreach ($setup as $env => $domains) {
                foreach ($domains as $domain) {
                    if((isset($_SERVER["SERVER_NAME"])&&$_SERVER["SERVER_NAME"] === $domain)|| (CLI::isCurrentContext() && $domain == PHP_SAPI)){
                        self::$config_file = "/includes/applications/" . $env . ".config.json";
                        break 2;
                    }
                    else if (str_starts_with($domain, "*")) {
                        $domain = str_replace("*", "", $domain);
                        $domain = str_replace(".", "\.", $domain);
                        if (preg_match('/' . $domain . '$/', $_SERVER["SERVER_NAME"])) {
                            self::$config_file = "/includes/applications/" . $env . ".config.json";
                            break 2;
                        }
                    }
                }
            }
            self::setConfiguration();
        }

        /**
         * Méthode statique de définition de l'objet Configuration via le fichier JSON
         * Récupération + parsing du fichier JSON
         * Défintition des propriétés statiques de l'objet Configuration
         * @param string|null $pConfigurationFile Url du fichier de configuration
         * @return void
         */
        static public function setConfiguration(string $pConfigurationFile = null):void
        {
            if (is_null($pConfigurationFile))
                $pConfigurationFile = Autoload::$folder . self::$config_file;
            $configurationData = array();
            try {
                $configurationData = SimpleJSON::import($pConfigurationFile);
            } catch (Exception $e) {
                if ($pConfigurationFile == Autoload::$folder . self::$config_file)
                    trigger_error(self::ERROR_CONFIG."<div>".$e->getMessage()."</div>", E_USER_ERROR);
            }

            foreach ($configurationData as $prefix => $property) {
                if (property_exists('core\application\Configuration', $prefix)) {
                    Configuration::$$prefix = $property;
                    continue;
                }
                if (is_array($property)) {
                    foreach ($property as $name => $value) {
                        $n = $prefix . "_" . $name;
                        if (property_exists('core\application\Configuration', $n))
                            Configuration::$$n = $value;
                    }
                }
            }
            if (!empty($configurationData['extra'])) {
                Configuration::setExtra($configurationData['extra']);
            }

            Configuration::fromEnvVars();
        }

        /**
         * Méthode de parsing de l'url en cours
         * récupère le controller, l'action, la langue (si multilangue) ainsi que les paramètres $_GET
         * @param string|null $pUrl
         * @return void
         */
        static public function parseURL(string $pUrl = null):void
        {
            Configuration::$server_domain = $_SERVER["SERVER_NAME"];
            $protocol = "http" . (self::isHttps() ? 's' : '') . "://";
            Configuration::$server_folder = preg_replace('/\/(index).php$/', "", $_SERVER["SCRIPT_NAME"]);
            Configuration::$server_folder = preg_replace('/^\//', "", Configuration::$server_folder);
            Configuration::$server_url = $protocol . Configuration::$server_domain . "/";
            if (!empty(Configuration::$server_folder))
                Configuration::$server_url .= Configuration::$server_folder . "/";

            /**
             * Définition de l'url + suppression des paramètres GET ?var=value
             */
            $url = $pUrl ?? $_SERVER["REQUEST_URI"];
            if (preg_match("/([^?]*)\?.*$/", $url, $matches)) {
                $url = $matches[1];
            }

            $application_name = RoutingHandler::extractApplication($url);

            self::$application = Application::getInstance()->setup($application_name);
            self::$application->setModule(RoutingHandler::extractModule($url, self::$application->getModulesAvailable()));
            self::$module = self::$application->getModule()->name;

            Configuration::$server_url .= self::$application->getUrlPart();

            $access = self::$application->getPathPart();

            self::$path_to_components = Configuration::$server_url . $access . self::$path_to_components;

            self::defineGlobalObjects();


            if (self::$application->multiLanguage) {
                self::$application->currentLanguage = RoutingHandler::extractLanguage($url);

                if (empty(self::$application->currentLanguage)) {
                    self::$application->currentLanguage = self::$application->defaultLanguage;
                    Header::location(Configuration::$server_url . self::$application->currentLanguage . "/" . $url);
                }
            }

            self::$path_to_application = Application::getInstance()->getFilesPath();

            self::setDictionary();

            self::$url = $url;

            $parsedURL = RoutingHandler::parse($url);

            if(is_null($parsedURL)){
                return;
            }

            self::$controller = str_replace("-", "_", $parsedURL["controller"]);
            self::$action = str_replace("-", "_", $parsedURL["action"]);

            if (isset($parsedURL["parameters"]) && is_array($parsedURL["parameters"]) && is_array($_GET)) {
                $_GET = array_merge($_GET, $parsedURL["parameters"]);
            }
        }

        /**
         * Méthode vérifiant l'existance et retournant une nouvelle instance du controller récupéré
         * Renvoie vers la page d'erreur 404 si le fichier contenant le controller n'existe pas
         * Stop l'application et renvoie une erreur si le fichier existe mais pas la classe demandée
         * @return DefaultController
         */
        static public function getController():DefaultController
        {
            if (Core::$controller === "statique") {
                self::$instance_controller = new StaticController();
                return self::$instance_controller;
            }
            $seo = Dictionary::seoInfos(self::$controller, self::$action);
            $controller_file = self::$path_to_application . "/modules/" . self::$module . "/controllers/controller." . self::$controller . ".php";
            $controller = 'app\\' . self::$application . '\\controllers\\' . self::$module . '\\' . self::$controller;
            if (!file_exists($controller_file)) {
                $defaultController = self::$application->getModule()->defaultController;
                if (call_user_func_array(array($defaultController, "isFromDB"), array(self::$controller, self::$action, self::$url))) {
                    $controller = self::$controller = $defaultController;
                    self::$action = "prepareFromDB";
                } else
                    Go::to404();
            } else
                include_once($controller_file);
            if (!class_exists($controller)) {
                if (self::debug())
                    trigger_error("Controller <b>" . self::$controller . "</b> introuvable", E_USER_ERROR);
                else
                    Go::to404();
            }
            self::$instance_controller = new $controller();
            if (isset($seo["title"]))
                self::$instance_controller->setTitle($seo["title"]);
            if (isset($seo["description"]))
                self::$instance_controller->setDescription($seo["description"]);
            return self::$instance_controller;
        }

        /**
         * Méthode permettant de définir le dictionnaire en fonction d'un fichier de langue
         * @return void
         */
        static public function setDictionary():void
        {
            $dictionary_path = self::$path_to_application . "/localization/" . Application::getInstance()->currentLanguage . ".json";
            try {
                $data = SimpleJSON::import($dictionary_path);
            } catch (Exception $e) {
                if (self::debug())
                    trigger_error('An error occured while importing dictionary file "<b>' . $dictionary_path . '</b>" <div>'.$e->getMessage().'</div>', E_USER_ERROR);
                else {
                    Application::getInstance()->currentLanguage = Application::getInstance()->defaultLanguage;
                    Go::to404();
                }
            }
            $seo = array();
            $terms = array();
            $alias = array();
            if (isset($data["terms"]) && is_array($data["terms"]))
                $terms = $data["terms"];
            if (isset($data["seo"]) && is_array($data["seo"]))
                $seo = $data["seo"];
            if (isset($data["alias"]) && is_array($data["alias"]))
                $alias = $data["alias"];
            Dictionary::defineLanguage(Application::getInstance()->currentLanguage, $terms, $seo, $alias);
        }

        /**
         * Méthode vérifiant l'existance de la méthode action dans la classe controller précédemment instanciée
         * @return string
         */
        static public function getAction():string
        {
            if (!method_exists(self::$instance_controller, self::$action))
                Go::to404();
            return self::$action;
        }

        /**
         * Méthode de récupération du template par défault en fonction du controller et de l'action demandée
         * @return string
         */
        static public function getTemplate():string
        {
            return self::$controller . "/" . self::$action . ".tpl";
        }

        /**
         * Méthode de vérification si l'application est disponible en mode développeur (en fonction du config.json et de l'authentication)
         * @return bool
         */
        static public function debug():bool
        {
            $authHandler = Application::getInstance()->authenticationHandler;
            return Configuration::$global_debug || call_user_func_array(array($authHandler, "is"), array($authHandler::DEVELOPER));
        }

        /**
         * @static
         * @return bool
         */
        static public function isBot():bool
        {
            return !in_array(preg_match("/(Googlebot\/|bingbot\/|Yahoo)/i", $_SERVER["HTTP_USER_AGENT"]), [0, false]);
        }

        /***
         * Méthode permettant d'afficher simplement un contenu sans passer par le système de templating
         * Sert notamment dans le cadre de requêtes asychrones (avec du Flash ou du JS par exemple)
         * @param string $pContent Contenu &agrave; afficher
         * @param string $pType Type de contenu &agrave; afficher - doit être spécifié pour assurer une bonne comptatilité &agrave; l'affichage
         * @return void
         */
        #[NoReturn]
        static public function performResponse(string $pContent, string $pType = "text"):void
        {
            $pType = strtolower($pType);
            $content = match ($pType) {
                "json" => "application/json",
                "xml" => "application/xml",
                "text" => "text/plain",
                default => $pType,
            };
            Header::contentType($content);
            echo $pContent;
            self::endApplication();
        }

        /**
         * Méthode de vérification de l'existance de variables GET
         * @return bool
         */
        static public function checkRequiredGetVars():bool
        {
            $gets = func_get_args();
            for ($i = 0, $max = count($gets); $i < $max; $i++) {
                if (empty($_GET[$gets[$i]]))
                    return false;
            }
            return true;
        }

        /**
         * @static
         * @param DefaultController|null $pController
         * @param string|null $pAction
         * @param string $pTemplate
         * @return void
         */
        static public function execute(DefaultController $pController = null, string $pAction = null, string $pTemplate = ""):void
        {
            if ($pController != "statique")
                $pController->setTemplate(self::$controller, self::$action, $pTemplate);
            if (!is_null($pAction))
                $pController->$pAction();
            if (!Core::$request_async) {
                Header::contentType("text/html");
                $pController->render();
                if (Core::debug()){
                    Debugger::getInstance()->render();
                }
            } else {
                $return = $pController->getGlobalVars();
                if (Core::debug()) {
                    $return = array_merge($return, Debugger::getInstance()->getGlobalVars());
                }
                if (Core::checkRequiredGetVars('render') && $_GET["render"] !== "false")
                    $return["html"] = $pController->render(false);
                $response = SimpleJSON::encode($return);
                $type = "json";
                self::performResponse($response, $type);
            }
        }

        /**
         * Méthode appelée afin de clore l'application
         * @param int $pExitCode
         * @return void
         */
        #[NoReturn]
        static public function endApplication(int $pExitCode = 0):void
        {
            self::$instance_controller = null;
            self::$application = null;
            Singleton::dispose();
            DBManager::dispose();
            exit($pExitCode);
        }


        static private function isHttps():bool
        {
            return (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')
                || isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == '443';
        }
    }
}
