PHP Framework
=============

Yet another PHP Framework

Dependencies
------------

None - all needed libs are included.

Features
--------

* MVC Architecture
* Support multiple applications on a single installation
* Each application support multiple modules
* Externalized configuration 
* Multi language support (url ready if needed)
* Support multiple database handlers with lazy loading
* MySQL query builder
* Routing handler with specific declaration file
* PHP Debugger
* Centralized component system for front-end
* Autoload class
* Forms handling (declaration, validation, display)

Installation locale
---
Pré-requis :
* Apache
* PHP 7.2
* MySQL

### Windows
Tout-en-un :
* [WAMP Server](https://www.wampserver.com/en/)
* [EasyPHP](https://www.easyphp.org/)
* ...

WSL2 :
* [Installation](https://learn.microsoft.com/fr-fr/windows/wsl/install)
* Mettre à jour la liste des paquets puis installer les pré-requis


### Configuration Apache
* S'assurer qu'Apache permet aux dossiers de travail de surcharger la configuration (pour accepter les fichiers [.htaccess](https://httpd.apache.org/docs/2.2/fr/howto/htaccess.html))
* Activer le module Apache `rewrite`

### Configuration du projet
* Ouvrir le fichier `includes/applications/setup.json` et identifier le préfixe associer à `localhost` (par défaut `dev`)
* Ouvrir le fichier `includes/applications/dev.config.json` correspondant à la configuration prise en compte

Composants
---
Un composant est un ensemble de resources JS, CSS, medias regroupé pour permettre le bonne affichage / la bonne exécution d'une page / fonctionnalité.

Dans le projet, depuis un controller PHP il est possible de charger un composant via la méthode :
```php
Autoload::addComponent($pComponentName);
``` 

Cette méthode va s'occuper de centraliser l'ensemble des composants à charger afin de n'ajouter qu'une seule balise `script` et qu'une seule balise `link[rel="stylesheet"]`. Ces deux balises vont pointer leurs urls vers le `StaticController` du framework avec la liste des composants à charger en paramètres.

C'est ensuite la classe `Dependencies` qui va se charger de récupérer la liste des composants à charger pour en déduire les dépendances ainsi que l'ordre des fichiers (`js` ou `css`) à renvoyer.

**Note :** Les urls dans les fichiers `css` sont relatives aux fichiers et doivent être entourées de guillemets `"`


Forms
----------
Form are declared in JSON file. Each file correspond to a single form.

lets say, we've got a login form in : includes/applications/main/modules/front/form/form.login.json
```json
	{
    	"login":{
    		"require":true,
    		"regExp":"TextNoHtml",
    		"tag":"input",
    		"attributes":{
                "placeholder":"Login",
    			"type":"text"
    		}
    	},
    	"mdp":{
    		"require":true,
    		"regExp":"TextNoHtml",
    		"tag":"input",
    		"attributes":{
                "placeholder":"Mot de passe",
    			"type":"password"
    		}
    	},
    	"submit":{
    		"tag":"input",
    		"attributes":{
    			"type":"submit",
    			"value":"Login",
    			"class":"button"
    		}
    	}
    }
```

We can access it in a controller (of 'main' application in the 'front' module) :
```php
$form_login = new Form('login');
if($form_login->isValid())
{
	$values = $form_login->getValues();
}
else
{
	$error = $form_login->getError();
}
$this->addForm('login', $form_login');
```

The 'addForm' method registers the Form object to Smarty and the current template and allows us to access it :
```html
<html>
	<body>
		{form.login->display url='action/route' param1='value1'}
	</body>
</html>
```

#### Form's inputs available
INPUT[text|password|submit|...]
```json
{
    "label":"Input",
    "tag":"input",
    "require":true|false,
    "attributes":
    {
        "type":"text"|"password"|"submit"...,
        "value":"Default Value",
        "class":...
    }
}
```

CAPTCHA
```json
{
    "label":"Captcha",
    "tag":"captcha",
    "require":true|false,
    "attributes":{
        "backgroundColor":"#ffffff",
        "fontSizeMax":13,
        "fontSizeMin":13,
        "width":100,
        "height":30,
        "rotation":15,
        "fontColors":["#444444","#ff0000","#000000"],
        "transparent":true,
        "length":7,
        "type":"random|calculus",
        "valueMax":99
    }
}
```

DATEPICKER
```json
{
    "label":"Datepicker",
    "tag":"datepicker"
}
```
UPLOAD  
```json
{
    "label":"Fichier",
    "tag":"upload"
    "fileType":"jpg|png|...",
    "fileName":"someName{id}",
    "resize":[200, 200]
}
```
RADIOGROUP
```json
{
    "label":"Radiogroup",
    "tag":"radiogroup",
    "display":"block",
    "height":"200px",
    "width":"400px",
    "fromModel":
    {
        "model":"ModelName",
        "method":"all",
        "name":"field_name",
        "value":"field_name_id"
    }
}
```
CHECKBOXGROUP
```json
{
    "label":"Checkboxgroup",
    "tag":"checkboxgroup",
    "height":"200px",
    "width":"400px",
    "fromModel":
    {
        "model":"ModelName",
        "method":"all",
        "name":"field_name",
        "value":"field_name_id"
    }
}
```  

Debugger
---------

```php
/**
 * @parameter string $pString			Data to log into debugger
 * @parameter bool	 $pDisplay			Specify if the debugger should automatically be opened
 **/
trace($pString, $pDisplay);

/**
 * @parameter object $pString			Data to log into debugger
 * @parameter bool	 $pDisplay			Specify if the debugger should automatically be opened
 **/
trace_r($pObject, $pDisplay);
```

PHP Namespaces
-----------
namespace | contexte | description
--------------|------------|--------------
core \\ *{subPackage}* \\ |Global |Classes & interfaces du package core
lib \\ *{package}* \\ *{subPackage}* |Global |Classes & interfaces des packages secondaire
app \\ *{appName}* \\ models |Application |Modèles de l'application *appName*
app \\ *{appName}* \\ controllers \\ *{moduleName}* |Application|Controllers du module *{moduleName}* de l'application *{appName}*
app \\ *{appName}* \\ src \\ *{subPackage}* |Application |Classes & interfaces de l'application *{appName}*



Todo (nice to have)
---------
* [ ] Integrate a light Dictionary class with the Dependencies's loaded scripts
* [ ] RoutingHandler : method to get a route depending upon controller/action/method/parameters
* [ ] Integrate services managing
* [ ] Develop an Autocomplete component
* [ ] Dependencies : Add minified option
* [ ] SimpleCrawler : use Events for logging
* [ ] Dictionary : Implement dynamic "title" and "description" tags (like terms)
