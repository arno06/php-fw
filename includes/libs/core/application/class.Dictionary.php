<?php
namespace core\application
{
	use core\utils\Stack;

	/**
	 * Class Dictionary - Permet la gestion d'un fichier de langue global à l'application
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.0
	 * @package application
	 */
	class Dictionary extends Singleton
	{
		/**
		 * Tableau des alias
		 * @var array
		 */
		private array $table_alias;

		/**
		 * Tableau des termes
		 * @var array
		 */
		private array $table_terms;

		/**
		 * Tableau des infos de SEO (Search Engine Optimisation)
		 * @var array
		 */
		private array $table_seo;

		/**
		 * Variable de la langue en cours
		 * @var string
		 */
		static public string $langue;

		/**
		 * Undefined data
		 */
		const UNDEFINED = "Undefined";


		public function __construct(PrivateClass $pInstance){}

		/**
		 * Méthode de récupération d'un terme se trouvant dans le fichier de langue
		 * Le paramètre attendu correspond à la concaténation des différents identifiants de niveau d'accès
		 * @param string $pId
		 * @return string|array
		 */
		static public function term(string $pId):string|array
		{
			$i = self::getInstance();
			$value = Stack::get($pId, $i->table_terms);
            if(!$value){
                return self::UNDEFINED;
            }
            if(!is_string($value)){
                return $value;
            }
            $re = "/\{([a-z.]+)}/";
			while(preg_match($re, $value, $matches))
			{
				$value = str_replace($matches[0], self::term($matches[1]), $value);
			}
			if(empty($value))
				$value = self::UNDEFINED;
			return $value;
		}

		/**
		 * Méthode de récupération de l'ensemble des termes disponibles via le fichier de langue
		 * @return array
		 */
		static public function terms():array
		{
            /** @var Dictionary $i */
			$i = self::getInstance();
			return $i->table_terms;
		}

		/**
		 * Méthode de récupération des informations de SEO pour un controller et un action donné
		 * @param String $pController		Nom du controller
		 * @param String $pAction			Nom de l'action
		 * @return array|null
		 */
		static public function seoInfos(string $pController, string $pAction):array|null
		{
			$i = self::getInstance();
			if(!isset($i->table_seo[$pController])||!isset($i->table_seo[$pController][$pAction]))
				return null;
			return $i->table_seo[$pController][$pAction];
		}

		/**
		 * Méthode de récupération du vrai nom d'un controller ou d'une action à partir de son alias dans la langue en cours
		 * Renvoi la valeur du controller tel qu'il existe
		 * @param string $pValue		Valeur de l'alias
		 * @return string
		 */
		static public function getAliasFrom(string $pValue):string
		{
			$i = self::getInstance();
			if(isset($i->table_alias[$pValue]))
				return $i->table_alias[$pValue];
			return $pValue;
		}

		/**
		 * Méthode de récupération de l'alias dans la langue actuelle pour un controller ou une action
		 * @param string $pValue		Valeur dont on souhaite récupérer l'alias
		 * @return string
		 */
		static public function getAliasFor(string $pValue):string
		{
			$i = self::getInstance();
			if($return = array_search($pValue, $i->table_alias))
				return $return;
			return $pValue;
		}

		/**
		 * Méthode de définition de l'objet Dictionary en fonction des paramètres
		 * @param string $pLanguage		Langue en cours - fr/en/de
		 * @param array $pTerms			Tableau des termes accessibles de manière global à l'application
		 * @param array $pSeo			Tableau des informations relatives à la SEO (balise "title" et "description")
		 * @param array $pAlias			Tableau des alias pour la gestion de la réécriture d'url dynamique
		 * @return void
		 */
		static public function defineLanguage(string $pLanguage, array $pTerms, array $pSeo, array $pAlias):void
		{
			if(empty($pLanguage))
				trigger_error("Impossible de définir le <b>Dictionary</b>, <b>langue</b> non renseignée.", E_USER_ERROR);
			self::$langue = $pLanguage;
			$i = self::getInstance();
			$i->table_alias = $pAlias;
			$i->table_terms =$pTerms;
			$i->table_seo = $pSeo;
		}
	}
}
