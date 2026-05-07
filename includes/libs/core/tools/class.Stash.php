<?php
namespace core\tools
{
    use core\application\Application;
    use core\data\Encoding;
    use core\data\SimpleJSON;
    use core\system\File;
    use core\system\Folder;
    use Exception;

    /**
     * Class Stash
     * @package core\tools
     */
    class Stash
    {
        private string $cache_folder;

        private bool $cache_enabled = true;

        protected int $cache_duration = 60;//minutes


        public function __construct(string $pCacheFolder)
        {
            $this->cache_folder = Application::getInstance()->getFilesPath().'/_cache/'.Application::getInstance()->getModule()->name.'/'.$pCacheFolder.'/';
        }


        protected function storeInCache(mixed $pData, string $pFileName):bool
        {
            if(!$this->cache_enabled||!$pData)
                return false;
            $pFileName = $this->cache_folder.$pFileName.".json";
            Folder::create($this->cache_folder);
            File::delete($pFileName);
            $pData = SimpleJSON::encode($pData);
            File::create($pFileName);
            File::append($pFileName, $pData);
            return true;
        }


        protected function pullFromCache(string $pFileName):mixed
        {
            if(!$this->cache_enabled)
                return false;

            $pFileName = $this->cache_folder.$pFileName.".json";

            if(!file_exists($pFileName))
                return false;

            try
            {
                $fmtime = filemtime($pFileName);
                $current = time();
                $diff = ($current - $fmtime) / (60);
                if($diff>$this->cache_duration)
                    return false;
                $jsonParsed = SimpleJSON::import($pFileName);
            }
            catch(Exception)
            {
                return false;
            }
            return Encoding::fromNumericEntities($jsonParsed);
        }

        /**
         * Méthode d'activation du cache
         */
        public function activateCache():void
        {
            $this->cache_enabled = true;
        }

        /**
         * Méthode de désactivation du cache
         */
        public function deactivateCache():void
        {
            $this->cache_enabled = false;
        }
    }
}