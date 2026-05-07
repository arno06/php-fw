<?php

namespace core\utils{

    use core\application\Autoload;
    use core\application\Singleton;

    class OPCacheHelper extends Singleton
    {
        private bool $enabled = false;

        private array $scripts = array();


        public function __construct()
        {
            if(!function_exists('opcache_get_status')){
                return;
            }

            $status = opcache_get_status();

            $this->enabled = $status['opcache_enabled']==1;

            foreach($status['scripts'] as $path=>$file){
                if(!str_contains($path, Autoload::$folder) || !$file['timestamp']){
                    continue;
                }
                $this->scripts[] = $file;
            }
        }


        public function countScripts():int
        {
            return count($this->scripts);
        }


        private function getScripts():array|null
        {
            if(!$this->enabled){
                return null;
            }

            $scripts = array();

            for($i = 0, $max = count($this->scripts); $i<$max; $i++){
                $file = $this->scripts[$i];
                $fullPath = $file['full_path'];
                $localPath = str_replace(Autoload::$folder.'/', '', $fullPath);
                $parts = explode("/", $localPath);
                $file = array_pop($parts);
                $target = &$scripts;
                foreach($parts as $dir){
                    if(!isset($target[$dir])){
                        $target[$dir] = array("name"=>$dir, "children"=>array(), "type"=>"dir");
                    }
                    $target = &$target[$dir]["children"];
                }
                $target[] = array(
                    "name"=>$file,
                    "type"=>"file",
                    "fullPath"=>$fullPath
                );
            }

            $this->order($scripts);

            return $scripts;
        }


        public function invalidate(string $pScript):bool
        {
            if(!opcache_is_script_cached($pScript)){
                return false;
            }
            return opcache_invalidate($pScript, true);
        }


        public function isEnabled():bool
        {
            return $this->enabled;
        }


        public function prettyPrint():string
        {
            $scripts = $this->getScripts();
            if(empty($scripts)||!$this->enabled){
                return "";
            }
            $return = "<ul class='opcache'>";
            $return .= $this->prepareData($scripts);
            $return .= "</ul>";
            return $return;
        }


        private function prepareData(array $pScripts):string
        {
            $return = '';
            foreach($pScripts as $script){
                if($script['type'] == 'dir'){
                    $return .= '<li class="opcache-dir closed"><span>📁 '.$script['name'].'</span>';
                    $return .= '<ul>'.$this->prepareData($script['children']).'</ul></li>';
                }else{
                    $return .= '<li class="opcache-file"><span>📄 '.$script['name'].'</span><span class="invalidate" data-script="'.$script['fullPath'].'">Invalider</span></li>';
                }
            }
            return $return;
        }


        private function order(array &$pFiles):void
        {
            $cmp = function($pA, $pB){
                if($pA["type"]===$pB["type"]){
                    return strcmp($pA['name'], $pB['name']);
                }
                if($pA["type"] == "dir"){
                    return -1;
                }
                return 1;
            };
            uasort($pFiles, $cmp);
            foreach($pFiles as &$data){
                if($data["type"] == "dir" && !empty($data['children'])){
                    $this->order($data['children']);
                }
            }
        }
    }
}