<?php
namespace app\main\controllers\front
{

    use core\application\Configuration;
    use core\application\Core;
    use core\application\DefaultController;
    use core\application\Go;
    use core\data\Encoding;
    use core\data\SimpleCSV;
    use core\db\DataManager;
    use core\db\Query;
    use core\system\File;
    use core\utils\CLI;
    use core\utils\RestHelper;

    class index extends DefaultController
    {

        public function __construct()
        {

        }

        public function index(){
            $fileName = "";
            File::readLines($fileName, function($pLIne, $pIndex){
                if($pIndex===0||empty($pLine)){
                    return;
                }
                $f = explode(";", $pLIne);
                if($f[5] == "non"){
                    return;
                }
                Query::insert(array(
                    "type_element_ca"=>$f[0],
                    "id_element_ca"=>$f[1],
                    "name_crat_ca"=>$f[6],
                    "url_crat_ca"=>$f[7]
                ))->into("crat_associations")->execute();
            });
        }

        public function crat(){

            if(!CLI::isCurrentContext()){
                Go::to404();
            }

            set_time_limit(0);

            RestHelper::$use_cache = false;
            RestHelper::$debug_track = false;

            $start = microtime(true);

            $companies_xml = RestHelper::request('https://api.vidal.fr/rest/api/companies?q=&page-size=2500');
            $companies = array();
            foreach($companies_xml->entry as $entry){
                $companies[] = strval($entry->title);
            }

            $url_crat = 'https://www.lecrat.fr/articleSearchSaisie.php?recherche=';

            $sources = RestHelper::request('https://data-publisher.vidal.fr/api/private/regroupements', RestHelper::HTTP_GET, array(), RestHelper::FORMAT_JSON);

            foreach($sources as &$s){
                $s["type"] = "product_range";
            }

            $substances = RestHelper::request('https://resources.vidal.fr/substances?token='.Configuration::extra('token').'&states=0,1,2,3', RestHelper::HTTP_GET, array(), RestHelper::FORMAT_JSON);

            foreach($substances["items"] as $substance){
                $sources[] = array(
                    "type"=>"substance",
                    "id"=>$substance["id_substance"],
                    "name"=>Encoding::fromNumericEntities($substance["name_substance"])
                );
            }

            $limit = count($sources);

            $filename = 'crat_'.$limit.'.csv';
            if(file_exists($filename)){
                File::delete($filename);
            }
            File::create($filename);
            File::append($filename, "type;id;name;labo;recherche;crat;name_crat;url".PHP_EOL);

            $progress = CLI::progressBar();
            $progress->update(0, 'starting');
            for($i = 0; $i<$limit; $i++){
                $current = microtime(true);
                $time = "";
                if($i>0){
                    $diff = $current - $start;
                    $avg = $diff / $i;
                    $time = $this->formatTime(round(($avg * ($limit - $i))));
                }
                $entry = $sources[$i];
                $name = $entry['name'];
                $progress->update(round(($i/$limit)*100), "", $time." (".$i."/".$limit.") - ".$name);

                $labo = "";
                $search = $name;
                foreach($companies as $company){
                    $re = str_replace('.', '\.', $company);
                    $re = str_replace('/', '\/', $re);
                    $re = '/'.$re.'(\s(LABO|CONSEIL)*)*/i';
                    if(preg_match($re, $name, $matches_companies)){
                        $labo = $company;
                        $search = preg_replace($re, "", $name);
                        break;
                    }
                }

                $isAcide = strpos(strtolower($search), "acide")===0;
                if($isAcide){
                    $precision = explode(" ", $entry["name"]);
                    $search = "acide ".$precision[1];
                }

                $url = $url_crat.urlencode($search);
                $res = RestHelper::request($url, RestHelper::HTTP_GET, array(), RestHelper::FORMAT_RAW);
                $matches = array();
                $crat = strpos($res, "VOTRE RECHERCHE N’A PU ABOUTIR.")!==false||!preg_match('/Résultat -> <span class="texte_T12B"><font color="#c38c15">([^<]+)<\/font><\/span>/', $res, $matches)?"non":"oui";
                $name_crat = "";
                if($crat==="oui"){
                    $name_crat = $isAcide?$search:($matches[1]??'Erreur RE');
                }
                File::append($filename, $entry["type"].";".$entry["id"].";".$name.";".$labo.";".$search.";".$crat.";".$name_crat.";".$url.PHP_EOL);
                usleep(250);
            }
            $completed = $this->formatTime(microtime(true)-$start);
            $progress->update(100, "", $completed." - done");
            CLI::exit();
        }

        private function formatTime($pSec){
            $remaining = $pSec;
            $unit = "s";
            if($remaining>60){
                $remaining = round($remaining/60);
                $unit = "min";
            }
            if($remaining>60){
                $remaining = round($remaining/60);
                $unit = "h";
            }
            if($remaining>24){
                $remaining = round($remaining/24);
                $unit = "j";
            }
            return $remaining.$unit;
        }

        public function migration_campus(){
            $res = DataManager::upgradeSchema('campus', 'docker');

            trace_r($res);
        }
    }
}
