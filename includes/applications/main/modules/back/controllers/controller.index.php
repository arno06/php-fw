<?php
namespace app\main\controllers\back
{
	use core\application\FrontController;
	use core\application\InterfaceController;
	use core\application\authentification\AuthentificationHandler;
	use core\application\Go;
	use core\application\Core;
	use core\tools\Menu;
	use core\tools\form\Form;
	use core\utils\Logs;

	class index extends FrontController implements InterfaceController
	{
		public function __construct()
		{

		}

		public function index()
		{
			if(!AuthentificationHandler::is(AuthentificationHandler::ADMIN))
				Go::toBack("index", "login");
			$menu = new Menu(Core::$path_to_application.'/modules/back/menu.json');
			$menu->redirectToDefaultItem(true);
		}

		public function login()
		{

			if(AuthentificationHandler::is(AuthentificationHandler::ADMIN))
				Go::toBack();
			$this->setTitle("Espace d'adminitration | Connexion");
			$form = new Form("login");
			if($form->isValid())
			{
				$data = $form->getValues();
				if(AuthentificationHandler::getInstance()->setAdminSession($data["login"], $data["mdp"]))
				{
					Go::toBack();
				}
				else
				{
					Logs::write("Tentative de connexion au backoffice <".$data["login"].":".$data["mdp"].">", Logs::WARNING);
					$this->addContent("error", "Le login ou le mot de passe est incorrect");
				}
			}
			else
				$this->addContent("error", $form->getError());
			$this->addForm("login", $form);
		}

		public function logout()
		{
			AuthentificationHandler::unsetUserSession();
			Go::toBack();
		}
	}
}