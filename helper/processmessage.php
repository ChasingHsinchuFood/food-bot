<?php
    class processMessage {
        public function __construct($message, $sender) {
            $this -> message =  strtolower($message);
            $this -> sender = $sender;
        }

        public function processText() {
            $json = array();
            $json["recipient"]["id"] = $this -> sender;

            $needle = "Hello";

            if(stristr($this -> message, $needle) != false) {
                $json["message"]["text"] = "Hello! May I help you ? \n 嗨，有什麼事可以為你效勞的嘛？";
            }
            else if(mb_strlen($this -> message) <= 5) {
                $json["message"]["text"] = "You have to input more texts so that I can understand what do you mean \n 你必須打更多的字好讓我看的懂！";
            }
            else {
                $messages = explode(",", $this -> message);
                //e.g. 問公車動態,台北,287
                //please tell me the bus status,Taipei,287

                if(count($messages) === 4) {
                    if(mb_stristr($messages[0], "問公車動態") != false || mb_stristr($messages[0], "tell me the bus status") != false) {
                        $busTime = new BusTime($messages);
                        $json["message"]["text"] = $busTime -> getStopTime();
                    }
                    else if(mb_stristr($messages[0], "問公路客運動態") != false || mb_stristr($messages[0], "tell me the bus status") != false) {
                        $busTime = new BusTime($messages);
                        $json["message"]["text"] = $busTime -> getStopTime();
                    }
                    else {
                        $json["message"]["text"] = "公車動態服務無法達成！\nthe dynamic bus service is not successful";
                    }
                }
                else if(count($messages) > 2) {
                    $json["message"]["text"] = "公車動態服務無法達成！\nthe dynamic bus service is not successful\n請注意！一次只能查詢一種路線公車！\n
                        Attention! the dynamic bus is only query one once.";
                }
                else if(mb_stristr($messages, "I have to book my ticket") != false || mb_stristr($messages, "我需要訂票") != false) {
                    $json["message"]["text"] = "Ok! what ticket do you want? \n 好的，請上傳『訂票檔』。";
                }
                else {

                    $json["message"]["text"] = "Sorry, this service is not available! \n 很抱歉，你說的這項服務我無法完成！";
                }
            }

            return $json;
        }

        public function processPostBack() {

        }

        public function processImg() {
            $json = array();
            $json["recipient"]["id"] = $this -> sender;
            $json["message"]["text"] = "Sorry, this service is not available! \n 很抱歉，你說的這項服務我無法完成！";
            return $json;
        }

        public function processFile() {
            $json = array();
            $json["recipient"]["id"] = $this -> sender;
            $json["message"]["text"] = "Sorry, this service is not available! \n 很抱歉，你說的這項服務我無法完成！";
            return $json;
        }

        private function getGif($animal) {
            $url = "http://api.giphy.com/v1/gifs/search?q=funny+" . $animal . "&api_key=dc6zaTOxFJmzC";
            $client = new Client();
            $response = $client -> request("GET", $url);
            $json = json_decode($response -> getBody(). true);
            $data = $json["data"];
            $dataLen = count($data);
            $index = rand(0, $dataLen);
            return $data[$index]["url"];
        }

    }
?>
