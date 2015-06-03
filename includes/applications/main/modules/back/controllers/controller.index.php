<?php
namespace app\main\controllers\back
{
    use core\application\Configuration;
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
            $authHandler = Configuration::$application_authentificationHandler;
            if(!call_user_func_array(array($authHandler, 'is'), array($authHandler::ADMIN)))
				Go::toBack("index", "login");
			$menu = new Menu(Core::$path_to_application.'/modules/back/menu.json');
			$menu->redirectToDefaultItem(true);
		}

		public function login()
		{
            $authHandler = Configuration::$application_authentificationHandler;
			if(call_user_func_array(array($authHandler, 'is'), array($authHandler::ADMIN)))
				Go::toBack();
			$this->setTitle("Espace d'adminitration | Connexion");
			$form = new Form("login");
			if($form->isValid())
			{
				$data = $form->getValues();
                $authHandlerInst = call_user_func_array(array($authHandler, 'getInstance'), array());
				if($authHandlerInst->setAdminSession($data["login"], $data["mdp"]))
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
            $authHandler = Configuration::$application_authentificationHandler;
            call_user_func_array(array($authHandler, 'unsetUserSession'), array());
			Go::toBack();
		}
	}
}