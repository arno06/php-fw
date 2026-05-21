# AGENTS.md

## Project Overview

This project is a modular PHP framework using an MVC architecture with multi-application support. Key points:

- **Applications & Modules**: Each application is in `includes/applications/{app}/`, with modules in `modules/{module}/` (e.g., `front`, `back`).
- **Configuration**: Environment mapping is in `includes/applications/setup.json`, with per-environment config in `{env}.config.json`.
- **Routing**: Routing rules are defined in `src/routing_rules.json` for each application. Default application is `main`, default module is `front`.
- **Views & Templates**: Template files (`.tpl`) are in `{app}/modules/{module}/views/{controller}/{action}.tpl`. A custom template engine with PHP context variables is used. Controllers populate template variables via `$this->addContent($pKey, $pValue)`.
- **Components**: JS/CSS components are declared in `includes/components/manifest.json` and loaded via `core\application\Autoload::addComponent($pComponentName)`.
- **CLI**: The template script for CLI usage is `cli/cli.php`. Use `core\utils\CLI::isCurrentContext()` to detect CLI execution.
- **Debugger**: Use `trace($pString, $pOpen)`, `trace_r($pObject, $pOpen)`, and `track($pId)` for debugging (see also `includes/components/debugger/`).
- **Events**: The framework uses `core\application\event\EventDispatcher` for event-driven architecture. Controllers extend `EventDispatcher` and can dispatch/listen to events.

## Coding Style Guidelines
To maintain consistency and readability across the codebase, we adhere to the following coding style guidelines:
 * Do not use Yoda conditions. For example, instead of writing `if (5 == x)`, write `if (x == 5)`.
 * Avoid using magic numbers. Instead, define constants with meaningful names to improve code readability and maintain.
 * Methods and functions parameters must start with a "p" prefix. For example, instead of `function calculateArea(radius)`, use `function calculateArea(pRadius)`.
 * Use camelCase for variable and function names. For example, instead of `let user_name = "John"`, use `let userName = "John"`.
 * Always use namespaces for classes (e.g., `core\application\Autoload`).
 * Name files according to their class/type (e.g., `class.Foo.php`, `controller.Bar.php`, `model.ModelFooBar.php`).
 * Any new JS/CSS component must be referenced in `includes/components/manifest.json` and loaded via `Autoload::addComponent($pComponentName)`.

## Architecture Patterns

### Controllers
- Controllers are located in `{app}/modules/{module}/controllers/` with files named `controller.{name}.php`
- Controllers extending `core\application\DefaultController` handle front-end logic (use `$this->addContent()` and `$this->addForm()` to pass data to templates)
- Controllers extending `core\application\DefaultBackController` handle admin CRUD operations with built-in list/add/edit/delete actions
- Controllers must declare an action method for each route (e.g., `public function index()`, `public function byId()`)

### Models
- Models are located in `{app}/models/` and should extend `core\application\BaseModel` if data source is a database table
- Specify table name and primary key in constructor: `parent::__construct('table_name', 'id_field')`
- Implement `core\models\InterfaceBackModel` for backoffice CRUD models
- Models use the `core\db\Query` builder for database operations across different handlers (e.g., `$handler="default"`)

### Views & Template System
- Views are located in `{app}/modules/{module}/views/{controller}/{action}.tpl` (or `{controller}.tpl` for the default `index` action)
- The custom template engine processes PHP context variables and supports: `{if}...{/if}`, `{foreach}...{/foreach}`, `{include file="..."}`, and direct PHP execution in safe mode
- Built-in template context includes: `$configuration`, `$forms`, `$content`, `$global` (GET/POST data)

### Authentication & Authorization
- Plug in custom authentication via `core\models\ModelAuthentication` or implement `core\interfaces\AuthenticationHandlerInterface`
- Declare the handler in the application config file (e.g., `dev.config.json`): `"authenticationHandler": "\\core\\application\\authentication\\MyAuthHandler"`
- User roles are defined as bit-flags (1=user, 2=admin, 4=developer); combine with bitwise OR to assign multiple roles
- Access developer features (debugger) with role 4, regardless of debug configuration

### Database & Query Builder
- Multiple database connections are managed via `core\db\DBManager` with handlers identified by name (e.g., `"default"`, `"vidalid"`)
- Use `core\db\Query` static methods for building queries: `Query::select(...)->where(...)->andWhere(...)->execute($pHandler)`
- Query conditions use constants: `Query::EQUAL`, `Query::IN`, `Query::LIKE`, etc for value comparisons

### Utilities & Helpers
- **File Operations**: `core\system\File` (create, delete, append, read), `core\system\Folder` (read directory), `core\system\Image` (resize, convert)
- **HTTP Requests**: `core\tools\Request` for HTTP calls, `core\utils\RestHelper` for REST APIs with optional caching
- **Data Import/Export**: `core\data\SimpleCSV`, `core\data\SimpleJSON`, `core\data\SimpleXML`
- **Utilities**: `core\utils\Logs`, `core\utils\CLI` (with progress bars and delays), `core\utils\SimpleRandom`, `core\utils\Cookie`
- **Pagination**: `core\tools\PaginationHandler` for managing paginated result sets
- **Menu System**: `core\tools\Menu` reads from JSON menu files to build navigation structures

## Contributing
 * Fork the repository and create a new branch for your feature or bug fix.
 * Commit message should be in french and descriptive
 * Before every commit, ensure that there is no call to `trace()` or `trace_r()` or `console.log()` in the code.