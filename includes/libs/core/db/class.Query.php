<?php
namespace core\db
{
    use core\application\Configuration;
    use Exception;
    use SQLite3;

    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .5
     * @package db
     * @subpackage query
     */
    class Query
    {
        static public string $mode = "sql";

        const LIKE 				= 	" LIKE ";

        const EQUAL				= 	" = ";

        const NOT_EQUAL         =   " != ";

        const UPPER 			= 	" > ";

        const UPPER_EQUAL		=	" >= ";

        const LOWER 			= 	" < ";

        const LOWER_EQUAL		=	" <= ";

        const IS				=	" IS ";

        const IS_NOT			=	" IS NOT ";

        const JOIN				=	" JOIN ";

        const JOIN_NATURAL		=	" NATURAL JOIN ";

        const JOIN_INNER 		= 	" INNER JOIN ";

        const JOIN_OUTER_FULL 	= 	" FULL OUTER JOIN ";

        const JOIN_OUTER_LEFT 	= 	" LEFT OUTER JOIN ";

        const JOIN_OUTER_RIGHT 	= 	" RIGHT OUTER JOIN ";

        const JOIN_CROSS 		= 	" CROSS JOIN ";

        const JOIN_UNION 		= 	" UNION JOIN ";

        const IN                =   " IN ";

        const MATCH             =   " MATCH ";

        static private array $specials = array(
            "NOW()",
            "NULL"
        );

        /**
         * Méthode d'execution d'une requêtes SQL
         * @param  String $pQuery
         * @param  String $pHandler
         * @param  bool   $pRaw
         * @return mixed
         */
        static public function execute(string $pQuery, string $pHandler = "default", bool $pRaw = false):mixed
        {
            $dbHandler = DBManager::get($pHandler);
            if(!$dbHandler)
                return false;
            $raw = $pRaw||!preg_match("/^(select|show|describe|explain)/i", $pQuery);
            return $dbHandler->execute($pQuery, $raw);
        }

        /**
         * Méthode de création d'une requête SQL SELECT
         * @param string $pFields
         * @param string $pTables
         * @return QuerySelect
         */
        static public function select(string $pFields, string $pTables):QuerySelect
        {
            return new QuerySelect($pFields, $pTables);
        }

        /**
         * @static
         * @param string $pTable
         * @param null|QueryCondition $pCondition
         * @param String $pHandler
         * @return int
         */
        static public function count(string $pTable, QueryCondition $pCondition = null, string $pHandler = "default"):int
        {
            $q = Query::select("count(1) as nb", $pTable)->setCondition($pCondition)->execute($pHandler);
            return $q[0]["nb"];
        }

        /**
         * Méthode de création d'une condition SQL indépendante (instructions WHERE, ORDER BY, LIMIT...)
         * @return QueryCondition
         */
        static public function condition():QueryCondition
        {
            return new QueryCondition();
        }

        /**
         * Méthode de création d'une requête 'INSERT' d'insertion d'une tuple
         * @param array $pValues
         * @return QueryInsert
         */
        static public function insert(array $pValues):QueryInsert
        {
            return new QueryInsert($pValues, QueryInsert::UNIQUE);
        }

        /**
         * Méthode de création d'une requête 'INSERT' d'insertion de N tuples
         * @param array $pValues
         * @return QueryInsert
         */
        static public function insertMultiple(array $pValues):QueryInsert
        {
            return new QueryInsert($pValues, QueryInsert::MULTIPLE);
        }

        /**
         * @static
         * @param array $pValues
         * @return QueryReplace
         */
        static public function replace(array $pValues):QueryReplace
        {
            return new QueryReplace($pValues, QueryInsert::UNIQUE);
        }

        /**
         * @static
         * @param array $pValues
         * @return QueryReplace
         */
        static public function replaceMultiple(array $pValues):QueryReplace
        {
            return new QueryReplace($pValues, QueryInsert::MULTIPLE);
        }

        /**
         * Méthode de création d'une requête DELETE
         * @return QueryDelete
         */
        static public function delete():QueryDelete
        {
            return new QueryDelete();
        }

