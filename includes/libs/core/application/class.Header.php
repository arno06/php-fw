<?php
namespace core\application
{
	/**
	 * Class Header
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package core\application
	 */
	class Header
	{
		/**
		 * Se charge d'effectuer une redirection HTTP
		 * @param string $pURL	Url cible de la redirection
		 * @param int $pCode	Code HTTP à envoyer par défaut 301
		 */
		static public function location($pURL ,$pCode = 301)
		{
			header("Location:".$pURL, true, $pCode);
			exit();
		}

		/**
		 * Modifie le MIME Type présent dans l'entête HTTP de la réponse
		 * @param string $pValue	ex : text/html, application/xml...
		 * @param string|bool $pCharset	Définit le charset à spécifier
		 */
		static public function content_type($pValue, $pCharset = false)
		{
			if(!$pCharset)
				$pCharset = Configuration::$site_encoding;
			header("Content-Type: ".$pValue."; charset=".$pCharset);
		}

		/**
		 * Modifie le status de l'entête HTTP de la réponse
		 * @param string $pValue
		 */
		static public function status($pValue)
		{
			header("status: ".$pValue);
		}

		/**
		 * @param string $pValue
		 */
		static public function http($pValue)
		{
			header("HTTP/".$pValue);
		}
	}
}
