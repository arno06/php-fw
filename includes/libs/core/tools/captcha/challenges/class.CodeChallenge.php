<?php
namespace core\tools\captcha\challenges
{

    use core\application\Dictionary;
    use core\system\Image;
    use core\tools\captcha\Challenge;
    use core\tools\captcha\InterfaceChallenge;
    use core\utils\SimpleRandom;

    class CodeChallenge extends Challenge implements InterfaceChallenge
    {
        const DEFAULT_FONT = "includes/libs/core/tools/captcha/assets/font.LinLibertine.ttf";

        public int $width = 100;

        public int $height = 30;

        public string $backgroundColor = "#ffffff";

        public bool $transparent = false;

        public int $rotation = 15;

        public array $fontSizeRange = [13];

        public array $fontColors = [];

        public int $valueMax = 20;

        public string $type;

        public int $length = 5;

        protected string $value;

        protected string $displayedValue;


        protected function generateSetup():void
        {
            switch($this->type){
                case "calculus":
                    $this->value = rand(0, $this->valueMax);
                    $operations = array(
                        "addition",
                        "substraction"
                    );

                    $operator = $operations[rand(0, count($operations)-1)];

                    switch($operator){
                        case "substraction":
                            $y = rand($this->value+1, $this->value*2);
                            $z = $this->value + $y;
                            $this->displayedValue = $z."-".$y;
                            break;
                        case "addition":
                            $y = rand(1, $this->value-1);
                            $z = $this->value - $y;
                            $this->displayedValue = $y."+".$z;
                            break;
                    }
                    break;
                default:
                case "random":
                    $this->value = SimpleRandom::string($this->length);
                    $this->displayedValue = $this->value;
                    break;
            }
        }


        public function get(): array
        {
            if(empty($this->fontColors))
                $this->fontColors[] = "#000000";
            $fontSizeMin = 12;
            $fontSizeMax = 12;
            if(count($this->fontSizeRange)==2){
                $fontSizeMin = $this->fontSizeRange[0];
                $fontSizeMax = $this->fontSizeRange[1];
            }
            if(count($this->fontSizeRange)==1){
                $fontSizeMin = $this->fontSizeRange[0];
                $fontSizeMax = $this->fontSizeRange[0];
            }
            $distance = $this->width/$this->length;
            $img = new Image($this->width, $this->height, Image::PNG, 1);
            if(!$this->transparent)
                $img->beginFill(hexdec(substr($this->backgroundColor, 1,2)), hexdec(substr($this->backgroundColor, 3,2)), hexdec(substr($this->backgroundColor, 5,2)));
            $img->drawRectangle(0, 0, $this->width, $this->height);
            $img->endFill();
            for($i = 0, $max = strlen($this->displayedValue); $i<$max;$i++)
            {
                $c = $this->fontColors[rand(0, count($this->fontColors)-1)];
                $f = self::DEFAULT_FONT;
                $s = rand($fontSizeMin, $fontSizeMax);
                $img->drawText(substr($this->displayedValue, $i, 1), $s, $f, intval(($distance/4) + $i*$distance), intval($s + (($this->height-$s)/2)), hexdec(substr($c, 1,2)),hexdec(substr($c, 3,2)),hexdec(substr($c, 5,2)), rand(-$this->rotation,$this->rotation));
            }

            return [
                "setup"=>["value"=>$this->value, "displayedValue"=>$this->displayedValue],
                "question"=>Dictionary::term("captcha.code.".$this->type).'<img src="'.$img->toDataUrl().'" alt="code"/>',
                "response"=>"<div class='webc-captcha-code'><input type='text' autocomplete='off' class='webc-captcha-input'><input type='button' class='webc-captcha-button' value='".Dictionary::term('captcha.code.check')."'></div>"
            ];
        }


        public function submit(string $pValue): bool
        {
            return $this->value == $pValue;
        }
    }
}