<?php
namespace core\utils{
    class XMLItem{

        /**
         * @var string
         */
        protected $phpdoc_class = "";

        /**
         * @var array
         */
        protected $extra_props = array();

        /**
         * @var array
         */
        private $xml_props = array();


        public function __construct()
        {
            $this->parsePHPDocs();
        }

        /**
         * @param \SimpleXMLElement $pXml
         */
        public function parse($pXml){
            self::registerNameSpaces($pXml);
            foreach($this->xml_props as $name=>$prop){
                if(isset($prop["attribute"])){
                    $attr = $pXml->attributes();
                    $this->{$name} = strval($attr[$prop["attribute"]]);
                    continue;
                }
                if(isset($prop["nodeValue"])&&$prop["nodeValue"]=="1"){
                    $this->{$name} = strval($pXml);
                    continue;
                }

                if(!isset($prop["xpath"])){
                    continue;
                }
                $this->setPropFromXMLElement($name, $prop["type"], $pXml->xpath($prop["xpath"]));
            }
        }

        /**
         * @param string $pName
         * @param string $pType
         * @param \SimpleXMLElement[] $pXMLElement
         */
        protected function setPropFromXMLElement($pName, $pType, $pXMLElement){
            if(!$pXMLElement){
                return;
            }
            $this->{$pName} = self::extractValue($pType, $pXMLElement);
        }

        /**
         * @return array
         */
        public function getExtraProps(){
            return $this->extra_props;
        }

        protected function parsePHPDocs(){

            $reflec = new \ReflectionClass(get_called_class());

            $this->phpdoc_class = $reflec->getDocComment();

            $allProps = $reflec->getProperties(\ReflectionProperty::IS_PUBLIC|\ReflectionProperty::IS_PROTECTED|\ReflectionProperty::IS_PRIVATE);
            for($i = 0, $max = count($allProps); $i<$max;$i++) {
                $prop = $allProps[$i];
                $doc = $prop->getDocComment();
                $type = self::extractDocVar('var', $doc);

                $attribute = self::extractDocVar('attribute', $doc);
                if ($attribute) {
                    $this->xml_props[$prop->getName()] = array("attribute"=>$attribute);
                    continue;
                }
                $nodeValue = self::extractDocVar('nodeValue', $doc);
                if ($nodeValue && $nodeValue == "1") {
                    $this->xml_props[$prop->getName()] = array("nodeValue"=>"1");
                    continue;
                }

                $xpath = self::extractDocVar('xpath', $doc);
                if (empty($xpath)) {
                    $this->extra_props[$prop->getName()] = array("type"=>$type, "phpdoc"=>$doc);
                    continue;
                }
                $this->xml_props[$prop->getName()] = array("xpath"=>$xpath, "type"=>$type);
            }
        }

        /**
         * @param string $pVarName
         * @param string $pComments
         * @return bool|string
         */
        static public function extractDocVar($pVarName, $pComments)
        {
            if(preg_match('/@'.$pVarName.'\s*([0-9a-z\_\[\]\/\"\=\:\^\@\(\)\\\\{\}\-]+)\s*/i', $pComments, $matches))
            {
                return $matches[1];
            }
            return false;
        }

        /**
         * @param string $pType
         * @param \SimpleXMLElement[] $pSource
         * @return array|null
         */
        static public function extractValue($pType, $pSource){

            $isArray = strpos($pType, '[]')!==false;

            if($isArray){
                $pType = str_replace('[]', '', $pType);
            }

            $values = array();
            foreach($pSource as $re){
                $val = strval($re);
                switch($pType){
                    case "string":
                        break;
                    case '\DateTime':
                        $val = new \DateTime(strval($re));
                        break;
                    case 'bool':
                        $val = $val === 'true';
                        break;
                    default:
                        if(class_exists($pType)){
                            /** @var XMLItem $val */
                            $val = new $pType();
                            $val->parse($re);
                        }
                        break;
                }
                $values[] = $val;
            }
            if(!$isArray&&empty($values)){
                return null;
            }
            if(!$isArray){
                return $values[0];
            }
            return $values;
        }

        /**
         * @param \SimpleXMLElement $pXml
         */
        static public function registerNameSpaces(\SimpleXMLElement $pXml){
            $ns = $pXml->getNamespaces(true);
            foreach($ns as $a=>$n){
                if(empty($a)){
                    $a = "default";
                }
                $pXml->registerXPathNamespace($a, $n);
            }
        }
    }
}