        /**
         * Méthode de création d'une requête UPDATE
         * @param String $pTable
         * @return QueryUpdate
         */
        static public function update(string $pTable):QueryUpdate
        {
            return new QueryUpdate($pTable);
        }

        /**
         * Méthode de création d'une requête DROP TABLE
         * @param string $pTable
         * @return QueryDrop
         */
        static public function drop(string $pTable):QueryDrop
        {
            return new QueryDrop($pTable);
        }

        /**
         * Méthode de création d'une requête TRUNCATE TABLE
         * @param string $pTable
         * @return QueryTruncate
         */
        static public function truncate(string $pTable):QueryTruncate
        {
            return new QueryTruncate($pTable);
        }

        /**
         * @static
         * @param string $pTable
         * @param string $pStorageEngine
         * @param string $pCollation
         * @return QueryCreate
         */
        static public function create(string $pTable, string $pStorageEngine = "InnoDB", string $pCollation = "latin1_swedish_ci"):QueryCreate
        {
            return new QueryCreate($pTable, $pStorageEngine, $pCollation);
        }

        /**
         * @static
         * @param string $pTable
         * @return QueryAlter
         */
        static public function alter(string $pTable):QueryAlter
        {
            return new QueryAlter($pTable);
        }

        /**
         * @static
         * @var string $pHandler
         * @return string
         */
        static public function getError(string $pHandler = "default"):string
        {
            return DBManager::get($pHandler)->getError();
        }

        /**
         * @static
         * @var string $pHandler
         * @return int
         */
        static public function getErrorNumber(string $pHandler = "default"):int
        {
            return DBManager::get($pHandler)->getErrorNumber();
        }

        /**
         * Méthode d'échappement d'une valeur (simple quote, double quote...)
         * @param string $pValue
         * @param bool $escape
         * @return string
         */
        static public function escapeValue(string $pValue, bool $escape = true):string
        {
            if (!$escape)
                return $pValue;
            if(!$pValue){
                return "''";
            }
            elseif(!in_array(strtoupper($pValue), self::$specials))
            {
                return match (self::$mode) {
                    "sqlite" => "'" . SQLite3::escapeString($pValue) . "'",
                    default => "'" . addslashes($pValue) . "'",
                };
            }
            else
                return strtoupper($pValue);
        }
    }

    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .2
     * @package db
     * @subpackage query
     */
    class BaseQuery
    {
        /**
         * Nom de la table
         * @var string|null
         */
        protected string|null $table;


        public function __construct(string $pTable = null)
        {
            $this->table = $pTable;
        }


        public function get():string
        {
            trigger_error("La méthode 'get' doit être surchargée.", E_USER_WARNING);
            return "";
        }


        public function execute(string $pHandler = "default", bool $pRaw = false):mixed
        {
            return Query::execute($this->get(), $pHandler, $pRaw);
        }
    }

    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .2
     * @package db
     * @subpackage query
     */
    class QueryCondition
    {
        private array $ands = [];

        private array $or = [];

        private array $havingAnds =[];

        private array $havingOr =[];

        private array $existAnds = [];

        private array $existOr = [];

        private string $order = "";

        private string $limit = "";

        private string $group = "";


        public function andExists(QuerySelect $pQuery):QueryCondition
        {
            $this->existAnds[] = "EXISTS (" . $pQuery->get() . ")";
            return $this;
        }


        public function orExists(QuerySelect $pQuery):QueryCondition
        {
            $this->existOr[] = "EXISTS (" . $pQuery->get() . ")";
            return $this;
        }


        public function andNotExists(QuerySelect $pQuery):QueryCondition
        {
            $this->existAnds[] = "NOT EXISTS (" . $pQuery->get() . ")";
            return $this;
        }


        public function orNotExists(QuerySelect $pQuery):QueryCondition
        {
            $this->existOr[] = "NOT EXISTS (" . $pQuery->get() . ")";
            return $this;
        }

