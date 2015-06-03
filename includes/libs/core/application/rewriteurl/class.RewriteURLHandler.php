<?php
namespace core\application\rewriteurl
{
	use core\application\Configuration;
	use core\application\Core;
	use core\application\Go;
	use core\application\Dictionary;
	use core\data\SimpleJSON;
	use \Exception;

	/**
	 * Class RewriteURLHandler - gestionnaire par défault de réécriture d'url
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.0
	 * @package application
	 * @subpackage rewriteurl
	 */
	abstract class RewriteURLHandler implements InterfaceRewriteURLHandler
	{
		const REGEXP_LANGUAGE   = '/^([a-z]{2,3})\//';
		const REGEXP_CONTROLLER = '/^([a-z\-]{1,})\//';
		const REGEXP_ACTION     = '/^([a-z\-]{1,})\//';
		const REGEXP_PARAMETERS = '/^(([a-z][a-z0-9\_\-]*:.*)*)\//i';

		/**
		 * Méthode de parsing d'une url
		 * Renvoie nécessairement un tableau contenant les informations :  controller - action - application - paramètre - langue
		 * @param String $pUrl		Url à parser
		 * @return array
		 */
		static public function parse($pUrl)
		{
			$extract = null;

			if(!Core::$isBackoffice)
			{
				return self::handleRoutingRules($pUrl);
			}

			$parameters = array();
			$request_url = $pUrl;
			$controller = self::extractController($request_url);

			if(!empty($controller))
			{
				$action = self::extractAction($request_url);
				if(empty($action))
					$action = "index";
				$parameters = self::extractParameters($request_url);
			}
			else
			{
				$controller = "index";
				$action = "index";
			}
			return array("controller"=>$controller,
				"action"=>$action,
				"parameters"=>$parameters);
		}

		static private function handleRoutingRules($pUrl)
		{
			$request_url = $pUrl;
			$parameters = array();

			$rules_file = Core::$path_to_application."/src/routing_rules.json";
			try
			{
				$rules = SimpleJSON::import($rules_file);
			}
			catch(Exception $e)
			{
				return null;
			}

			$index_parameters = array();

			for($i = 0, $max = count($rules); $i<$max;$i++)
			{
				$rule = $rules[$i];

				$rule['re_url'] = $rule['url'];
				$rule['re_url'] = str_replace('/', '\/', $rule['re_url']);
				$rule['re_url'] = str_replace('.', '\.', $rule['re_url']);

				$index_param = 0;
				if(!isset($rule["parameters"]))
					$rule["parameters"] = array();
				foreach($rule["parameters"] as $name=>$re)
				{
					if(!preg_match('/\{\$'.$name.'\}/', $rule['re_url']))
						continue;

					$index_parameters[++$index_param] = $name;
					$rule['re_url'] = preg_replace('/\{\$'.$name.'\}/', '('.$re.')', $rule['re_url']);
				}

				$rule['re_url'] = "/^".$rule['re_url'].'$/';

				if(!preg_match($rule['re_url'], $request_url, $matches))
					continue;

				for($k = 1, $maxk = count($matches); $k<$maxk; $k++)
				{
					$parameters[$index_parameters[$k]] = $matches[$k];
				}

				if(isset($parameters["controller"])&&!empty($parameters["controller"]))
				{
					$rule["controller"] = $parameters["controller"];
					unset($parameters["controller"]);
				}
				if(isset($parameters["action"])&&!empty($parameters["action"]))
				{
					$rule["action"] = $parameters["action"];
					unset($parameters["action"]);
				}

				return array("controller"=>$rule['controller'],
					"action"=>$rule['action'],
					"parameters"=>$parameters);
			}

			return null;
		}


		/**
		 * Méthode d'écriture d'une URL
		 * @static
		 * @param string $pController
		 * @param string $pAction
		 * @param array $pParams
		 * @param string $pLangue
		 * @return string
		 */
		static public function rewrite($pController = "", $pAction = "", $pParams = array(), $pLangue = "")
		{
			$pController =  self::getAlias($pController);
			$pAction = self::getAlias($pAction);
			$return = "";
			if(!Core::$isBackoffice&&(!isset($pLangue)||empty($pLangue))&&Configuration::$site_multilanguage)
				$return = Configuration::$site_currentLanguage."/";
			elseif(!Core::$isBackoffice&&isset($pLangue)&&!empty($pLangue)&&Configuration::$site_multilanguage)
				$return = $pLangue."/";
			if(!empty($pController))
				$return .= $pController."/";
			if(!empty($pAction))
				$return .= $pAction."/";
			if(isset($pParams)&&is_array($pParams))
			{
				foreach($pParams as $name=>$value)
					$return .= $name.":".urlencode($value)."/";
			}
			return $return;
		}


		/**
		 *
		 * @param String $pValue
		 * @return String
		 */
		static public function getAlias($pValue = "")
		{
			if(!Core::$isBackoffice&&Configuration::$site_translateURL)
				return preg_replace('/(\_)/', "-", Dictionary::getAliasFor($pValue));
			else
				return preg_replace('/(\_)/', "-", $pValue);
		}


