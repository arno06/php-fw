<?php
namespace core\tools
{
    use core\application\event\Event;
    use core\application\event\EventDispatcher;
    use core\data\Encoding;
    use core\system\File;
    use Exception;

    /**
     * Class SimpleCrawler
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.0
     * @package core\tools
     * $sc = new SimpleCrawler("http://localhost/test/");
     * $sc->addEventListener(SimpleCrawlerEvent::OUTPUT, 'callableHandler', $instance);
     * $sc->logFile = 'localhost.csv';
     * $sc->fetch();
     */
    class SimpleCrawler extends EventDispatcher
    {
        private array $urlsDone;

        private array $urlsToCrawl;

        public string $logFile;

        public bool $deepRunning;


        public function __construct(string $pBaseUrl)
        {
            $this->deepRunning = true;
            $this->urlsToCrawl = array($pBaseUrl);
        }


        public function fetch():void
        {
            $this->urlsDone = array();
            if($this->logFile)
            {
                File::delete($this->logFile);
                File::create($this->logFile);
                File::append($this->logFile, Encoding::BOM());
            }
            $this->log("url", "title", "description", "date");

            while($this->next()){}
        }


        private function log(string $pUrl, string $pTitle, string $pDescription, string $pFirst = null):void
        {
            if(!$this->logFile)
                return;
            if(!$pFirst)
                $pFirst = gmdate("D, d M Y H:i:s", time());
            $pTitle = str_replace('"', '""', $pTitle);
            $pTitle = Encoding::fromHTMLEntities($pTitle);
            $pDescription = str_replace('"', '""', $pDescription);
            $pDescription = Encoding::fromHTMLEntities($pDescription);
            File::append($this->logFile, '"'.$pFirst.'";"'.$pUrl.'";"'.$pTitle.'";"'.$pDescription.'"'.PHP_EOL);
        }


        private function next():bool
        {
            $message = "".count($this->urlsDone)." done".PHP_EOL;
            $message .= "".count($this->urlsToCrawl)." left".PHP_EOL;

            if(count($this->urlsToCrawl) == 0)
            {
                return false;
            }
            $url = array_shift($this->urlsToCrawl);

            if(in_array($url, $this->urlsDone))
            {
                return true;
            }

            $message .= "Loading : ".$url.PHP_EOL;

            $this->dispatchEvent(new SimpleCrawlerEvent(SimpleCrawlerEvent::OUTPUT, $message));

            $r = new Request($url);

            try
            {
                $d = $r->execute();
            }
            catch (Exception)
            {
                $d = false;
            }

            if(!$d)
            {
                $this->urlsDone[] = $url;
                $redirect = $r->getRedirectURL();
                if($redirect)
                {
                    $this->urlsToCrawl[] = $redirect;
                }
                else
                {
                    $this->log($url, $r->getResponseHTTPCode(), $r->getRedirectURL());
                }
                return true;
            }

            $baseHref = $this->extract('/<base href="([^"]+)"/', $d);
            $title = $this->extract('/<title>([^<]+)/', $d);
            $description = $this->extract('/<meta name="description" content="([^"]+)"/', $d);

            preg_match_all('/href="([^"]+)"/', $d, $matches);

            if(!empty($matches[1]))
            {
                foreach($matches[1] as $u)
                {
                    if(str_starts_with($u, 'http://')
                        || str_starts_with($u, 'https://')
                        || str_starts_with($u, 'javascript:')
                        || str_starts_with($u, '#')
                        || $u === "/")
                        continue;

                    if(str_starts_with($u, "/"))
                        $u = substr($u, 1, strlen($u));

                    $u = $baseHref.$u;

                    if($this->deepRunning && !in_array($u, $this->urlsToCrawl) && !in_array($u, $this->urlsDone))
                    {
                        $this->urlsToCrawl[] = $u;
                    }

                }
            }

            $this->urlsDone[] = $url;

            $this->log($url, $title, $description);

            return true;
        }


        private function extract(string $pRegExp, string $pContent):bool|string
        {
            if(preg_match($pRegExp, $pContent, $matches))
            {
                if(!isset($matches[1]))
                    return false;
                return $matches[1];
            }
            return false;
        }

    }


    class SimpleCrawlerEvent extends Event
    {
        const OUTPUT = "evt_output";

        public string $message;


        public function __construct(string $pType, string $pMessage)
        {
            $this->message = $pMessage;
            parent::__construct($pType);
        }


        public function __clone()
        {
            return new SimpleCrawlerEvent($this->type, $this->message);
        }
    }
}