        /**
         * Méthode d'ajout d'une condition 'OR' à l'instance de condition en cours
         * @param string $pField
         * @param string $pType
         * @param string $pValue
         * @param bool $pEscape
         * @return $this
         */
        public function orWhere	(string $pField, string $pType, string $pValue, bool $pEscape = true):QueryCondition
        {
            if($pEscape)
                $pValue = Query::escapeValue($pValue);
            $this->or[] = $pField . $pType . $pValue;
            return $this;
        }

        /**
         * Méthode d'ajout d'une condition 'AND' à l'instance de condition en cours
         * @param string $pField
         * @param string $pType
         * @param string $pValue
         * @param bool   $pEscape
         * @return QueryCondition
         */
        public function andWhere(string $pField, string $pType, string $pValue, bool $pEscape = true):QueryCondition
        {
            if($pEscape)
                $pValue = Query::escapeValue($pValue);
            if ($pType == Query::MATCH)
                $this->ands[] = " MATCH(" . $pField . ") AGAINST (" . $pValue . ")";
            else
                $this->ands[] = $pField . $pType . $pValue;
            return $this;
        }


        public function andMatch(string $pField, string $pValue):QueryCondition
        {
            $temp = trim($pValue);
            if (empty($temp)) return $this;

            $keywords = explode(" ", $pValue);
            $against = "";
            for($i = 0, $max = count($keywords) ; $i < $max ; $i++)
                if (!empty($keywords[$i]))
                {
                    $against .= "+".$keywords[$i];
                    if ($i < $max-1) $against .= " ";
                }

            if ($pValue[strlen($pValue)-1] != " ")
                $against .= "*";
            $this->ands[] = " MATCH(" . $pField . ") AGAINST(" . Query::escapeValue($against) . " IN BOOLEAN MODE)";
            return $this;
        }

        /**
         * Méthode d'ajout d'un HAVING 'OR' à l'instance de condition en cours
         * @param String $pField
         * @return QueryCondition
         */
        public function orHaving(string $pField):QueryCondition
        {
            $this->havingOr[] = Query::escapeValue($pField);
            return $this;
        }

        /**
         * Méthode d'ajout d'un HAVING 'AND' à l'instance de condition en cours
         * @param string $pField
         * @return QueryCondition
         */
        public function andHaving(string $pField):QueryCondition
        {
            $this->havingAnds[] = Query::escapeValue($pField);
            return $this;
        }

        /**
         * Méthode d'ajout d'un 'GROUP BY'
         * @param String $pField
         * @return QueryCondition
         */
        public function groupBy(string $pField):QueryCondition
        {
            $this->group = " GROUP BY ".$pField;
            return $this;
        }

        /**
         * Méthode d'ajout d'une instance existante d'une condition dans un 'AND' dans l'instance de la condition en cours
         * @param QueryCondition $pCondition
         * @return QueryCondition
         */
        public function andCondition(QueryCondition $pCondition):QueryCondition
        {
            $condition = $pCondition->getWhere();
            if(!empty($condition))
                $this->ands[] = "(" . preg_replace("/^ WHERE /i", "", $condition) . ")";
            return $this;
        }

        /**
         * Méthode d'ajout d'une instance existante d'une condition dans un 'OR' dans l'instance de la condition en cours
         * @param QueryCondition $pCondition
         * @return QueryCondition
         */
        public function orCondition(QueryCondition $pCondition):QueryCondition
        {
            $this->or[] = "(" . preg_replace("/^ WHERE /i", "", $pCondition->getWhere()) . ")";
            return $this;
        }

        /**
         * Méthode d'ajout d'un 'ORDER BY'
         * @param string $pField
         * @param string $pType		ASC|DESC
         * @return QueryCondition
         */
        public function order(string $pField, string $pType = "ASC"):QueryCondition
        {
            if($this->order=="")
                $this->order = " ORDER BY ".$pField." ".$pType;
            else
                $this->order .= ", ".$pField." ".$pType;
            return $this;
        }

        /**
         * Méthode d'ajout d'une LIMIT
         * @param Int $pFirst
         * @param Int $pNumber
         * @return QueryCondition
         */
        public function limit(int $pFirst,int  $pNumber):QueryCondition
        {
            $this->limit = " LIMIT ".$pFirst.",".$pNumber;
            return $this;
        }

