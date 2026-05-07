<?php
namespace core\data
{
	use core\application\Configuration;

	/**
	 * Class regroupant des méthodes statiques "utilitaires" pour l'encodage
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.0
	 * @package core\data
	 */
	abstract class Encoding
	{
		/**
		 * Méthode static d'encodage récursif de valeurs dans leurs valeur numériques (é ==> &#233;)
		 * @static
		 * @param mixed $pValue
		 * @return mixed
		 */
		static public function toNumericEntities(mixed $pValue):mixed
		{
            if(!$pValue){
                return $pValue;
            }
			$convmap = array(0x80, 0xff, 0, 0xff);
			if (is_object($pValue))
				return $pValue;
			if(!is_array($pValue))
				return mb_encode_numericentity($pValue, $convmap, Configuration::$global_encoding);
			foreach($pValue as &$value)
				$value = self::toNumericEntities($value);
			return $pValue;
		}


		/**
		 * Méthode static de décodage récursif des entités numériques
		 * @static
		 * @param  mixed $pValue
		 * @return mixed
		 */
		static public function fromNumericEntities(mixed $pValue):mixed
		{
            if(!$pValue){
                return $pValue;
            }
			$convmap = array(0x80, 0xff, 0, 0xff);
			if(!is_array($pValue))
			{
				$specialChars = array("&#8221;"=>'"',
					"&#8220;"=>'"',
					"&#8222;"=>'"',
					"&#8211;"=>'-',
					"&#8212;"=>'_',
					"&#8216"=>"'",
					"&#8217"=>"'",
					"&#8218"=>"'");
				foreach($specialChars as $k=>$v)
					$pValue = preg_replace("/".$k."/",$v,$pValue);
				return mb_decode_numericentity($pValue, $convmap, Configuration::$global_encoding);
			}
			foreach($pValue as &$value)
				$value = self::fromNumericEntities($value);
			return $pValue;
		}

		/**
		 * Méthode static d'encodage récursif de valeurs dans leurs valeur HTML (é ==> &eacute;)
		 * @param mixed $pValue
		 * @param int $pQuote
		 * @param bool $pCharset
		 * @return mixed
		 */
		static public function toHTMLEntities(mixed $pValue, int $pQuote = ENT_QUOTES, bool $pCharset = false):mixed
		{
            if(!$pValue){
                return $pValue;
            }
			if(!$pCharset)
				$pCharset = Configuration::$global_encoding;
			if(!is_array($pValue))
				return htmlentities($pValue, $pQuote, $pCharset);
			foreach($pValue as &$value)
				$value = self::toHTMLEntities($value, $pQuote, $pCharset);
			return $pValue;
		}

		/**
		 * Méthode static de décodage récursif d'entité HTML dans leur version ISO-8859-1
		 * @param mixed $pValue
		 * @param int $pQuote
		 * @param bool $pCharset
		 * @return mixed
		 */
		static public function fromHTMLEntities(mixed $pValue, int $pQuote = ENT_QUOTES, bool $pCharset = false):mixed
		{
            if(!$pValue){
                return $pValue;
            }
			if(!$pCharset)
				$pCharset = Configuration::$global_encoding;
			if(!is_array($pValue))
				return html_entity_decode($pValue, $pQuote, $pCharset);
			foreach($pValue as &$value)
				$value = self::fromNumericEntities($value);
			return $pValue;
		}


		static public function fromUTF8(mixed $pValue):mixed
		{
            if(!$pValue){
                return $pValue;
            }
			if(is_string($pValue))
                return mb_convert_encoding($pValue, 'ISO-8859-1');
			if (is_object($pValue))
			{
				$values = get_object_vars($pValue);
				foreach($values as $key=>$value)
					$pValue->$key = self::fromUTF8($value);
			}
			elseif (is_array($pValue))
			{
				foreach($pValue as &$value)
					$value = self::fromUTF8($value);
			}
			return $pValue;
		}


		static public function toUTF8(mixed $pValue):mixed
		{
            if(!$pValue){
                return $pValue;
            }
			if(is_string($pValue))
				return mb_convert_encoding($pValue, 'UTF-8');
			if (is_object($pValue))
			{
				$values = get_object_vars($pValue);
				foreach($values as $key=>$value)
					$pValue->$key = self::toUTF8($value);
			}
			elseif (is_array($pValue))
			{
				foreach($pValue as &$value)
					$value = self::toUTF8($value);
			}
			return $pValue;
		}

		/**
		 * Méthode static de récupération du BOM UTF-8
		 * @static
		 * @return string
		 */
		static public function BOM():string
		{
			return chr(239) . chr(187) . chr(191);
		}
	}
}
