<?php

namespace core\utils
{

    use core\application\Configuration;
    use core\data\SimpleXML;
    use core\tools\docs\PHPDocHelpers;

    class InteroperableObject
    {
        public function parse($pRaw = null, $pParams = null, $pQueryString = null)
        {
            if(is_null($pRaw)&&is_null($pParams)&&is_null($pQueryString)){
                return null;
            }

            $definition = IObjectDictionary::getDefinition(get_called_class());

            $classComments = $definition["classComments"];
            $allProps = $definition["props"];

            $pathThis = false;

            if(is_null($pRaw)){

                $api = PHPDocHelpers::extractDocVar('api', $classComments);
                if(!$api){
                    return null;
                }

                foreach($pParams as $name=>$val){
                    $api = str_replace('{'.$name.'}', $val, $api);
                }

                preg_match_all('/\{([a-z\.0-9]+)\}/', $api, $matches);

                foreach($matches[1] as $match){
                    $api = str_replace("{".$match."}", Configuration::extra($match), $api);
                }

                if(!is_null($pQueryString)){
                    if(strpos($api, "?")===false){
                        $api .= "?";
                    }else{
                        $api .= "&";
                    }
                    $api .= $pQueryString;
                }

                $format = PHPDocHelpers::extractDocVar('format', $classComments);

                if($format===false){
                    $format = "json";
                }

                $pRaw = RestHelper::request($api, RestHelper::HTTP_GET, array(), $format);
                $pathThis = PHPDocHelpers::extractDocVar('this', $classComments);
            }

            $raw = $pRaw;

            switch(true){
                case $pRaw instanceOf \SimpleXMLElement:
                    $extracts = array(
                        "attribute"=>function($pVal, \SimpleXMLElement $pXml){
                            $attr = $pXml->attributes();
                            return strval($attr[$pVal]);
                        },
                        "nodeValue"=>function($pVal, $pXml){
                            if($pVal !== "1"){
                                return null;
                            }
                            return strval($pXml);
                        },
                        "path"=>function($pVal, \SimpleXMLElement $pXml){
                            SimpleXML::registerNameSpaces($pXml);
                            return $pXml->xpath($pVal);
                        }
                    );
                    if($pathThis !== false){
                        $pRaw = $pRaw->xpath($pathThis)[0];
                    }
                    $format = "xml";
                    break;
                case is_array($pRaw):
                    $extracts = array(
                        "path"=>function($pVal, $pRaw){
                            return Stack::get($pVal, $pRaw);
                        }
                    );
                    if($pathThis !== false){
                        $pRaw = Stack::get($pathThis, $pRaw);
                    }
                    $format = "json";
                    break;
                default:
                    trigger_error('Unknown source type for parsing', E_USER_WARNING);
                    return null;
                    break;
            }

            $extraProps = [];
            for($i = 0, $max = count($allProps); $i<$max;$i++) {
                /** @var \ReflectionProperty $prop */
                $prop = $allProps[$i];
                $doc = $prop->getDocComment();
                $name = $prop->getName();
                foreach($extracts as $n=>$pCallback){
                    if($n === "path"){
                        $type = PHPDocHelpers::extractDocVar('var', $doc);
                        $path = PHPDocHelpers::extractDocVar('path', $doc);
                        if($path === false){
                            $extraProps[$name] = array("type"=>$type);
                            continue;
                        }
                        $this->setPropFromData($name, $type, $pCallback($path, $pRaw), $format);
                        break;//To check
                    }
                    $val = PHPDocHelpers::extractDocVar($n, $doc);
                    if($val !== false){
                        $this->{$name} = $pCallback($val, $pRaw);
                        break;//To check
                    }
                }
            }

            foreach($extraProps as $name=>$prop){
                $stackId = PHPDocHelpers::extractDocVar($name, $classComments);
                if(!$stackId){
                    continue;
                }
                $this->setPropFromData($name, $prop["type"], $extracts['path']($stackId, $raw));
            }

            return $pRaw;
        }

        /**
         * @param string $pName
         * @param string $pType
         * @param mixed $pData
         * @param string $pFormat
         */
        protected function setPropFromData($pName, $pType, $pData, $pFormat = "json"){
            if(!$pData){
                return;
            }

            $isArray = strpos($pType, '[]')!==false;

            if($isArray){
                $pType = str_replace('[]', '', $pType);
                $values = array();
                foreach($pData as $re){
                    $values[] = self::extractValue($pType, $re);
                }
            }else{
                if($pFormat === "xml"){
                    $pData = $pData[0];
                }
                $values = self::extractValue($pType, $pData);
            }
            $this->{$pName} = $values;
        }

        /**
         * @param string $pType
         * @param mixed $pSource
         * @return array|null
         */
        static public function extractValue($pType, $pSource){
            switch($pType){
                case "string":
                    $val = strval($pSource);
                    break;
                case '\DateTime':
                    $val = new \DateTime(strval($pSource));
                    break;
                case 'bool':
                    $val = strval($pSource) === 'true';
                    break;
                case 'int':
                    $val = intval($pSource);
                    break;
                default:
                    $val = null;
                    if(class_exists($pType)){
                        /** @var InteroperableObject $val */
                        $val = new $pType();
                        $val->parse($pSource);
                    }
                    break;
            }
            return $val;
        }
    }

    class IObjectDictionary
    {
        static private $classes = [];

        static public function getDefinition($pClassName){
            if(isset(self::$classes[$pClassName])){
                return self::$classes[$pClassName];
            }

            $reflec = new \ReflectionClass($pClassName);
            self::$classes[$pClassName] = array(
                "classComments"=>$reflec->getDocComment(),
                "props"=>$reflec->getProperties(\ReflectionProperty::IS_PUBLIC|\ReflectionProperty::IS_PROTECTED|\ReflectionProperty::IS_PRIVATE)
            );
            return self::$classes[$pClassName];
        }
    }
}