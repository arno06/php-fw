<?php

namespace core\db{

    use core\application\BaseModel;

    /**
     * Class DataManager
     * @package core\db
     */
    class DataManager{

        /**
         * @var array
         */
        private $tables = array();

        /**
         * @var array
         */
        private $foreign_values = array();

        /**
         * @param string $pTableName
         * @param string $pIdName
         * @param string $pSelectField
         * @return DataManagerSource
         */
        public function addSource($pTableName, $pIdName, $pSelectField){
            $ins = new DataManagerSource($pTableName, $pIdName, $pSelectField);
            $this->tables[] = $ins;
            return $ins;
        }

        /**
         * @param string $pTable
         * @param string $pField
         * @param mixed $pValue
         */
        public function addForeignValue($pTable, $pField, $pValue){
            if(!isset($this->foreign_values[$pTable.".".$pField])){
                $this->foreign_values[$pTable.".".$pField] = array();
            }
            $this->foreign_values[$pTable.".".$pField][] = $pValue;
        }

        /**
         * @param string $pHandler
         * @return array
         */
        public function export($pHandler = "default"){
            $tables = array();
            $schema = array();
            /** @var DataManagerSource $source */
            foreach($this->tables as $source){
                $schema[] = $source->toArray();
                $cond = Query::condition();
                $field_info = explode(".", $source->getSelectField());
                $field = array_pop($field_info);
                $cond->andWhere($field, Query::IN, "(".implode(', ', $this->foreign_values[$source->getSelectField()]).")", false);
                $data = Query::select('*', $source->getTable())->setCondition($cond)->execute($pHandler);

                $tables[$source->getTable()] = $data;

                if($source->hasForeignKeys()){
                    $keys = $source->getForeignKeys();
                    foreach($data as $datum){
                        foreach($keys as $name=>$alias){
                            if(!isset($foreign_values[$alias])){
                                $foreign_values[$alias] = array();
                            }
                            if(in_array($datum[$name], $foreign_values[$alias])||is_null($datum[$name])){
                                continue;
                            }
                            $foreign_values[$alias][] = $datum[$name];
                        }
                    }
                }
            }

            return array(
                "schema"=>$schema,
                "tables"=>$tables
            );

        }

        /**
         * @param array $pData
         * @param null $pDomain
         * @param string $pHandler
         */
        public function import($pData, $pDomain = null, $pHandler = "default"){
            $tables = array_reverse($pData['schema']);
            $data = $pData['tables'];
            $ids = array();

            foreach($tables as $info){
                $id = $info["id"];
                $select_id = $info["select_field"];
                $name = $info["table"];
                $insert = $data[$name];
                if(preg_match('/^'.$name.'\.(.+)$/', $select_id, $matches)){
                    $select_id = $matches[1];
                }

                if(!isset($ids[$name.".".$id])){
                    $ids[$name.".".$id] = array();
                }

                foreach($insert as $item){
                    $old_id = false;
                    if($id === $select_id){
                        $old_id = $item[$id];
                    }
                    unset($item[$id]);

                    if(isset($info["foreign_keys"])){
                        $keys = $info["foreign_keys"];
                        foreach($keys as $field=>$ref){
                            if(isset($item[$field])&& !empty($item[$field]) && isset($ids[$ref])){
                                if(isset($ids[$ref][$item[$field]])){
                                    $item[$field] = $ids[$ref][$item[$field]];
                                }
                            }
                        }
                    }

                    Query::insert($item)->into($name)->execute($pHandler);
                    if($old_id !== false){
                        $ids[$name.".".$id][$old_id] = DBManager::get($pHandler)->getInsertId();
                    }
                }

            }

            if(!is_null($pDomain) && isset($data["main_uploads"])){
                foreach($data["main_uploads"] as $datum){
                    $file = "http://".$pDomain."/".$datum["path_upload"];
                    file_put_contents($datum["path_upload"], file_get_contents($file));
                }
            }
        }
    }

    /**
     * Class DataManagerSource
     * @package core\db
     */
    class DataManagerSource{

        /**
         * @var string
         */
        private $table;

        /**
         * @var string
         */
        private $id;

        /**
         * @var string
         */
        private $select_field;

        /**
         * @var array
         */
        private $foreign_keys = array();

        /**
         * DataManagerSource constructor.
         * @param string $pTableName
         * @param string $pId
         * @param string $pSelectField
         */
        public function __construct($pTableName, $pId, $pSelectField){
            $this->table = $pTableName;
            $this->id = $pId;
            $this->select_field = $pSelectField;
        }

        /**
         * @param string $pField
         * @param string $pForeignTable
         * @param string $pForeignField
         * @return $this
         */
        public function addForeignKey($pField, $pForeignTable, $pForeignField){
            $this->foreign_keys[$pField] = $pForeignTable.".".$pForeignField;
            return $this;
        }

        /**
         * @return string
         */
        public function getId(){
            return $this->id;
        }

        /**
         * @return string
         */
        public function getTable(){
            return $this->table;
        }

        /**
         * @return string
         */
        public function getSelectField(){
            return $this->select_field;
        }

        /**
         * @return array
         */
        public function getForeignKeys(){
            return $this->foreign_keys;
        }

        /**
         * @return bool
         */
        public function hasForeignKeys(){
            return !empty($this->foreign_keys);
        }

        /**
         * @return array
         */
        public function toArray(){
            return array(
                "table"=>$this->table,
                "id"=>$this->id,
                "select_field"=>$this->select_field,
                "foreign_keys"=>$this->foreign_keys
            );
        }
    }
}