<?php
namespace core\application
{

    use JetBrains\PhpStorm\NoReturn;

    /**
	 * Class Header
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .4
	 * @package core\application
	 */
	class Header
	{
        static private array $pool = array();

        /**
         * Méthode de gestion systématique des headers HTTP de cache
         * @param string $pETag     jeton
         * @param int $pDuration    Durée du cache
         */
        static public function handleCache(string $pETag, int $pDuration):void
        {
            self::$pool["Cache-Control"] = "max-age=".$pDuration.", public";
            self::$pool["ETag"] = $pETag;

            if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && isset($_SERVER['HTTP_IF_NONE_MATCH']))
            {
                $if_modified_since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
                $if_none_match = $_SERVER['HTTP_IF_NONE_MATCH'];
                $expires = $if_modified_since+$pDuration;

                if($if_none_match == $pETag && (time() < $expires))
                {
                    self::$pool['HTTP/1.1 304 Not Modified'] = '';
                    self::$pool["Expires"] = gmdate("D, d M Y H:i:s", $expires)." GMT";
                    self::write();
                    Core::endApplication();
                }
            }

            self::$pool["Last-Modified"] = gmdate("D, d M Y H:i:s", time())." GMT";
            self::$pool["Expires"] = gmdate("D, d M Y H:i:s", time() + $pDuration)." GMT";
            self::write();
        }

        /**
         * Méthode de définition des différents header en fonction du pool prédéfinit
         */
        static private function write():void
        {
            foreach(self::$pool as $n=>$v)
            {
                if(empty($v))
                {
                    header($n);
                }
                else
                {
                    header($n.": ".$v);
                }
            }
        }

        /**
         * Gère les requêtes de type OPTIONS
         * @param array $pDomain liste des domaines autorisés
         * @param array $pMethods liste des méthodes HTTP autorisées
         * @param array $pHeaders liste des headers autorisés
         */
        #[NoReturn]
        static public function handleOptionsRequest(array $pDomain = array("*"), array $pMethods = array("GET"), array $pHeaders = array('Content-Type')):void
        {
            self::allowOrigin($pDomain);
            header('Access-Control-Allow-Methods: '.implode(", ", $pMethods));
            header('Access-Control-Allow-Headers: '.implode(", ", $pHeaders));
            Core::endApplication();
        }

        static public function allowOrigin(array $pDomain = array('*')):void
        {
            header('Access-Control-Allow-Origin: '.implode(", ", $pDomain));
        }

		/**
		 * Se charge d'effectuer une redirection HTTP
		 * @param string $pURL	Url cible de la redirection
		 * @param int $pCode	Code HTTP à envoyer par défaut 301
         * @return void
		 */
        #[NoReturn]
		static public function location(string $pURL , int $pCode = 301):void
		{
			header("Location:".$pURL, true, $pCode);
            Core::endApplication();
		}

		/**
		 * Modifie le MIME Type présent dans l'entête HTTP de la réponse
		 * @param string $pValue	ex : text/html, application/xml...
		 * @param string|bool $pCharset	Définit le charset à spécifier
         * @return void
		 */
		static public function contentType(string $pValue, string|bool $pCharset = false):void
		{
			if(!$pCharset)
				$pCharset = Configuration::$global_encoding;
			header("Content-Type: ".$pValue."; charset=".$pCharset);
		}


        static public function contentEncoding(string$pValue):void
        {
            header("Content-Encoding: ".$pValue);
        }


        static public function contentLength(int $pValue):void
        {
            header("Content-Length: ".$pValue);
        }

		/**
		 * Modifie le status de l'entête HTTP de la réponse
		 * @param string $pValue
         * @return void
		 */
		static public function status(string $pValue):void
		{
			header("status: ".$pValue);
		}


		static public function http(string $pValue):void
		{
			header("HTTP/".$pValue);
		}
	}
}