        /**
         * Méthode de génération de la condition
         * @return string
         */
        public function get():string
        {
            return $this->getWhere().$this->group.$this->getHaving().$this->order.$this->limit;
        }

        /**
         * Méthode de génération de la section WHERE de l'instance de la condition en cours
         * @return string
         */
        public function getWhere():string
        {
            $where = "";
            $ands = implode(" AND ", $this->ands);
            $or = implode(" OR ", $this->or);
            $existAnds = implode(" AND ", $this->existAnds);
            $existOr = implode(" OR ", $this->existOr);
            if(!empty($ands))
                $where .= " WHERE ".$ands;
            if(!empty($or))
            {
                if(empty($ands))
                    $where .= " WHERE ".$or;
                else
                    $where .= " OR ".$or;
            }
            if(!empty($existAnds))
            {
                if(empty($where))
                    $where .= " WHERE ".$existAnds;
                else
                    $where .= " AND ".$existAnds;
            }
            if(!empty($existOr))
            {
                if(empty($where))
                    $where .= " WHERE ".$existOr;
                else
                    $where .= " AND ".$existOr;
            }
            return $where;
        }


        public function getHaving():string
        {
            $having = "";
            $ands = implode(" AND ", $this->havingAnds);
            $or = implode(" OR ", $this->havingOr);
            if(!empty($ands))
                $having .= " HAVING ".$ands;
            if(!empty($or))
            {
                if(empty($ands))
                    $having .= " HAVING ".$or;
                else
                    $having .= " OR ".$or;
            }
            return $having;
        }
    }


    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .2
     * @package db
     * @subpackage query
     */
    class QueryWithCondition extends BaseQuery
    {
        protected QueryCondition|null $condition = null;


        /**
         * Méthode de définition de la condition d'une requête SELECT
         * @param QueryCondition $pConditionInstance
         * @return QueryWithCondition
         */
        public function setCondition(QueryCondition $pConditionInstance):QueryWithCondition
        {
            $this->condition = $pConditionInstance;
            return $this;
        }


        public function having(string $pField):QueryWithCondition
        {
            $this->getCondition()->andHaving($pField);
            return $this;
        }


        public function andHaving(string $pField):QueryWithCondition
        {
            $this->getCondition()->andHaving($pField);
            return $this;
        }


        public function orHaving(string $pField):QueryWithCondition
        {
            $this->getCondition()->orHaving($pField);
            return $this;
        }

        /**
         * Méthode d'ajout d'une condition 'WHERE' à la requête SELECT en cours
         * @param string $pField
         * @param string $pType
         * @param string $pValue
         * @param bool $pEscape
         * @return QueryWithCondition
         */
        public function where(string $pField, string $pType, string $pValue, bool $pEscape = true):QueryWithCondition
        {
            $this->getCondition()->andWhere($pField, $pType, $pValue, $pEscape);
            return $this;
        }

        /**
         * Méthode d'ajout d'une condition 'AND' à la requête SELECT en cours
         * @param string $pField
         * @param string $pType
         * @param string $pValue
         * @param bool $pEscape
         * @return QueryWithCondition
         */
        public function andWhere(string $pField, string $pType, string $pValue, bool $pEscape = true):QueryWithCondition
        {
            $this->getCondition()->andWhere($pField, $pType, $pValue, $pEscape);
            return $this;
        }

        /**
         * Méthode d'ajout d'une condition 'OR' à la requête SELECT en cours
         * @param string $pField
         * @param string $pType
         * @param string $pValue
         * @return QueryWithCondition
         */
        public function orWhere(string $pField, string $pType, string $pValue):QueryWithCondition
        {
            $this->getCondition()->orWhere($pField, $pType, $pValue);
            return $this;
        }


        public function andExists(QuerySelect $pQuery):QueryWithCondition
        {
            $this->getCondition()->andExists($pQuery);
            return $this;
        }


        public function orExists(QuerySelect $pQuery):QueryWithCondition
        {
            $this->getCondition()->orExists($pQuery);
            return $this;
        }


        public function andNotExists(QuerySelect $pQuery):QueryWithCondition
        {
            $this->getCondition()->andNotExists($pQuery);
            return $this;
        }


