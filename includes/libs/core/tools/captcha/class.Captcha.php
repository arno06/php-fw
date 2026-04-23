<?php
namespace core\tools\captcha
{
    use core\data\SimpleJSON;
    use core\db\Query;
    use core\utils\SimpleRandom;

    class Captcha
    {
        const CHECK_LIFETIME = 7 * 24 * 60 * 60;
        const MAX_ATTEMPT = 3;
        const MAX_ATTEMPT_LIFTIME = 2 * 60 * 60;

        const TABLE = 'captcha_challenges';

        private $token;

        private $types = null;

        private $cleanToken = false;

        /**
         * @var array
         */
        private $result = null;

        /**
         * @var array
         */
        private $challenge = null;

        private $instance = null;

        public $verified = false;

        public function __construct($pToken = null, $pTypes = ["icons"])
        {
            $this->token = $pToken;
            $this->types = $pTypes;

            $this->init();
        }

        private function init(){
            $setup = null;
            $this->challenge = null;
            if(!empty($this->token)){
                $this->challenge = Query::select('*', self::TABLE)->where('token_cc', Query::EQUAL, $this->token)->limit(0, 1)->execute();

                if(!empty($this->challenge)){
                    $this->challenge = $this->challenge[0];

                    if(!empty($this->challenge["validation_date_cc"])){
                        $validation = strtotime($this->challenge["validation_date_cc"]) + self::CHECK_LIFETIME;
                        if($validation < time()){
                            $this->challenge = null;
                            $this->clearToken();
                        }else{
                            $this->verified = true;
                            $this->result = [
                                "verified"=>true
                            ];
                            return;
                        }
                    }else{
                        unset($this->challenge["validation_date_cc"]);
                    }
                    if(!empty($this->challenge["attempts_cc"])
                        && $this->challenge["attempts_cc"] >= self::MAX_ATTEMPT){
                        $free = strtotime($this->challenge["creation_date_cc"]) + self::MAX_ATTEMPT_LIFTIME;
                        if(time() < $free){
                            $this->result = [
                                "too_many_attempts"=>true,
                                "waiting_time"=>$free - time()//seconds
                            ];
                            return;
                        }
                        $this->challenge = null;
                        $this->clearToken();
                    }else{
                        if(empty($this->challenge["attempts_cc"])){
                            unset($this->challenge["attempts_cc"]);
                        }
                    }
                }
            }
            if(!$this->challenge){
                $this->token = SimpleRandom::string(32);
                while(Query::count(self::TABLE, Query::condition()->andWhere('token_cc', Query::EQUAL, $this->token))){
                    $this->token = SimpleRandom::string(32);
                }

                $this->challenge = [
                    "token_cc"=>$this->token,
                    "challenge_cc"=>$this->types[0],
                    "creation_date_cc"=>"NOW()"
                ];
                Query::insert($this->challenge)->into(self::TABLE)->execute();
            }else{
                $setup = SimpleJSON::decode($this->challenge["setup_cc"]);
            }
            $challengeClass = $this->challenge["challenge_cc"];

            $className = 'core\tools\captcha\challenges\\'.ucFirst($challengeClass).'Challenge';

            $this->instance = new $className($setup);

            $this->result = $this->instance->get();

            $this->challenge["setup_cc"] = SimpleJSON::encode($this->result["setup"]);
            $this->saveChallenge();
        }

        public function submit($pValue){
            if(!$this->challenge){
                return false;
            }
            if(isset($this->challenge["attempts_cc"]) && $this->challenge["attempts_cc"]> self::MAX_ATTEMPT){
                return false;
            }
            if(!$this->challenge["attempts_cc"]){
                $this->challenge["attempts_cc"] = 0;
            }
            $this->challenge["attempts_cc"]++;
            if($this->instance && $this->instance->submit($pValue)){
                $this->verified = true;
                $this->result = [
                    "verified"=>true
                ];
                $this->challenge["validation_date_cc"] = "NOW()";
                $this->saveChallenge();
                return true;
            }

            $this->result = [
                "wrong_answer"=>true,
            ];

            if($this->challenge["attempts_cc"] >= self::MAX_ATTEMPT){
                $this->challenge["creation_date_cc"] = date("Y-m-d H:i:s");
                $free = strtotime($this->challenge["creation_date_cc"]) + self::MAX_ATTEMPT_LIFTIME;
                $this->result = [
                    "too_many_attempts"=>true,
                    "waiting_time"=>$free - time()//seconds
                ];
            }
            $this->challenge["validation_date_cc"] = "NULL";
            $this->saveChallenge();
            return false;
        }

        public function get(){
            unset($this->result["setup"]);
            $this->result["token"] = $this->token;
            return $this->result;
        }


        private function clearToken(){
            if(!$this->cleanToken){
                return;
            }
            Query::delete()->from(self::TABLE)->where('token_cc', Query::EQUAL, $this->token)->execute();
            $this->token = null;
        }

        private function saveChallenge(){
            Query::update(self::TABLE)->values($this->challenge)->where("token_cc", Query::EQUAL, $this->token)->execute();
        }
    }
}