		/**
		 * @static
		 * @param String $pURL		URL récupérée via son adresse
		 * @return Boolean
		 */
		static public function checkForBackoffice(&$pURL)
		{
			$r = false;
			if(preg_match('/^'.Configuration::$site_backoffice.'\//',$pURL, $extract, PREG_OFFSET_CAPTURE))
			{
				$pURL = preg_replace('/^'.Configuration::$site_backoffice.'\//',"",$pURL);
				$r =  true;
			}
			return $r;
		}


		/**
		 * @static
		 * @param  $pURL
		 * @return bool|string
		 */
		static public function extractLanguage(&$pURL)
		{
			if(Configuration::$site_multilanguage&&!Core::$isBackoffice&&!preg_match("/^statique/",$pURL, $matches))
			{
				$language = self::shift($pURL, self::REGEXP_LANGUAGE);
				if(!$language)
					Go::toFront("","",array(), Configuration::$site_defaultLanguage);
				return $language;
			}
			return Configuration::$site_defaultLanguage;
		}


		/**
		 * @static
		 * @param  $pURL
		 * @return bool|String
		 */
		static public function extractController(&$pURL)
		{
			$controller = self::shift($pURL, self::REGEXP_CONTROLLER);
			if (!empty($controller)
				&&Configuration::$site_translateURL
				&&!Core::$isBackoffice)
			{
				$controller = Dictionary::getAliasFrom($controller);
				if(empty($controller))
					Go::to404();
			}
			return $controller;
		}


		/**
		 * @static
		 * @param  $pURL
		 * @return bool|String
		 */
		static public function extractAction(&$pURL)
		{
			$action = self::shift($pURL, self::REGEXP_ACTION);
			if (!empty($action)
				&&Configuration::$site_translateURL
				&&!Core::$isBackoffice)
			{
				$action = Dictionary::getAliasFrom($action);
				if(empty($action))
					Go::to404();
			}
			return $action;
		}


		/**
		 * Méthode permettant de dépiler une chaine de caractères de l'url passée en paramètre et respectant l'expression régulière souhaitée
		 * @static
		 * @param  $pURL
		 * @param  $pRegExp
		 * @return bool|string
		 */
		static public function shift(&$pURL, $pRegExp)
		{
			if(isset($pURL)&&preg_match($pRegExp, $pURL, $extract, PREG_OFFSET_CAPTURE))
			{
				if(!isset($extract[1][0]))
					return false;
				$r = str_replace("/", '\/', $extract[1][0]);
				$pURL = preg_replace("/^".$r.'\//', "", $pURL);
				return $extract[1][0];
			}
			return false;
		}


		/**
		 * @static
		 * @param  $pUrl
		 * @return array
		 */
		static public function extractParameters(&$pUrl)
		{
			$parameters = array();
			if(empty($pUrl))
				return $parameters;
			$p = self::shift($pUrl, utf8_encode(self::REGEXP_PARAMETERS));
			$params = explode("/",$p);
			$max = count($params);
			for($i=0;$i<$max;$i++)
			{
				$params[$i] = urldecode($params[$i]);
				$param = explode(":",$params[$i]);
				if(isset($param[0])&&isset($param[1]))
				{
					$value = $param[1];
					for($j=2,$maxJ=count($param);$j<$maxJ;$j++)
						$value.=":".$param[$j];
					$parameters[$param[0]] = $value;
				}
			}
			if(isset($parameters["q"]))
				$parameters["q"] = urldecode($parameters["q"]);
			return $parameters;
		}


		/**
		 * Méthode permettant de filtrer une chaine de caractères pour son utilisation dans une url
		 * @param String $pTexte			Chaine de caractères a filtrer
		 * @param bool $pLower
		 * @return String
		 */
		static public function sanitize($pTexte, $pLower = true)
		{
			$chars = array(
				"ç"=>"c",
				"â"=>"a",
				"à"=>"a",
				"ä"=>"a",
				"é"=>"e",
				"è"=>"e",
				"ê"=>"e",
				"ë"=>"e",
				"ë"=>"e",
				"ì"=>"i",
				"ï"=>"i",
				"ì"=>"i",
				"î"=>"i",
				"ù"=>"u",
				"ü"=>"u",
				"û"=>"u",
				"ô"=>"o",
				"ò"=>"o",
				"ö"=>"o",
				"ÿ"=>"y",
				"æ"=>"ae"
			);

			foreach($chars as $key=>$change)
			{
				$pTexte = str_replace($key, $change, $pTexte);
				$pTexte = str_replace(mb_strtoupper($key, Configuration::$site_encoding), mb_strtoupper($change, Configuration::$site_encoding), $pTexte);
			}
			if ($pLower) $pTexte = strtolower($pTexte);
			$pTexte = preg_replace("/[\s]/i", "_", $pTexte);
			$pTexte = preg_replace("/[^\_0-9a-z]/i", "_", $pTexte);
			$pTexte = preg_replace(array("/^_+/", "/_+$/", "/_+/"), array("", "", "_"), $pTexte);
			return $pTexte;
		}
	}
}