        public function orNotExists(QuerySelect $pQuery):QueryWithCondition
        {
            $this->getCondition()->orNotExists($pQuery);
            return $this;
        }

        /**
         * Méthode d'ajout d'une condition imbriquée dans la condition de la requête en cours
         * @param QueryCondition $pCondition
         * @return QueryWithCondition
         */
        public function andCondition(QueryCondition $pCondition):QueryWithCondition
        {
            $this->getCondition()->andCondition($pCondition);
            return $this;
        }


        public function orCondition(QueryCondition $pCondition):QueryWithCondition
        {
            $this->getCondition()->orCondition($pCondition);
            return $this;
        }

        /**
         * Méthode d'ajout d'un 'ORDER BY'
         * @param string $pField
         * @param string $pType		ASC|DESC
         * @return QueryWithCondition
         */
        public function order(string $pField, string $pType = "ASC"):QueryWithCondition
        {
            $this->getCondition()->order($pField, $pType);
            return $this;
        }


        /**
         * Méthode d'ajout d'une LIMIT
         * @param Int $pFirst
         * @param Int $pNumber
         * @return QueryWithCondition
         */
        public function limit(int $pFirst, int $pNumber):QueryWithCondition
        {
            $this->getCondition()->limit($pFirst, $pNumber);
            return $this;
        }

        /**
         * Méthode d'ajout d'un 'GROUP BY'
         * @param string $pField
         * @return QueryWithCondition
         */
        public function groupBy(string $pField):QueryWithCondition
        {
            $this->getCondition()->groupBy($pField);
            return $this;
        }


        protected function getCondition():QueryCondition
        {
            if(is_null($this->condition))
                $this->condition = Query::condition();
            return $this->condition;
        }
    }


    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .2
     * @package db
     * @subpackage query
     */
    class QuerySelect extends QueryWithCondition
    {
        private array $tables = [];

        private array $fields = [];

        private string $joins = "";

        private array $query_union;


        public function __construct(string $pFields, string $pTables)
        {
            parent::__construct();
            $this->addFrom($pFields, $pTables);
        }


        public function join(string $pTable, string $pType = " NATURAL JOIN ", string $pOn = ""):QuerySelect
        {
            if(!empty($pOn))
                $pOn = "ON ".$pOn;
            $this->joins .= $pType.$pTable." ".$pOn;
            return $this;
        }

        /**
         * Méthode d'ajout d'une table et de champs au SELECT en cours
         * @param string $pFields
         * @param string $pTables
         * @return QuerySelect
         */
        public function addFrom(string $pFields, string $pTables):QuerySelect
        {
            if(!in_array($pTables, $this->tables))
                $this->tables[] = $pTables;
            if(!in_array($pFields, $this->fields))
                $this->fields[] = $pFields;
            return $this;
        }


        public function union(QuerySelect $pQuery):QuerySelect
        {
            if(!isset($this->query_union))
                $this->query_union = [];
            $this->query_union[] = $pQuery;
            return $this;
        }

        /**
         * Méthode de génération de la requête
         * @param bool $pSemicolon
         * @return string
         */
        public function get(bool $pSemicolon = true):string
        {
            $field = implode(",", $this->fields);
            $table = implode(",", $this->tables);
            $joins = $this->joins." ";
            $condition = $this->getCondition()->get();
            $union = "";
            if(!empty($this->query_union))
            {
                foreach($this->query_union as $q)
                    $union .= " UNION ".preg_replace("/;$/", "", $q->get());
            }
            $str = "SELECT " . $field . " FROM " . $table . " " . $joins . $condition . $union;
            if ($pSemicolon) $str .= ";";
            return $str;
        }


        public function execute(string $pHandler = "default", bool $pRaw = false):mixed
        {
            $result = Query::execute($this->get(), $pHandler, $pRaw);
            if (Configuration::$global_explainOnSelect === true)
                $this->explain($pHandler);
            return $result;
        }

