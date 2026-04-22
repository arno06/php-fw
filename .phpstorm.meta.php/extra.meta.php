<?php
namespace PHPSTORM_META{

    registerArgumentsSet('QueryConditionWhereComparison', \core\db\Query::EQUAL, \core\db\Query::LOWER, \core\db\Query::LOWER_EQUAL, \core\db\Query::UPPER, \core\db\Query::UPPER_EQUAL, \core\db\Query::IS, \core\db\Query::IS_NOT, \core\db\Query::IN, \core\db\Query::NOT_IN, \core\db\Query::LIKE,\core\db\Query::NOT_EQUAL, \core\db\Query::MATCH);
    expectedArguments(\core\db\QueryCondition::andWhere(), 1, argumentsSet('QueryConditionWhereComparison'));
    expectedArguments(\core\db\QueryCondition::orWhere(), 1, argumentsSet('QueryConditionWhereComparison'));
    expectedArguments(\core\db\QuerySelect::where(), 1, argumentsSet('QueryConditionWhereComparison'));

    registerArgumentsSet('QueryJoinTypes', \core\db\Query::JOIN_OUTER_LEFT, \core\db\Query::JOIN, \core\db\Query::JOIN_CROSS, \core\db\Query::JOIN_LEFT, \core\db\Query::JOIN_OUTER_RIGHT,\core\db\Query::JOIN_OUTER_FULL,\core\db\Query::JOIN_INNER,\core\db\Query::JOIN_UNION,\core\db\Query::JOIN_NATURAL);
    expectedArguments(\core\db\QuerySelect::join(), 1, argumentsSet('QueryJoinTypes'));

    /*CL*/registerArgumentsSet('ComponentsList', "Autocomplete","Backoffice","Captcha","Debugger","Form","M4Tween","Request","Uploader","WebCPicker");/*CL*/
    expectedArguments(\core\application\Autoload::addComponent(), 0, argumentsSet('ComponentsList'));


    /*CF*/registerArgumentsSet('ExtraList', "");/*CF*/
    expectedArguments(\core\application\Configuration::extra(), 0, argumentsSet('ExtraList'));

    registerArgumentsSet('RestUtilsMethods', \core\utils\RestHelper::HTTP_GET, \core\utils\RestHelper::HTTP_POST, \core\utils\RestHelper::HTTP_DELETE, \core\utils\RestHelper::HTTP_PATCH, \core\utils\RestHelper::HTTP_PUT);
    expectedArguments(\core\utils\RestHelper::request(), 1, argumentsSet('RestUtilsMethods'));
    registerArgumentsSet('RestUtilsContentTypes', \core\utils\RestHelper::FORMAT_JSON, \core\utils\RestHelper::FORMAT_XML, \core\utils\RestHelper::FORMAT_RAW);
    expectedArguments(\core\utils\RestHelper::request(), 3, argumentsSet('RestUtilsContentTypes'));

    registerArgumentsSet('SimpleRandomFormat', \core\utils\SimpleRandom::ALPHA_LOW|\core\utils\SimpleRandom::ALPHA_UP|\core\utils\SimpleRandom::NUMERIC);
    expectedArguments(\core\utils\SimpleRandom::string(), 1, argumentsSet('SimpleRandomFormat'));

    exitPoint(\trigger_error(ANY_ARGUMENT, 256));
    exitPoint(\core\application\Header::location());
    exitPoint(\core\application\Header::handleOptionsRequest());
    exitPoint(\core\application\Core::performResponse());
    exitPoint(\core\application\Core::endApplication());
}