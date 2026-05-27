<?php
namespace core\utils
{

    use core\application\Core;
    use JetBrains\PhpStorm\NoReturn;

    abstract class CLI
    {
        const RED = '0;31';

        const GREEN = '0;32';

        const YELLOW = '0;33';

        const LIGHT_YELLOW = '0;93';

        const BLUE = '0;34';

        const WHITE = '0;97';

        const RESET = '39';

        const RESET_ALL = "\e[0m";

        const BOLD = "\e[1m";


        static public function delay(int $pSeconds):void
        {
            for($i = $pSeconds; $i>0; $i--){
                self::resetLine()->out("Starting in ".(CLIUtils::formatTime($i)));
                sleep(1);
            }
            self::resetLine();
        }


        static public function progressBar():CLIProgressBar
        {
            return new CLIProgressBar();
        }

        static public function enrichedOutput(string $pString):void
        {
            if(empty($pString)){
                return;
            }
            $pString = strip_tags($pString);
            $lines = explode("\n", $pString);
            foreach($lines as $line){
                $line = preg_replace('/\*([^*]+)\*/', CLI::BOLD."$1".CLI::RESET_ALL, $line);
                CLI::newLine()->out($line)
                    ->resetTextColor()
                    ->endOfLine();
            }
        }

        static public function newLine():CLILine
        {
            return new CLILine();
        }


        static public function resetLine():CLILine
        {
            return new CLILine("\033[2K\r");
        }

        #[NoReturn]
        static public function exit(int $pExitCode = 0):void
        {
            echo "\r\n";
            Core::endApplication($pExitCode);
        }


        static public function isCurrentContext():bool
        {
            return PHP_SAPI == "cli";
        }
    }

    class CLILine
    {
        public function __construct(string $pInit = ""){
            echo $pInit;
        }


        public function setTextColor(string $pColor):CLILine
        {
            echo "\e[".$pColor."m";
            return $this;
        }

        public function setBold():CLILine
        {
            echo CLI::BOLD;
            return $this;
        }

        public function resetAll():CLILine
        {
            echo CLI::RESET_ALL;
            return $this;
        }

        public function resetTextColor():CLILine
        {
            return $this->setTextColor(CLI::RESET);
        }


        public function out(string $pString):CLILine
        {
            echo $pString;
            return $this;
        }


        public function endOfLine():void
        {
            echo "\r\n";
        }
    }

    class CLIProgressBar
    {
        private int $steps;


        public function __construct(int $pSteps = 20)
        {
            $this->steps = $pSteps;
        }


        public function update(float $pProgress, string $pMessageBefore = "", string $pMessageAfter = ""):void
        {
            $out = "[";
            for($i = 0; $i<$this->steps; $i++){
                $percent = round($i/$this->steps * 100);
                $out .= ($percent<=$pProgress)?"*":"_";
            }
            $out .= "]";
            CLI::resetLine()->out($pMessageBefore)->out(" ".$out." ")->out($pMessageAfter);
        }
    }

    abstract class CLIUtils
    {
        static public function formatTime(int $pSec):string
        {
            $remaining = $pSec;
            $precision = "";
            $unit = "s";
            $units = [
                ["remaining"=>60, "unit"=>"min"],
                ["remaining"=>60, "unit"=>"h"],
                ["remaining"=>24, "unit"=>"j"]
            ];
            foreach($units as $u){
                if($remaining > $u["remaining"]){
                    $val = $remaining;
                    $remaining = floor($remaining / ($u["remaining"]));
                    $precision = $val - ($remaining * $u["remaining"]);
                    $unit = $u["unit"];
                }else{
                    break;
                }
            }
            return $remaining.$unit.$precision;
        }
    }
}