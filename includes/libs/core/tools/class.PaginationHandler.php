<?php
namespace core\tools
{
	use core\db\Query;
	use core\db\QueryCondition;

	/**
	 * Class PaginationHandler
	 * Gestionnaire de pagination coté controller (pas de gestion de la mise en page)
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .3
	 * @package core\tools
	 */
	class PaginationHandler
	{
		/**
		 * Nombre d'entrée &agrave; afficher par page
		 * @var int
		 */
		private $itemsByPage;

		/**
		 * Nombre de pages max
		 * @var int
		 */
		private $nbPages;

		/**
		 * Numero de la page en cours
		 * @var int
		 */
		private $currentPage;

		/**
		 * Nombre d'entrées maximum
		 * @var int
		 */
		private $nbItems;

		/**
		 * @var int
		 */
		public $first;

		/**
		 * @var int
		 */
		public $number;

		/**
		 * Constructor
		 * @param int $pCurrentPage		Page en cours
		 * @param int $pNbItemByPage	Nombre d'item par page
		 * @param int $pNbItemsMax		Nombre total d'item dans la base
		 */
		public function __construct($pCurrentPage, $pNbItemByPage, $pNbItemsMax)
		{
			$this->currentPage = $pCurrentPage>0?$pCurrentPage:1;
			$this->itemsByPage = $pNbItemByPage;
			$this->nbItems = $pNbItemsMax;
			$this->nbPages = ceil($this->nbItems/$this->itemsByPage);
			$this->first = (($this->currentPage-1)*$this->itemsByPage);
			$this->number = $this->itemsByPage;
		}

		/**
		 * Méthode de récupération des infos nécessaires &agrave; la mise en place de la pagination dans la vue
		 * @return Array
		 */
		public function getPaginationInfo()
		{
			return array("nbPages"=>$this->nbPages, "currentPage"=>$this->currentPage);
		}

		/**
		 * @return QueryCondition
		 */
		public function getConditionLimit()
		{
			return Query::condition()->limit($this->first, $this->number);
		}
	}
}