<?php

namespace core\models{

    use core\db\QueryCondition;

    /**
     * Interface InterfaceBackModel
     * @package core\models
     * @property string $id
     */
    interface InterfaceBackModel{

        public function insert(array $pValues);

        public function getTupleById(string $pId):array;

        public function updateById(string $pId, array $pValues):mixed;

        public function deleteById(string $pId):mixed;

        public function all(QueryCondition $pCondition):array;

        public function count(QueryCondition $pCondition):int;

        public function getInsertId():int;

        public function generateInputsFromDescribe():array;
    }
}