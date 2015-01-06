<?php
namespace core\tools
{
	use core\application\Core;
	use core\application\Configuration;
	use Smarty;
	use PHPMailer;
	use phpmailerException;

	/**
	 * Class PsarterMailer
	 * 		- Permet de gérer des emails avec PHPMailer pour la gestion de l'email et Smarty pour la gestion des templates
	 * 		- Int&egrave;gre automatiquement les images HTML dans l'email
	 *
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.0
	 * @package core\tools
	 */
	class PsartekMailer
	{

		/**
		 * Instance Smarty
		 * @var Smarty
		 */
		private $smarty;


		/**
		 * Instance PhpMailer
		 * @var PHPMailer
		 */
		private $phpmailer;


		/**
		 * Template du mail
		 * @var String
		 */
		private $template = "";

		/**
		 * Constructor
		 */
		public function __construct()
		{
			$this->smarty = new Smarty();
			$this->smarty->assign("path_to_theme", Core::$path_to_theme);
			$this->smarty->assign("server_url", Configuration::$server_url);
			$this->smarty->assign("site_backoffice", Configuration::$site_backoffice);
			Core::setupSmarty($this->smarty);
			$this->phpmailer = new PHPMailer();
		}

		public function setCharset($charset)
		{
			$this->phpmailer->CharSet = $charset;
		}

		/**
		 * @param string $pFolder
		 * @param string $pName
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
		 * Méthode de définition du champs "from"
		 * @param $pName		String		Nom de l'expéditeur
		 * @param $pMail		String		Email de l'expéditeur
		 * @return void
		 */
		public function setFrom($pName, $pMail)
		{
			$this->phpmailer->From = $pMail;
			$this->phpmailer->FromName = $pName;
		}


		/**
		 * Méthode de définition de l'objet de l'email
		 * @param $pSubject		String		Objet de l'email
		 * @return void
		 */
		public function setSubject($pSubject)
		{
			$this->phpmailer->Subject = $pSubject;
		}


		/**
		 * Méthode d'ajout d'un contenu au template
		 * @param $pName			String		Nom de la variable Smarty (exploitable via {$pName} dans le .tpl)
		 * @param $pContent			*			Contenu de la variable
		 * @return void
		 */
		public function addContent($pName, $pContent)
		{
			$this->smarty->assign($pName, $pContent);
		}


		/**
		 * Méthode d'ajout d'un destinataire au mail
		 * @param $pName			String		Nom du destinataire
		 * @param $pMail			String		Email du destinataire
		 * @return void
		 */
		public function addAdress($pName, $pMail)
		{
			$this->phpmailer->AddAddress($pMail, $pName);
		}

		/**
		 * @param $pName
		 * @param $pMail
		 */
		public function addCC($pName, $pMail)
		{
			$this->phpmailer->AddCC($pMail, $pName);
		}


		/**
		 * Méthode d'envoi de l'email avec PHPMailer
		 * @return Boolean
		 */
		public function send()
		{
			if(!empty(Configuration::$server_smtp))
			{
				$this->phpmailer->IsSMTP();
				$this->phpmailer->Host = Configuration::$server_smtp;
			}
			$body = $this->smarty->fetch($this->template);
			$this->phpmailer->MsgHTML($body, Configuration::$server_url);
			$traitement = false;
			try
			{
				$traitement = $this->phpmailer->Send();
			}
			catch(phpmailerException $e){}
			return $traitement;
		}


		/**
		 * Méthode d'affichage de l'email apr&egrave;s évaluation de son contenu par Smarty
		 * Stop l'exécution du code PHP
		 * @return void
		 */
		public function display()
		{
			$this->smarty->display($this->template);
			exit();
		}

		/**
		 * @return void
		 */
		public function clearRecipients()
		{
			$this->phpmailer->ClearAllRecipients();
		}

		public function addAttachment($pPath, $pName='')
		{
			$this->phpmailer->AddAttachment($pPath, $pName);
		}
	}
}