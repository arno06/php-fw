<?php
namespace core\tools
{
    use CurlHandle;
    use Exception;

    /**
     * Class Request - permet de gérer une surcouche nécessaire &agrave; CURL pour se simplifier les traitements
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.3
     * @package core\tools
     */
    class Request
    {
        private CurlHandle $curlResource;

        private string $url;

        private int $responseCode;

        private string $responseContentType;

        private string $redirectURL;

        private int $keepResponseHeaders;

        private string|null $responseHeaders = null;


        public function __construct(string $pUrl, int $pKeepResponseHeaders = 0)
        {
            $this->initResource();
            $this->setUrl($pUrl);
            $this->setOption(CURLOPT_HEADER, $pKeepResponseHeaders);
            $this->keepResponseHeaders = $pKeepResponseHeaders;
        }


        public function initResource():void
        {
            $this->curlResource = curl_init();
        }

        /**
         * Méthode de définition de l'url cible de la requête
         * @param  string   $pUrl
         * @return void
         */
        public function setUrl(string $pUrl):void
        {
            $this->url = $pUrl;
            curl_setopt($this->curlResource, CURLOPT_URL, $pUrl);
        }

        /**
         * Définit les données à envoyer en POST
         * @param mixed $pData
         * @return void
         */
        public function setDataPost(mixed $pData):void
        {
            $this->setOption(CURLOPT_POST, true);
            $this->setOption(CURLOPT_POSTFIELDS, $pData);
        }

        /**
         * Récupère la CURL Resource définie pour la requête en cours
         * @return CurlHandle
         */
        public function getResource():CurlHandle
        {
            return $this->curlResource;
        }

        /**
         * Méthode de définition d'une option liée &agrave; la requête en cours
         * @param int $pCode
         * @param mixed $pValue
         * @return void
         */
        public function setOption(int $pCode, mixed $pValue):void
        {
            curl_setopt($this->curlResource, $pCode, $pValue);
        }

        /**
         * Méthode d'éxecution de la requête - renvoi le résultat du traitement
         * @return string
         * @throws Exception
         */
        public function execute():string
        {
            ob_start();
            $return = curl_exec($this->curlResource);
            $datas = ob_get_contents();
            ob_end_clean();
            if($this->keepResponseHeaders === 1){
                $header_size = curl_getinfo($this->curlResource, CURLINFO_HEADER_SIZE);
                $this->responseHeaders = substr($datas, 0, $header_size);
                $datas = substr($datas, $header_size);
            }
            $this->responseCode = curl_getinfo($this->curlResource, CURLINFO_HTTP_CODE);
            $content_type = curl_getinfo($this->curlResource, CURLINFO_CONTENT_TYPE);
            if (!empty($content_type)) {
                if (is_numeric(strpos($content_type, ';'))) {
                    $split = explode(';', $content_type);
                    $this->responseContentType = $split[0];
                } else {
                    $this->responseContentType = $content_type;
                }
            }

            if(str_starts_with($this->responseCode, "3"))
                $this->redirectURL = curl_getinfo($this->curlResource, CURLINFO_REDIRECT_URL);
            curl_close($this->curlResource);
            if(!$return)
                throw new Exception("Impossible d'accéder &agrave; l'url : <b>".$this->url."</b>");
            return $datas;
        }

        /**
         * Code HTTP de la réponse
         * @return int
         */
        public function getResponseHTTPCode():int
        {
            return $this->responseCode;
        }

        /**
         * Content-type de la réponse
         * @return int
         */
        public function getResponseContentType():int
        {
            return $this->responseContentType;
        }

        /**
         * URL de redirection
         * @return string
         */
        public function getRedirectURL():string
        {
            return $this->redirectURL;
        }


        public function getResponseHeaders():string
        {
            return $this->responseHeaders;
        }


        public function setMethod(string $pValue):void
        {
            curl_setopt($this->curlResource, CURLOPT_CUSTOMREQUEST, $pValue);
        }

        /**
         * Exécute une requête HTTP via CURL
         * Renvoie le résultat
         * @throws Exception
         * @param  string   $pUrl
         * @return string
         */
        static public function load(string $pUrl):string
        {
            $r = new Request($pUrl);
            return $r->execute();
        }

        /**
         * Exécute un ensemble de requête GET via les méthodes curl_multi_*
         * @param string[] $pUrlArr
         * @return array
         */
        static public function multiLoad(array $pUrlArr):array
        {
            $requests = [];
            foreach($pUrlArr as $url)
            {
                $r = new Request($url);
                $r->setOption(CURLOPT_RETURNTRANSFER, 1);
                $requests[] = $r;
            }

            $mh = curl_multi_init();

            foreach($requests as $r)
                curl_multi_add_handle($mh, $r->getResource());

            $active = 0;
            //execute the handles
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            while ($active && $mrc == CURLM_OK) {
                if (curl_multi_select($mh) != -1) {
                    do {
                        $mrc = curl_multi_exec($mh, $active);
                    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
                }
            }

            $return = array();
            foreach($requests as $r)
                $return[] = curl_multi_getcontent($r->getResource());

            foreach($requests as $r)
                curl_multi_remove_handle($mh, $r->getResource());

            curl_multi_close($mh);

            return $return;
        }
    }
}
