<?php
namespace core\data
{

    use core\system\File;
    use JetBrains\PhpStorm\NoReturn;
    use ZipArchive;

	/**
	 * Class de gestion des fichiers CSV
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .3
	 * @package data
	 */
	abstract class SimpleExcel implements InterfaceData
	{
		/**
		 * Méthode d'encodage d'un tableau en données formatées vers le format spécifique
		 * @param array $pArray		Tableau des données
		 * @return string
		 */
        #[NoReturn]
		static function encode(array $pArray):string
		{
			trigger_error("Not implemented yet.", E_USER_ERROR);
		}

		/**
		 * Méthode de récupération d'un tableau associatif multidimensionnel &agrave; partir d'une chaine de caract&egrave;res
		 * @param String $pString		Contenu au format spécifique
		 * @return array
		 */
        #[NoReturn]
		static function decode(string $pString):array
		{
			trigger_error("Not implemented yet.", E_USER_ERROR);
		}

		/**
		 * Méthode de chargement de décodage d'un fichier au format spécifique
		 * @param string $pFile
		 * @return array|null
		 */
		static function import(string $pFile):array|null
		{
            return match (strtolower(File::getExtension($pFile))) {
                "xlsx" => self::importXlsx($pFile),
                default => null,
            };
        }


		static private function importXlsx($pFile):array|bool
		{
			$xlsxHandler = new XLSXHandler($pFile);
			$data = $xlsxHandler->read();
			if(!count($data))
				return false;
			$return = array();
			for($i = 1, $max = count($data), $maxj = count($data[0]); $i<$max; $i++)
			{
				$cols = array();
				for($j = 0; $j<$maxj; $j++)
					$cols[$data[0][$j]] = $data[$i][$j]??"";
				$return[] = $cols;
			}
			return $return;
		}
	}

	class XLSXHandler
	{
		const XML_CELLS     = "xl/worksheets/sheet1.xml";

		const XML_STRINGS   = "xl/sharedStrings.xml";

		private string $path;

		private ZipArchive $zipHandler;


		public function __construct($pFile)
		{
			$this->path = $pFile;
			$this->zipHandler = new ZipArchive();
		}


		public function save():void
		{

		}


		public function read():bool|array
		{
			if($this->zipHandler->open($this->path)===false)
				return false;
			$data = array();
			$tmp_values = $this->zipHandler->getFromName(self::XML_STRINGS);
			$tmp_values = SimpleXML::decode($tmp_values);
			$string_values = array();
			foreach($tmp_values["sst"]["si"] as $v)
				$string_values[] = $v["t"]["nodeValue"];
			$cells = $this->zipHandler->getFromName(self::XML_CELLS);
			$tmp_data = SimpleXML::decode($cells);
			$range_letters = range("a", "z");
			$columns = 0;
			foreach($tmp_data["worksheet"]["sheetData"]["row"] as &$r)
			{
				$cols = array();
				for($i = 0; $i<$columns;$i++)
					$cols[] = "";
				foreach($r["c"] as &$c)
				{
					if(empty($data))
						$columns++;
					if(!isset($c["v"]) || !is_array($c["v"]))
						continue;
                    $v = $c["v"]["nodeValue"];
					if(empty($c["v"]["nodeValue"]))
						$c["v"]["nodeValue"] = 0;
					if(isset($c["t"]) && $c["t"]=="s" && isset($string_values[$c["v"]["nodeValue"]]))
					{
						$v = $string_values[$c["v"]["nodeValue"]];
					}
					preg_match("/^([a-z]+)([0-9]+)/i", $c["r"], $matches);
					$l = strtolower($matches[1]);
					$cols[array_search($l, $range_letters)] = $v;
				}
				$data[] = $cols;
			}
			$this->zipHandler->close();
			return $data;
		}

		public function delete()
		{

		}
	}
}