        /**
         * @Author Alain LEE - alee@cbi-multimedia.com
         * @param string $pHandler
         * @return array|resource
         */
        public function explain(string $pHandler = "default"):mixed
        {
            $query = $this->get();
            $result = Query::execute("EXPLAIN ".$query, $pHandler);
            $useKey = true;
            $useTemporary = false;
            $useFileSort = false;
            $tableWithNoIndex = [];
            $errors = [];
            if (is_array($result))
                foreach($result as $row)
                {
                    if (!empty($row['type']) && $row['type'] == "ALL" && $row['select_type'] != "UNION RESULT")
                    {
                        $tableWithNoIndex[] = $row['table'];
                        $useKey = false;
                    }
                    if (!empty($row['Extra']))
                    {
                        if (stripos($row['Extra'], "Using temporary") !== false)
                            $useTemporary = true;
                        if (stripos($row['Extra'], "Using filesort") !== false)
                            $useFileSort = true;
                    }
                }

            if ((sizeof($result) > 1 || stripos($query, " WHERE ") !== false) && !$useKey)
                foreach($tableWithNoIndex as $t)
                    $errors[] = "La table <b>".$t."</b> n'utilise pas d'index";

            if ($useTemporary)
                $errors[] = "Utilisation d'une table temporaire";

            if ($useFileSort)
                $errors[] = "Un tri nécessite un deuxi&egrave;me passage dans les résultats et peut ralentir la requête";

            if (!empty($errors))
            {
                $displayError = "La requête \"<b>".$query."</b>\" peut présenter des lenteurs :";
                foreach($errors as $e)
                    $displayError .= "<br/>- ".$e;
                trigger_error($displayError, E_USER_WARNING);
            }

            return $result;
        }
    }


    /**
     * @Author Alain LEE - alee@cbi-multimedia.com
     * @version .2
     * @package db
     * @subpackage query
     */
    class QueryReplace extends QueryInsert
    {
        /**
         * Méthode de génération de la requête
         * @return string
         */
        public function get():string
        {
            $values = implode(",", $this->values);
            return 'REPLACE INTO '.$this->table.' '.$this->fields.' VALUES '.$values.';';
        }
    }


    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .2
     * @package db
     * @subpackage query
     */
    class QueryUpdate extends QueryWithCondition
    {
        /**
         * Tableau des valeurs à mettre-à-jour
         * @var array
         */
        private array $values = [];

        /**
         * Méthode de définition des champs à mettre-à-jour
         * @param array $pValues
         * @param bool $pEscape
         * @return QueryUpdate
         */
        public function values(array $pValues, bool $pEscape = true):QueryUpdate
        {
            foreach($pValues as $field=>$value)
                $this->values[] = $field . "=" . Query::escapeValue($value, $pEscape);
            return $this;
        }

        /**
         * Méthode de génération de la méthode
         * @return string
         */
        public function get():string
        {
            $values = implode(",", $this->values);
            $condition = $this->getCondition()->get();
            return "UPDATE ".$this->table." SET ".$values.$condition.";";
        }
    }


    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .2
     * @package db
     * @subpackage query
     */
    class QueryInsert extends BaseQuery
    {
        const UNIQUE = "UNIQUE";

        const MULTIPLE = "MULTIPLE";

        /**
         * Chaine de caract&egrave;res des champs de la table à remplir
         * @var String
         */
        protected string $fields = "";
        /**
         * Tableau de chaines de caract&egrave;res des valeurs à insérer
         * @var array
         */
        protected array $values = [];


        public function __construct(array $pValues, string $pType = "")
        {
            parent::__construct();
            switch($pType)
            {
                case QueryInsert::MULTIPLE:
                    $this->setFields($pValues[0]);
                    $this->setValues($pValues);
                    break;
                case QueryInsert::UNIQUE:
                default:
                    $this->setFields($pValues);
                    $this->setValues(array($pValues));
                    break;
            }
        }

        /**
         * Méthode de définition des champs de la table en fonction des clés du tableau de valeurs envoyées
         * @param array $pTuple
         * @return void
         */
        private function setFields(array $pTuple):void
        {
            $f = array_keys($pTuple);
            $this->fields = "(".implode(",", $f).")";
        }

