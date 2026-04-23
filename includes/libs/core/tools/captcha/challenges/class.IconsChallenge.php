<?php
namespace core\tools\captcha\challenges
{

    use core\application\Dictionary;
    use core\system\Folder;
    use core\system\Image;

    class IconsChallenge
    {
        const ICONS_PATH = 'includes/libs/core/tools/captcha/assets/icons/';

        private $icon_size = 32;

        private $different_icons_occurences = null;
        private $total_icons = null;
        private $least_icon_occurences = null;

        private $icons = [];
        private $correct_answers = [];

        public function __construct($pSetup = null){
            if(!$pSetup){
                $this->generateSetup();
            }else{
                $this->icons = $pSetup["icons"];
                $this->correct_answers = $pSetup["correct_answers"];
            }
        }

        private function generateSetup(){
            $this->different_icons_occurences = rand(2,3);

            $this->total_icons = rand($this->different_icons_occurences * 2, $this->different_icons_occurences * 2 + 1);

            $this->least_icon_occurences = round(($this->total_icons/$this->different_icons_occurences)-1);

            $icons = Folder::read(self::ICONS_PATH);
            $icons = array_keys($icons);
            shuffle($icons);

            $icons = array_splice($icons, 0, $this->different_icons_occurences);

            $least_icon = array_shift($icons);

            $this->icons = [];
            $this->appendIcon($this->icons, $least_icon, $this->least_icon_occurences);

            $total = $this->total_icons - $this->least_icon_occurences;

            while(!empty($icons)){
                $ct = floor($total / count($icons));
                $total -= $ct;
                $this->appendIcon($this->icons, array_shift($icons), $ct);
            }

            shuffle($this->icons);
            foreach($this->icons as $key=>$icon){
                if($icon == $least_icon){
                    $this->correct_answers[] = $key;
                }
            }

        }

        private function appendIcon(&$pIcons, $pIcon, $pCount){
            for($i = 0; $i<$pCount; $i++){
                $pIcons[] = $pIcon;
            }
        }

        public function get(){

            $choices = "<div class='webc-captcha-options'>";

            foreach($this->icons as $i=>$icon){
                $img = new Image($this->icon_size, $this->icon_size, Image::PNG);
                $img->drawImage(self::ICONS_PATH.$icon, $this->icon_size, $this->icon_size);
                $img->rotate(rand(0,3) * 90);
                $choices .= "<img src='".$img->toDataUrl()."' alt='Image ".$i."'>";
            }

            $choices .= "</div>";

            return [
                "setup"=>["icons"=>$this->icons, "correct_answers"=>$this->correct_answers],
                "question"=>Dictionary::term('captcha.icons.question'),
                "response"=>$choices
            ];
        }

        public function submit($pValue){
            if(!$this->correct_answers){
                return false;
            }
            return in_array($pValue, $this->correct_answers);
        }

    }
}