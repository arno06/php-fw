# PHP Framework

Yet another PHP Framework

## Sommaire
 * [Fonctionnalités](#fonctionnalités)
 * [Installation locale](#installation-locale)
 * [Routing](#routing)
 * [Formulaires](#formulaires)
 * [Composants](#composants)
 * [Debugger](#debugger)
 * [Namespaces](#namespaces)
 * [Todo](#todo-nice-to-have)

## Fonctionnalités

* Architecture MVC
* Plusieurs niveaux d'abstractions
  * 1 installation du framework permet le support de **n** _applications_
  * 1 _application_ supporte **x** _modules_
* Configuration externalisée en fonction du domaine d'exécution
* Support du multi-langue
* Support de connexions multiples à différents serveurs de base de données
* Classe de création de requêtes SQL
* Gestionnaire de routes par un fichier externalisée
* Debugger PHP
* Système de gestion de [composants](#composants) JS/CSS (avec dépendances)
* Système de [formulaires](#formulaires) (déclaration, validation, affichage par défaut)

## Installation locale

Pré-requis :
* Apache
* PHP 8.*
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


### Cloner le repository
Dans le dossier racine du serveur Apache

### Configuration du projet
* Ouvrir le fichier `includes/applications/setup.json` et identifier le préfixe associer à `localhost` (par défaut `dev`)
* Ouvrir le fichier `includes/applications/dev.config.json` correspondant à la configuration prise en compte
    * Vérifier les informations renseignées des environnements associés (API et Base de données)
* Si la base est locale, s'assurer que le [schéma](https://github.com/arno06/php-fw/wiki/SQL-Dump) de base existe et est accessible

### Lancement du projet
Accéder directement à [http://localhost/php-fw/](http://localhost/php-fw/)


### Configuration PhpStorm
Un ensemble de [metadata avancées pour PhpStorm](https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html) sont disponibles au sein du dossier `.phpstorm.meta.php` pour préciser l'autocomplétion ou le comportement de certaines méthodes.

En complément, si `node` est disponible sur la machine de développement, il est possible des _watchers_ afin d'enrichir ce fichier de `meta` en fonction de la configuration du projet en cours :

 * `.phpstorm.meta.php/watcher.manifest.js` : qui devra écouter les modifications du fichier `includes/components/manifest.json` afin de permettre l'autocomplétion des composants disponibles lors de l'appel à la méthode : `core\application\Autoload::addComponent("");`
 * `.phpstorm.meta.php/watcher.config.js` : qui devra écouter les modifications des fichiers `includes/applications/*.config.json` afin de permettre l'autocomplétion sur les clés disponibles lors de l'appel à la méthode : `core\application\Configuration::extra("")`

## Routing
Pour identifier le controller et l'action exécutée, on part de l'URL.
* On regarde la première composante de l'url (chaîne de caractères entre les `/` après le domaine), si cette composante correspond à une application existante, c'est cette application qu'il faut suivre, sinon il faut suivre l'application `main` (par défaut)
* On regarde ensuite la seconde composante de l'url, si cette composante correspond à un module, c'est ce module qu'il faut suivre, sinon il faut suivre le module `front` (par défaut)
* Une fois qu'on a identifié l'`application` et le `module`, il faut regarder le fichier `src/routing_rules.json` de l'application
* En fonction de l'url et de la méthode HTTP, on identifie directement le controller, l'action ainsi que le nom des paramètres GET parsés

Exemple d'url :
```
 "article/{$permalink}-{$id}.html": {
   "parameters": {
     "permalink": "[a-z0-9\\-\\_]+",
     "id": "[0-9]+"
   },
   "GET": {
     "controller": "Article",
     "action": "byId"
   }
 }
```

Les composantes dynamiques présentes dans l'url sont identifiées par des accolades et des noms de variables (IE `{$permalink}`). Les valeurs potentiels de ces composantes sont définies dans la propriété `parameters` sous forme d'expression régulière.

Si l'url en cours correspond à la route, le couple `controller` / `action` exécuté est déduit du verbe HTTP utilisé :
* `GET`
* `POST`
* `DELETE`
* `...`
* `*` (wildcard pour tout prendre en compte)

Il est également possible de spécifier plusieurs verbes pour un même couple en concaténant les valeurs avec un pipe : `GET|POST`

## Formulaires

Les fichiers de formulaires sont déclarés dans des fichiers JSON présents dans le dossier `forms` du module de l'application en cours.

Par exemple, imaginons un formulaire de "login" includes/applications/main/modules/front/form/form.login.json
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

On peut alors l'instancier dans un controller du même module et de la même application :

```php
$form_login = new Form('login');
if($form_login->isValid())
{
	$values = $form_login->getValues();
	trace_r($values);
}
else
{
	$error = $form_login->getError();
	trace($error);
}
$this->addForm('login', $form_login');
```

La méthode `addForm` déclare l'instance de la classe Form pour permettre son accès dans le template :

```html
<html>
	<body>
		{form.login->display url='action/route' param1='value1'}
	</body>
</html>
```

### Exemples de tags supportés :

#### input[text|password|submit|...]

```
	{
		"label":"Input",
		"tag":"input",
		"require":cool,
		"attributes":
		{
			"type":"text"|"password"|"submit"...,
			"value":"Default Value",
			"class":...
		}
	}
```  

#### select

```  
	{
		"label":"Select",
		"tag":"select",
		"fromModel":
		{
			"model":"app\\models\\ModelName",
			"method":"all",
			"name":"field_name",
			"value":"field_name_id"
		}
	}
```

#### datepicker

```  
	{
		"label":"Datepicker",
		"tag":"datepicker"
	}
```  

#### upload  

```  
	{
		"label":"Fichier",
		"tag":"upload"
		"fileType":"jpg|png|...",
		"fileName":"someName{id}",
		"resize":[200, 200]
	}
```  

#### Groupe de boutons radios

```  
	{
		"label":"Radiogroup",
		"tag":"radiogroup",
		"display":"block",
		"height":"200px",
		"width":"400px",
		"fromModel":
		{
			"model":"app\\models\\ModelName",
			"method":"all",
			"name":"field_name",
			"value":"field_name_id"
		}
	}
```  

#### Groupe de checkbox

```  
	{
		"label":"Checkboxgroup",
		"tag":"checkboxgroup",
		"height":"200px",
		"width":"400px",
		"fromModel":
		{
			"model":"app\\models\\ModelName",
			"method":"all",
			"name":"field_name",
			"value":"field_name_id"
		}
	}
```

#### Captcha

```  
    {
        "label": "Captcha",
        "tag": "captcha",
        "type": "code|icons",
        "config": {
          "backgroundColor":"#ffffff",
          "fontSizeMax":13,
          "fontSizeMin":13,
          "width":100,
          "height":30,
          "rotation":15,
          "fontColors":["#444444","#ff0000","#00ff00"],
          "transparent":true,
          "length":7,
          "type":"calculus|random",
          "valueMax":99
        }
    }
```  

## Composants
Un composant est un ensemble de resources JS, CSS, medias regroupé pour permettre le bonne affichage / la bonne exécution d'une page / fonctionnalité.

Les composants sont décrits dans le fichier `includes/components/manifest.json`

Dans le projet, depuis un controller PHP il est possible de charger un composant via la méthode :

```php
Autoload::addComponent($pComponentName);
``` 

Cette méthode va s'occuper de centraliser l'ensemble des composants à charger afin de n'ajouter qu'une seule balise `script` et qu'une seule balise `link[rel="stylesheet"]`. Ces deux balises vont pointer leurs urls vers le `StaticController` du framework avec la liste des composants à charger en paramètres.

C'est ensuite la classe `Dependencies` qui va se charger de récupérer la liste des composants à charger pour en déduire les dépendances ainsi que l'ordre des fichiers (`js` ou `css`) à renvoyer.

**Note :** Les urls dans les fichiers `css` sont relatives aux fichiers et doivent être entourées de guillemets `"`

IE :
```css
/** mon image est présente dans le dossier path/to/imgs/ présent au même niveau que ma feuille de style **/
.some_class{background:url("path/to/imgs/mymg.png") no-repeat;}
```

## Debugger

```php
/**
 * Alias pour Debugger:trace
 * Méthode d'affichage d'une chaine de caractère dans le debugger
 * @parameter string $pString			Données à afficher
 * @parameter bool	 $pOpen 			Spécifie si le débugger doit être ouvert
 **/
trace($pString, $pOpen);

/**
 * Alias pour Debugger:trace_r
 * Pour les objets & les tableaux
 * @parameter object $pString			Données à afficher
 * @parameter bool	 $pOpen 			Spécifie si le débugger doit être ouvert
 **/
trace_r($pObject, $pOpen);

/**
 * Alias pour Debugger:track
 * Méthode de suivi du temps d'exécution et de l'usage de mémoire généré entre deux appels pour le même identifiant
 * @paramters string $pId               Identifiant du block de code à tracker
 */
track($pId);
```

## Logs

En complément du Debugger, il est possible d'écrire dans des fichiers de logs personnalisés via la classe `core\utils\Logs` :

```php
/**
 * Log d'une chaine de caractère
 */
\core\utils\Logs::write($pMessage, $pLogFile);

/**
 * Log d'un objet ou d'un tableau
 */
\core\utils\Logs::write_r($pData, $pLogFile);
```


## Authentification

Une authentification locale basée sur des utilisateurs en bases de données & une session serveur est disponible sur le framework. 

Il est possible de créer un utilisateur via la méthode `createUser` de la classe `ModelAuthentication` :
```php
\core\models\ModelAuthentication::createUser($pLogin, $pPassword, $pRole);
```

Les rôles sont définis par un entier (ex : 1 un utilisateur normal, 2 un administrateur - ayant accès au module back, 4 un développeur). Par le biais d'une manipulation bit à bit, il est possible s'assigner plusieurs droits à un même utilisateur (ex : 7 pour un utilisateur normal, un admin et un developpeur). 

Note : les droits développeurs donnent accès au Debugger quelque soit le contexte (une configuration en mode `debug` ou non).

Pour le modèle de données, se référer à la page wiki : [SQL Dump](https://github.com/arno06/php-fw/wiki/SQL-Dump)

Il est possible de surcharger le mode d'authentification en écrivant une classe de type AuthenticationHandler personnalisé implémentant l'interface `core\interfaces\AuthenticationHandlerInterface` et en le déclarant dans la configuration de l'application (ex : `dev.config.json`).

## Module de backoffice

Un module de backoffice de type CRUD correspond à un module de backoffice permettant à un utilisateur administrateur de manipuler les données présentes dans une table de la base de données.

La mise en place d'un tel module se fait en 4 étapes : 
 * Création d'une table de base de données (eg : `posts` contenant les champs `id_post`, `title_post`, `creation_date_post`, `content_post`)
 * Création d'un modèle de données (eg : `app\main\models\ModelPost`) - cette classe doit hériter de la classe `core\application\BaseModel` et doit préciser le nom de la table ainsi que le nom du champ de clé primaire dans le constructeur
 * Création d'un fichier de configuration du formulaire (eg : `includes/applications/main/modules/back/forms/form.post.json`) - ce fichier doit préciser les champs à afficher dans le formulaire de création / modification d'un post ainsi que les types de champs (input, textarea, datepicker, ...) et les règles de validation associées
 * Création d'un controller (eg : `app\main\controllers\back\posts`). Ce controller est une classe PHP devant héritée de `core\application\DefaultBackController` et qui doit préciser le modèle de données, le nom du formulaire ainsi que les champs à afficher dans le listing.

## Namespaces

| namespace                                           | contexte    | description                                                       |
|-----------------------------------------------------|-------------|-------------------------------------------------------------------|
| core \\ _{subPackage}_ \\                           | Global      | Classes & interfaces du package core                              |
| lib \\ _{package}_ \\ _{subPackage}_                | Global      | Classes & interfaces des packages secondaire                      |
| app \\ _{appName}_ \\ models                        | Application | Modèles de l'application _appName_                                |
| app \\ _{appName}_ \\ controllers \\ _{moduleName}_ | Application | Controllers du module _{moduleName}_ de l'application _{appName}_ |
| app \\ _{appName}_ \\ src \\ _{subPackage}_         | Application | Classes & interfaces de l'application _{appName}_                 |

## Todo (nice to have)

* [ ] RoutingHandler : method to get a route depending upon controller/action/method/parameters
* [ ] Integrate services managing
* [ ] Develop an Autocomplete component
* [ ] Dependencies : Add minified option
* [ ] SimpleCrawler : use Events for logging
* [ ] Dictionary : Implement dynamic "title" and "description" tags (like terms)