        /**
         * Méthode de définition et d'échappement des valeurs à insérer
         * @param array $pTuples
         * @return void
         */
        private function setValues(array $pTuples):void
        {
            $this->values = [];
            for($i = 0, $max = count($pTuples); $i<$max; $i++)
            {
                $pTuples[$i] = array_map("core\\db\\Query::escapeValue", $pTuples[$i]);
                $this->values[] = "(" . implode(",", $pTuples[$i]) . ")";
            }
        }

        /**
         * Méthode de définition du nom de la table dans laquelle insérer les valeurs
         * @param string $pTable
         * @return QueryInsert
         */
        public function into(string $pTable):QueryInsert
        {
            $this->table = $pTable;
            return $this;
        }

        /**
         * Méthode de génération de la requête
         * @return string
         */
        public function get():string
        {
            $values = implode(",", $this->values);
            return "INSERT INTO ".$this->table." ".$this->fields." VALUES ".$values.";";
        }
    }


    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .2
     * @package db
     * @subpackage query
     */
    class QueryDelete extends QueryWithCondition
    {
        /**
         * Méthode de définition de la table à cibler pour la suppression
         * @param string $pTable
         * @return QueryDelete
         */
        public function from(string $pTable):QueryDelete
        {
            $this->table = $pTable;
            return $this;
        }

        /**
         * Méthode de génération de la requête
         * @return string
         */
        public function get():string
        {
            $condition = $this->getCondition()->get();
            return "DELETE " . " FROM `".$this->table."`".$condition.";";
        }
    }


    class QueryTruncate extends BaseQuery
    {
        /**
         * Méthode de génération de la requête
         * @return string
         */
        public function get():string
        {
            return "TRUNCATE TABLE '".$this->table."';";
        }
    }


    class QueryDrop extends BaseQuery
    {
        /**
         * Méthode de génération de la requête
         * @return string
         */
        public function get():string
        {
            return "DROP TABLE `".$this->table."';";
        }
    }


    class QueryCreate extends QueryStructure
    {

        public function __construct(string $pTable, string $pStorageEngine, string $pCollation)
        {
            parent::__construct($pTable);
            $this->storage_engine = $pStorageEngine;
            $this->collation = $pCollation;
        }


        public function get():string
        {
            $query = "CREATE TABLE IF NOT EXISTS `".$this->table."` (";
            if(!empty($this->primary))
                $this->add_fields[] = "PRIMARY KEY (".implode(", ",$this->primary).")";
            $query.= implode(", ", $this->add_fields);
            $char = explode("_", $this->collation);
            $query .= ") ENGINE=".$this->storage_engine." CHARACTER SET ".$char[0]." COLLATE ".$this->collation;
            if($this->hasAI)
                $query .= " AUTO_INCREMENT=1";
            $query .= ";";
            return $query;
        }
    }


    class QueryAlter extends QueryStructure
    {
        private string $change_fields = "";

        private array $removed_fields = [];

        /**
         * @param string $pName
         * @param string $pType
         * @param string $pSize
         * @param string $pDefaultValue
         * @param bool $pNull
         * @param bool $pAI
         * @param string $pIndex
         * @param string $pComments
         * @param string $pWhere
         * @return $this
         * @throws Exception
         */
        public function addField(string $pName, string $pType, string $pSize = "", string $pDefaultValue = "", bool $pNull = false, bool $pAI = false, string $pIndex = "", string $pComments = "", string $pWhere = ""):QueryAlter
        {
            if(!empty($this->change_fields))
                throw new Exception("Impossible de faire appel à la commande SQL ADD lorsque la commande CHANGE est définie");
            parent::addField($pName, $pType, $pSize, $pDefaultValue, $pNull, $pAI, $pIndex, $pComments, $pWhere);
            return $this;
        }

