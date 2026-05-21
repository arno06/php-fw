<?php
namespace core\data
{
	use core\system\File;
	use Exception;

	/**
	 * Class de gestion des fichiers CSV
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .4
	 * @package data
	 */
	abstract class SimpleCSV implements InterfaceData
	{
		/**
		 * Caractère de séparation des champs
		 * @var String
		 */
		const SEPARATOR = ";";

		/**
		 * Méthode de conversion de données au format Tableau en chaine de caractères formatée en CSV
		 * @param array $pArray Données à convertir
         * @param bool $skipLabels
		 * @return string
		 */
		static public function encode(array $pArray, bool $skipLabels = false):string
		{
			if(!$pArray)
				return "";
			$result = '';
			$libelles = array();
			$donnees = "";

			// if a unique entry is sent, put it in an envelope
			if (!isset($pArray[0])) {
                $pArray = array($pArray);
			}

			for($i = 0, $max = count($pArray); $i<$max;$i++)
			{
				foreach($pArray[$i] as $champs=>$value)
				{
					if(!in_array($champs, $libelles))
						$libelles[] = $champs;
				}
			}
			$data = array();
			$maxj = count($libelles);
			for($i = 0; $i<$max;$i++)
			{
				$d = array();
				for($j = 0;$j<$maxj;$j++)
				{
					if(isset($pArray[$i][$libelles[$j]]))
					{
						$v = $pArray[$i][$libelles[$j]];
						$v = str_replace('"', '""', $v);
						if(preg_match("/([\r\n".self::SEPARATOR."])/", $v))
							$v = '"'.$v.'"';
						$d[$libelles[$j]] = $v;
					}
					else
						$d[$libelles[$j]] = "";
				}
				$data[] = $d;
			}
			for($i=0, $max = count($data); $i<$max; $i++)
			{
				$ligne = "";
				$ct = 0;
				foreach($data[$i] as $value)
					$ligne .= $ct++?self::SEPARATOR.$value:$value;
				$donnees .= $ligne."\r\n";
			}

			if (!$skipLabels) {
				$result .= implode(self::SEPARATOR,$libelles)."\r\n";
			}

			$result .= $donnees;

			return $result;
		}

		/**
		 * Méthode de conversion d'une chaine de caractères formatée en CSV vers un Tableau
		 * @param string $pString Chaine à convertir
		 * @return array
		 */
		static public function decode(string $pString):array
		{
			$return = array();
			$dataArray = explode(PHP_EOL,$pString);
            $fields = explode(self::SEPARATOR, $dataArray[0]);
            foreach($fields as &$field){
                $field = preg_replace('/([\r\n])$/', '', $field);
            }
            $maxFields = count($fields);
			$max = count($dataArray);
			for($i = 1; $i < $max; $i++)
			{
				if($dataArray[$i]=="")
					continue;
				$temp = explode(self::SEPARATOR, $dataArray[$i]);
				$new = array();
				for($j = 0; $j<$maxFields; $j++)
				{
					$v = $temp[$j]??"";
					$v = preg_replace("/^\"/", "", $v);
					$v = preg_replace("/\"$/", "", $v);
					$new[$fields[$j]] = $v;
				}
				$return[] = $new;
			}
			return $return;
		}

		/**
		 * Méthode d'exportation de données provenant de la base vers un fichier CSV
		 * Renvoie le résultat de l'écriture du fichier
		 * @param array $pData					Tableau des données
		 * @param string $pFileName				Nom du fichier
		 * @return mixed
		 */
		static public function export(array $pData, string $pFileName):mixed
		{
			if(!$pData)
				return false;
			$donnees = self::encode($pData);
			File::delete($pFileName);
			File::create($pFileName);
			return File::append($pFileName, $donnees);
		}

		/**
		 * Méthode d'import de données à partir d'un fichier CSV
		 * @param string $pFile				Nom du fichier
		 * @return array|null
		 */
		static public function import(string $pFile):array|null
		{
			try
			{
				$dataString = File::read($pFile);
			}
			catch (Exception)
			{
				return null;
			}
			return self::decode($dataString);
		}
	}
}
