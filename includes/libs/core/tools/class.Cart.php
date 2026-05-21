<?php
namespace core\tools
{
	use core\application\Singleton;
	use core\application\PrivateClass;

	/**
	 * Class Cart Permet de gérer un panier - cas d'une boutique en ligne
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .3
	 * @package core\tools
	 */
	class Cart extends Singleton
	{
		/**
		 * Nom de la variable de session gérant les items du panier
		 * @var String
		 */
		const SESSION_VAR_NAME = "fw_cart";


		/**
		 * Constructor
		 * @param PrivateClass $pInstance
		 */
		public function __construct(PrivateClass $pInstance)
		{
			if(!$_SESSION[self::SESSION_VAR_NAME]||!is_array($_SESSION[self::SESSION_VAR_NAME]))
				$_SESSION[self::SESSION_VAR_NAME] = array();
		}

		/**
		 * Méthode d'ajout d'un item au panier
		 * @param mixed $pId							Identifiant unique
		 * @param int $pPrice						Prix unitaire
		 * @param int $pQuantity						Quantité d'item de ce type
         * @param mixed $pProperty
		 * @return void
		 */
		public function add(int|string $pId, int $pPrice, int $pQuantity=1, mixed $pProperty=null):void
		{
			if(!$pId || !$pPrice || $pQuantity<=0)
				return;
			if($_SESSION[self::SESSION_VAR_NAME][$pId])
			{
				$_SESSION[self::SESSION_VAR_NAME][$pId]["quantity"] += $pQuantity;
				$_SESSION[self::SESSION_VAR_NAME][$pId]["total"] = $pPrice * $_SESSION[self::SESSION_VAR_NAME][$pId]["quantity"];
			}
			else
				$_SESSION[self::SESSION_VAR_NAME][$pId] = array("quantity"=>$pQuantity, "price"=>$pPrice, "property"=>$pProperty, "total"=>($pPrice*$pQuantity));
		}

		/**
		 * Méthode de mise-é-jour de la quantité souhaitée pour un item donnée
		 * @param int|string $pId			Identifiant unique
		 * @param int $pQuantity		Nouvelle quantité souhaité
		 * @return void
		 */
		public function updateQuantity(int|string $pId, int $pQuantity):void
		{
			if(!$_SESSION[self::SESSION_VAR_NAME][$pId])
				return;
			$_SESSION[self::SESSION_VAR_NAME][$pId]["quantity"] = $pQuantity;
			$_SESSION[self::SESSION_VAR_NAME][$pId]["total"] = $pQuantity * $_SESSION[self::SESSION_VAR_NAME][$pId]['price'];
		}

		/**
		 * Méthode de suppression d'un item du panier
		 * @param string|int $pId					Identifiant unique
		 * @return void
		 */
		public function remove(string|int $pId):void
		{
			unset($_SESSION[self::SESSION_VAR_NAME][$pId]);
		}

		/**
		 * Méthode de ré-initialisation du Panier
		 * @return void
		 */
		public function trash():void
		{
			unset($_SESSION[self::SESSION_VAR_NAME]);
			$_SESSION[self::SESSION_VAR_NAME] = array();
		}

		/**
		 * Méthode de récupération des informations du panier
		 * Renvoie un tableau associatif : array("estimation"=>x, "countItems"=>y);
		 * @return array
		 */
		public function getResume():array
		{
			return array("estimation"=>$this->getEstimation(),
				"countItems"=>$this->getCountItems());
		}

		/**
		 * Méthode de récupération du tableau des items se trouvant dans le panier
		 * @return array
		 */
		public function getItems():array
		{
			return $_SESSION[self::SESSION_VAR_NAME];
		}

		/**
		 * Méthode d'estimation de la valeur du panier
		 * @return float
		 */
		private function getEstimation():float
		{
			$estimation = 0;
			$max = count($_SESSION[self::SESSION_VAR_NAME]);
			if(!$max)
				return $estimation;
			foreach($_SESSION[self::SESSION_VAR_NAME] as $datas)
				$estimation += $datas["quantity"] * $datas["price"];
			return $estimation;
		}

		/**
		 * Méthode de récupération du nombre d'items ajoutés au panier
		 * @return int
		 */
		private function getCountItems():int
		{
			$count = 0;
			$max = count($_SESSION[self::SESSION_VAR_NAME]);
			if(!$max)
				return $count;
			foreach($_SESSION[self::SESSION_VAR_NAME] as $datas)
				$count += $datas["quantity"];
			return $count;
		}

        /**
         * Méthode de récupération de quantité pour un produit particulier
         * @param string|int $pId
         * @return int
         */
		static public function getQuantityById(string|int $pId):int
		{
			return $_SESSION[self::SESSION_VAR_NAME][$pId]['quantity'];
		}
	}
}