        /**
         * @param string $pName
         * @param string $pType
         * @param string $pSize
         * @param string $pDefaultValue
         * @param bool $pNull
         * @param bool $pAI
         * @param string $pIndex
         * @param string $pComments
         * @return $this
         * @throws Exception
         */
        public function changeField(string $pName, string $pType, string $pSize = "", string $pDefaultValue = "", bool $pNull = false, bool $pAI = false, string $pIndex = "", string $pComments = ""):QueryAlter
        {
            if(!empty($this->change_fields))
                throw new Exception("Impossible de faire appel à la commande SQL CHANGE sur plusieurs champs simultamément");
            if(!empty($this->add_fields))
                throw new Exception("Impossible de faire appel à la commande SQL CHANGE lorsque la commande ADD est définie");
            $this->change_fields = "`".$pName."` ".$this->setupField($pName, $pType, $pSize, $pDefaultValue, $pNull, $pAI, $pIndex, $pComments);
            return $this;
        }


        public function removeField(string $pName):QueryAlter
        {
            $this->removed_fields[] = "DROP COLUMN ".$pName;
            return $this;
        }


        public function get():string
        {
            $add = "";
            if(!empty($this->add_fields))
                $add = " ADD ".implode(", ", $this->add_fields);
            $change = "";
            if(!empty($this->change_fields))
                $change = " CHANGE ".$this->change_fields;
            $remove = "";
            if(!empty($this->removed_fields)){
                $remove = " ".implode(', ', $this->removed_fields);
            }
            return "ALTER TABLE `".$this->table."`".$add.$change.$remove.";";
        }
    }

    class QueryStructure extends BaseQuery
    {
        protected array $add_fields = [];

        protected string $storage_engine = "";

        protected string $collation = "";

        protected array $primary = [];

        protected bool $hasAI = false;

        /**
         * @param string $pName
         * @param string $pType
         * @param string $pSize
         * @param string $pDefaultValue
         * @param bool $pNull
         * @param bool $pAI
         * @param string $pIndex
         * @param string $pComments
         * @param string $pWhere
         * @return $this
         * @throws Exception
         */
        public function addField(string $pName, string $pType, string $pSize = "", string $pDefaultValue = "", bool $pNull = false, bool $pAI = false, string $pIndex = "", string $pComments = "", string $pWhere = ""):QueryStructure
        {
            $this->add_fields[] = $this->setupField($pName, $pType, $pSize, $pDefaultValue, $pNull, $pAI, $pIndex, $pComments, $pWhere);
            return $this;
        }

        /**
         * @param string $pName
         * @param string $pType
         * @param string $pSize
         * @param string $pDefaultValue
         * @param bool $pNull
         * @param bool $pAI
         * @param string $pIndex
         * @param string $pComments
         * @param string $pWhere
         * @return string
         * @throws Exception
         */
        protected function setupField(string $pName, string $pType, string $pSize = "", string $pDefaultValue = "", bool $pNull = false, bool $pAI = false, string $pIndex = "", string $pComments = "", string $pWhere = ""):string
        {
            $field_prop = array("`".$pName."`");
            if(preg_match('/(varchar)$/i', $pType) &&(empty($pSize)))
                throw new Exception("Le type 'varchar' requiert la définition d'une taille de champ.");
            if(preg_match("/(int|varchar)$/i", $pType) && !preg_match('/\([0-9]+\)/', $pType))
            {
                if(!$pSize)
                    $pSize = 11;
                $pType .= "(".$pSize.")";
            }
            $field_prop[] = $pType;
            if(!$pNull)
                $field_prop[] = "NOT NULL";
            else
                $field_prop[] = "NULL";
            if(!empty($pDefaultValue))
                $field_prop[] = "DEFAULT '".$pDefaultValue."'";
            if($pAI)
            {
                if($this->hasAI)
                    throw new Exception("Une table ne peut contenir qu'un seul champ avec incrémentation automatique.");
                $this->hasAI = true;
                $field_prop[] = "AUTO_INCREMENT";
            }
            if(!empty($pIndex)&&strtoupper($pIndex)!="PRIMARY")
                $field_prop[] = $pIndex;
            if(strtoupper($pIndex)=="PRIMARY")
                $this->primary[] = "`".$pName."`";

            if(!empty($pComments))
                $field_prop[] = "COMMENT '".$pComments."'";
            if(!empty($pWhere))
                $field_prop[] = $pWhere;
            return implode(" ", $field_prop);
        }
    }
}
