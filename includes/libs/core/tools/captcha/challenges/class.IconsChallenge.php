<?php
namespace core\tools\captcha\challenges
{

    use core\application\Dictionary;
    use core\system\Folder;
    use core\system\Image;
    use core\tools\captcha\Challenge;
    use core\tools\captcha\InterfaceChallenge;
    use Exception;

    class IconsChallenge extends Challenge implements InterfaceChallenge
    {
        protected string $icons_path = 'includes/libs/core/tools/captcha/assets/icons/';

        protected int $icon_size = 32;

        protected array $icons = [];

        protected array $correct_answers = [];


        protected function generateSetup():void
        {
            $different_icons_occurences = rand(2,3);

            $total_icons = rand($different_icons_occurences * 2, $different_icons_occurences * 2 + 1);

            $least_icon_occurences = round(($total_icons/$different_icons_occurences)-1);

            $icons = Folder::read($this->icons_path);
            $icons = array_keys($icons);
            shuffle($icons);

            $icons = array_splice($icons, 0, $different_icons_occurences);

            $least_icon = array_shift($icons);

            $this->icons = [];
            $this->appendIcon($this->icons, $least_icon, $least_icon_occurences);

            $total = $total_icons - $least_icon_occurences;

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


        private function appendIcon(array &$pIcons, string $pIcon, int $pCount):void
        {
            for($i = 0; $i<$pCount; $i++){
                $pIcons[] = $pIcon;
            }
        }

        /**
         * @return array
         * @throws Exception
         */
        public function get():array
        {

            if(!file_exists($this->icons_path.$this->icons[0])){
                $this->generateSetup();
            }

            $choices = "<div class='webc-captcha-options'>";

            foreach($this->icons as $i=>$icon){
                $img = new Image($this->icon_size, $this->icon_size, Image::PNG);
                $img->drawImage($this->icons_path.$icon, $this->icon_size, $this->icon_size);
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


        public function submit($pValue):bool
        {
            if(!$this->correct_answers){
                return false;
            }
            return in_array($pValue, $this->correct_answers);
        }

    }
}