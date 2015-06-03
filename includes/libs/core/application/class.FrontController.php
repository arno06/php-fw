<?php
namespace core\application
{
	use core\application\authentification\AuthentificationHandler;
	use core\tools\form\Form;
	use Smarty;

	/**
	 * Controller de frontoffice de base
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.0
	 * @package application
	 * @subpackage controller
	 */
	class FrontController extends event\EventDispatcher
	{
		/**
		 * Tableau associatif des données qu'on souhaite envoyer &agrave; la vue.
		 * @var array
		 */
		private $content = array();


		/**
		 * Tableau associatif des données contenues entre les Balises Head de la vue.
		 * @var array
		 */
		private $head = array("title"=>"", "description"=>"");


		/**
		 * Tableau associatif des formulaires qu'on souhaite envoyer &agrave; la vue.
		 * @var array
		 */
		private $forms = array();


		/**
		 * Nom de la vue (template) qu'on souhaite afficher
		 * @var String
		 */
		private $template = "";


		/**
		 * @static
		 * @param  $pControllerName
		 * @param  $pActionName
		 * @param  $pUrl
		 * @return bool
		 */
		static public function isFromDB($pControllerName, $pActionName, $pUrl)
		{
			/** MUST BE OVERRIDEN */
			return false;
		}


		/**
		 * Méthode static d'initialisation des données avant l'envoi &agrave; la vue dans le cas de contenu dynamique
		 * @return void
		 */
		public function prepareFromDB()
		{
			/** MUST BE OVERRIDEN */
			Go::to404();
		}


		/**
		 * Méthode public de rendu de la page en cours
		 * @param Smarty $smarty
		 * @param bool $pDisplay
		 * @return string
		 */
		public function renderHTML($smarty = null, $pDisplay = true)
		{
			if($smarty==null)
				$smarty = new Smarty();
			Core::setupSmarty($smarty);
			if(!$smarty->template_exists($this->template))
			{
				if(Core::debug())
					trigger_error("Le template <b>".$this->template."</b> est introuvable", E_USER_ERROR);
				else
					Go::to404();
			}
			$conf = get_class_vars('core\application\Configuration');
			$terms =Dictionary::terms();
			$globalVars = $this->getGlobalVars();
			$smarty->assign_by_ref("configuration", $conf);
			$smarty->assign_by_ref("request_async", Core::$request_async);
			$smarty->assign_by_ref("dictionary", $terms);
			foreach($this->forms as $n=>&$form)
			{
				$smarty->register_object("form_".$n, $form, array("display", "name", "getValue", "getLabel", "getOptions", "isChecked"));
			}
			foreach($globalVars as $n=>&$v)
			{
				$smarty->assign_by_ref($n, $v);
			}
			if(!Core::debug())
				$smarty->load_filter('output', 'gzip');
			return $smarty->fetch($this->template, null, null, $pDisplay);
		}

		/**
		 * @return array
		 */
		public function getGlobalVars()
		{
			$is = array();
            $authHandler = Configuration::$application_authentificationHandler;
            foreach($authHandler::$permissions as $name=>$value)
				$is[$name] = $authHandler::is($name);
			return array(
				"path_to_theme"=>Core::$path_to_theme,
				"path_to_components"=>Core::$path_to_components,
				"scripts"=>Autoload::scripts(),
				"styles"=>Autoload::styles(),
				"head"=>$this->head,
				"forms"=>$this->forms,
				"content"=>$this->content,
				"user_is"=>$is,
				"controller"=>preg_replace("/\_/", "-", Core::$controller),
				"action"=>preg_replace("/\_/", "-", Core::$action)
			);
		}


		/**
		 * Méthode d'ajout de script &agrave; la vue.
		 * @param String $pScript				Nom du fichier JS
		 * @return void
		 */
		protected function addScript($pScript)
		{
			Autoload::addScript($pScript);
		}


		/**
		 * Méthode d'ajout de feuille de style &agrave; la vue
		 * @param String $pStyle				Nom du fichier CSS
		 * @return void
		 */
		protected function addStyle($pStyle)
		{
			Autoload::addStyle($pStyle);
		}


		/**
		 * Méthode d'ajout d'une variable de contenu envoyé &agrave; la vue
		 * @param String $pSmartyVar				Nom d'acc&egrave;s &agrave; la variable
		 * @param mixed $pContent					Valeur de la variable acc&egrave;s tout type (String, Object, array, int...)
		 * @return void
		 */
		protected function addContent($pSmartyVar, $pContent)
		{
			$this->content[$pSmartyVar]=$pContent;
		}

		/**
		 * Méthode de récupération du contenu d'une variable
		 * @param String $pSmartyVar
		 * @return mixed
		 */
		protected function getContent($pSmartyVar)
		{
			if(!isset($this->content[$pSmartyVar]))
				return "";
			return $this->content[$pSmartyVar];
		}


		/**
		 * Méthode d'ajout d'un formulaire envoyé &agrave; la vue
		 * @param String $pName				Nom d'acc&egrave;s au formulaire
		 * @param Form $pForm
		 * @return void
		 */
		protected function addForm($pName, Form &$pForm)
		{
			$pForm->prepareToView();
			$this->forms[$pName] = $pForm;
		}


		/**
		 * Méthode de définition de la valeur pour la balise Title contenue entre les balises Head de la vue
		 * @param String $pTitle				SEO : 5 mots de longueur moyenne pour 70 caract&egrave;res espace compris
		 * @return void
		 */
		public function setTitle($pTitle)
		{
			$this->head['title'] = $pTitle;
		}


		/**
		 * Méthode définition de la valeur pour la balise Meta - description - contenue entre les balises Head de la vue
		 * @param String $pDescription				SEO : 150 caract&egrave;res espace compris
		 * @return void
		 */
		public function setDescription($pDescription)
		{
			$this->head['description'] = $pDescription;
		}


		/**
		 * @param $pFolder
		 * @param $pName
		 * @param string $pFile
		 * @return void
		 */
		public function setTemplate($pFolder, $pName, $pFile = "")
		{
			if(!empty($pFile))
				$this->template = $pFile;
			else
				$this->template = $pFolder."/template.".$pName.".tpl";
		}

		/**
		 * Destructor
		 * @return void
		 */
		public function __destruct()
		{
			unset($this->forms);
			unset($this->content);
			unset($this->head);
			$this->removeAllEventListeners();
		}
	}
}