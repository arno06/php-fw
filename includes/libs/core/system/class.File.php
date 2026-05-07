<?php
namespace core\system
{
	use Exception;
    use JetBrains\PhpStorm\NoReturn;

    /**
	 * Class File
	 * Surcouche aux fonctions Php permettant de gérer les fichiers
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .4
	 * @package system
	 */
	abstract class File
	{
		const REGEXP_EXTENSION = '/\.([a-z0-9]{2,4})$/i';

		/**
		 * Méthode de création d'un nouveau fichier sur le serveur
		 * Renvoi le résultat du traitement - False si le fichier existe déj&agrave;
		 * @param string $pFile					chemin du fichier
		 * @return bool
		 */
		static public function create(string $pFile):bool
		{
			if(file_exists($pFile))
				return false;
			else
				return fclose(fopen($pFile,'x'));
		}

		/**
		 * Méthode de récupération d'une ressource apr&egrave;s ouverture d'un fichier non binaire
		 * @param string $pFile					Chemin du fichier
		 * @param string $pMode					Mode d'ouverture, par défault "r" pour "read" lecture
		 * @return mixed
		 */
		static protected function open(string $pFile, string $pMode = "r"):mixed
		{
			if(file_exists($pFile))
				return fopen($pFile, $pMode);
			else
				return false;
		}

		/**
		 * Méthode de récupération du contenu d'un fichier non binaire
		 * Déclenche une Exception en cas d'échec
		 * @param String $pPath					Chemin du fichier
		 * @return false|string
		 * @throws Exception
		 */
		static public function read(string $pPath):false|string
		{
			if($ressource = self::open($pPath))
			{
				$r = fread($ressource, filesize($pPath));
				fclose($ressource);
				return $r;
			}
			else {
                $split = explode("/", $pPath);
                $file = array_pop($split);
                throw new Exception("Le fichier '".$file."' n'existe pas.");
            }
		}

        /**
         * @param string $pFileName
         * @param Callable $pCallBack
         * @return void
         * @throws Exception
         */
        static public function readLines(string $pFileName, Callable $pCallBack):void
        {
            if(!($resource = self::open($pFileName))){
                throw new Exception("Le fichier '".$pFileName."' n'existe pas.");
            }
            $idx = 0;
            while(!feof($resource)){
                $pCallBack(fgets($resource), $idx++);
            }
            fclose($resource);
        }

		/**
		 * Méthode d'écriture &agrave; la suite d'un fichier existant
		 * @param string $pFile					Chemin du fichier
		 * @param string $pValue				Valeur &agrave; écrire
		 * @return bool|int
		 */
		static public function append(string $pFile, string $pValue):bool|int
		{
			$r = self::open($pFile, "a");
			$return = @fwrite($r, $pValue);
			@fclose($r);
			return $return;
		}

		/**
		 * Méthode de suppression d'un fichier
		 * Renvoi le résultat de l'action, false si le fichier n'existe pas
		 * @param string $pFile					Chemin du fichier
		 * @return bool
		 */
		static public function delete(string $pFile):bool
		{
			if(file_exists($pFile))
			{
				chmod($pFile, 0777);
				return unlink($pFile);
			}
			else
				return true;
		}

		/**
		 * Méthode de renommage d'un fichier/dossier
		 * Renvoi le résultat de l'action, false si le fichier n'existe pas
		 * @param string $pFile					Chemin actuel
		 * @param string $pNewName				Nouveau Chemin
		 * @return bool
		 */
		static public function rename(string $pFile, string $pNewName):bool
		{
			if(file_exists($pFile))
				return @rename($pFile, $pNewName);
			else
				return false;
		}

		/**
		 * Méthode d'échappement des caract&egrave;res pouvant poser probl&egrave;me dans certains syst&egrave;mes de fichiers
		 * @param string $pFileName				Nom du fichier
		 * @return string
		 */
		static public function sanitizeFileName(string $pFileName):string
		{
			$pFileName = strtolower($pFileName);
			$chars = array(" "=>"-",
				"@"=>"at",
				"\\"=>"-",
				"/"=>"-",
				"â"=>"a",
				"à"=>"a",
				"ä"=>"a",
				"é"=>"e",
				"è"=>"e",
				"ê"=>"e",
				"ë"=>"e",
				"ï"=>"i",
				"ì"=>"i",
				"ù"=>"u",
				"ü"=>"u",
				"ô"=>"o",
				"ò"=>"o",
				"ö"=>"o",
				"ÿ"=>"y");
			foreach($chars as $key=>$change)
				$pFileName = str_replace($key, $change, $pFileName);
			return $pFileName;
		}

		/**
		 * Méthode de récupération d'une extension d'un fichier &agrave; partir du nom de ce même fichier
		 * @param string $pFile		Nom du fichier - peut être le chemin relatif ou absolu de celui-ci
		 * @return string(2,3)
		 */
		static public function getExtension(string $pFile):string
		{
			preg_match(self::REGEXP_EXTENSION, $pFile, $extracts);
			return $extracts[1];
		}


		static public function isImage(string $pFile):bool
		{
			$extension = self::getExtension($pFile);
			return in_array($extension, array('gif', 'jpg', 'jpeg', 'png', 'tif'));
		}

		/**
		 * Méthode de récupération du MimType d'un fichier &agrave; partir de son nom
		 * @param string $pFile		Nom du fichier - peut être le chemin relatif ou absolu de celui-ci
		 * @return string
		 */
		static public function getMimeType(string $pFile):string
		{
			$extension = self::getExtension($pFile);
            return match ($extension) {
                "tgz", "gz" => "application/x-gzip",
                "zip" => "application/zip",
                "rar" => "application/rar",
                "pdf" => "application/pdf",
                "png" => "image/png",
                "gif" => "image/gif",
                "jpg" => "image/jpeg",
                "txt" => "text/plain",
                "csv" => "text/csv",
                default => "application/octet-stream",
            };
		}

		/**
		 * Méthode permettant de forcer le téléchargement d'un fichier ou un contenu via un fichier temporaire
		 * Quitte l'applicatif - aucune sortie HTML générée
		 * @param string 	$pFile		emplacement du fichier &agrave; télécharger
		 * @param string	$pSource	contenu du fichier - peut être du contenu JSON, CSV, XML...
		 * @return void
		 */
        #[NoReturn]
		static public function download(string $pFile, string $pSource = ""):void
		{
			if(empty($pFile))
				return;
			$fromSource = !empty($pSource);
			if(!$fromSource)
				$length = filesize($pFile);
			else
				$length = strlen($pSource);
			header("content-disposition: inline; filename=\"".basename($pFile)."\"");
			header('Content-Type: application/force-download');
			header('Content-Transfer-Encoding: binary');
			header("Content-Length: ".$length);
			header("Pragma: no-cache");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
			header("Expires: 0");
			if(!$fromSource)
				readfile($pFile);
			else
				echo $pSource;
			exit(0);
		}
	}
}
