<?php
namespace core\tools\debugger
{

    use core\utils\CLI;
    use ReflectionClass;

    class TestCase
    {
        private string|null $currentTest = null;
        private array $tests = [];
        private array $assertions = [];
        private array $failed = [];

        protected function assert(mixed $pTest, mixed $pValue, string $pMessage):void
        {
            $this->assertions[] = [
                "assert", $pTest, $pValue, $pMessage
            ];
            if($pTest != $pValue){
                $this->failedAssertion("assert", $pMessage." - incorrect value", ["Expecting *".$pValue."*, got *".$pTest."*"]);
            }
        }

        protected function assertInArray(array $pArray, array $pValues, string $pMessage):void
        {
            $this->assertions[] = [
                "assertInArray", $pArray, $pValues, $pMessage
            ];
            $errors = [];
            for($i = 0; $i<count($pValues); $i++){
                if(!in_array($pValues[$i], $pArray))
                {
                    $errors[] = "*".$pValues[$i]."* not in (".implode(", ", $pArray).")";
                }
            }
            if(!empty($errors)){
                $this->failedAssertion("assertInArray", $pMessage, $errors);
            }
        }

        protected function assertCount(mixed $pArray, int $pCount):void
        {
            $this->assertions[]=[
                "assertCount", $pArray, $pCount
            ];
            if(!is_array($pArray)){
                $this->failedAssertion("assertCount", "Value passed is not an array");
                return;
            }
            if(count($pArray) != $pCount){
                $this->failedAssertion("assertCount", "incorrect array items count", ["expecting ".$pCount." got ".count($pArray)]);
            }
        }

        private function failedAssertion(string $pMethodName, string $pMessage, array $pLines = []):void
        {
            $this->failed[] = $pMethodName.": ".$pMessage;
            trigger_error("*".$this->currentTest."* : ".$pMessage.(!empty($pLines)?(",\n".implode("\n", $pLines)):""), E_USER_WARNING);
        }

        public function run(array|null $pMethods = null):void
        {
            $reflection = new ReflectionClass($this);

            $methods = $reflection->getMethods();

            track($reflection->name);
            for($i = 0; $i<count($methods); $i++){
                $method = $methods[$i];
                $methodName = $method->name;
                if(!str_starts_with($methodName, "test") || (!is_null($pMethods) && !in_array($methodName, $pMethods))){
                    continue;
                }
                $this->tests[] = $methodName;
                $this->currentTest = $methodName;
                track($reflection->name."->".$methodName);
                try{
                    $this->$methodName();
                }
                catch(\Throwable $e){
                    $error = "An error occured in test \"".$reflection->name."->".$methodName."\"\n".$e->getFile().":".$e->getLine()."\n".$e->getMessage();
                    $this->failed[] = $error;
                    trigger_error($error, E_USER_WARNING);
                }
                track($reflection->name."->".$methodName);
            }
            track($reflection->name);

            list($totalTest, $totalAssertions, $totalFailed, $percentSuccess) = $this->getResults();

            $message = <<<MESS
$reflection->name's results
Tests: *$totalTest*
Assertions: *$totalAssertions*
Failed: *$totalFailed*

*$percentSuccess % successful*
MESS;
            trace($message);
        }

        public function getResults():array
        {
            $totalTest = count($this->tests);
            $totalAssertions = count($this->assertions);
            $totalFailed = count($this->failed);

            $percentSuccess = $totalAssertions>0?round((($totalAssertions - $totalFailed) / $totalAssertions)*100):0;
            return [$totalTest, $totalAssertions, $totalFailed, $percentSuccess];
        }
    }
}