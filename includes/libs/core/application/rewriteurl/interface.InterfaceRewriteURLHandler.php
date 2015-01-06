<?php
namespace core\application\rewriteurl
{
	/**
	 * Interface InterfaceRewriteURLHandler - permet d'implémenter les classes gérant la réécriture d'url
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .2
	 * @package application
	 * @subpackage rewriteurl
	 */
	interface InterfaceRewriteURLHandler
	{
		/**
		 * Méthode de parsing d'une url
		 * Renvoie nécessairement un tableau contenant les informations :  controller - action - application - param&egrave;tre
		 * @param String $pUrl			Url &agrave; parser
		 * @return array
		 */
		static function parse($pUrl);

		/**
		 * Méthode d'écriture d'une URL
		 * @param String $pController				Controller &agrave; cibler
		 * @param String $pAction					Action &agrave; appeler sur le controller
		 * @param Array $pParams					Tableau associatif des param&egrave;tres GET souhaités
		 * @param String $pLangue					Langue souhaitée pour l'url
		 * @return String
		 */
		static function rewrite($pController = "", $pAction = "", $pParams = array(), $pLangue = "");
	}
}
