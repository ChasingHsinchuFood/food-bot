<?php
    use \GuzzleHttp\Client;

    class ProcessMessage {
        private $confidence = null;

        private $entities = [
            'greeting', 'wit/datetime',
            'wit/location', 'wit/local_search_query',
        ];

        public function __construct($message, $sender) {
            $this->message =  strtolower($message);
            $this->sender = $sender;
        }

        public function handleEntity($data, $name) {
            return isset($data['nlp']) && isset($data['nlp']['entities'])
                && isset($data['nlp']['entities'][$name])
                && isset($data['nlp']['entities'][$name][0]);
        }

        public function processText() {
            $json = array();
            $json["recipient"]["id"] = $this->sender;

            $needle = "hello";
            $needleTw = "你好";

            if(mb_stristr($this->message, $needle) != false) {
                $json["message"]["attachment"]["type"] = "image";
                $links = ['https://i.giphy.com/media/26u8ymPsDsnu1YWg8/giphy.webp',
                    'https://i.giphy.com/media/26u8ymPsDsnu1YWg8/giphy.webp'];
                $json["message"]["attachment"]["payload"]["url"] = $links[array_rand($links)];
            }
            else if(mb_strlen($this->message) <= 5) {
                $json["message"]["text"] = "You have to input more texts so that I can understand what do you mean \n 你必須打更多的字好讓我看的懂！";
            }
            else {
                $messages = explode(",", $this->message);
                $json = $this->processTextSplit($json, $messages);
            }

            return $json;
        }

        public function processPostBack() {
            $message = "";

            if($this->message === "what_do_you_want_to_eat") {
                $message = "使用說明在下列網址：\n the command lists is about the following url:\n";
                $message .= "https://hsinchu.life/eat_map";
            }
            else if($this->message === "give_me_command_lists") {
                $message = "使用說明在下列網址：\n";
                $message .= "https://hsinchu.life/need_help";
            }
            else {
                $message = "invalid post back.";
            }

            return $message;
        }

        public function processImg() {
            $json = array();
            $json["recipient"]["id"] = $this->sender;
            $json["message"]["text"] = "Sorry, this service is not available! \n 很抱歉，你說的這項服務我無法完成！";
            return $json;
        }

        public function processFile() {
            $json = array();
            $json["recipient"]["id"] = $this->sender;
            $json["message"]["text"] = "Sorry, this service is not available! \n 很抱歉，你說的這項服務我無法完成！";
            return $json;
        }

        private function processTextSplit($json, $messages) {
            if(count($messages) === 3) {
                if(mb_stristr($messages[0], "公車") != false || mb_stristr($messages[0], "bus") != false) {
                    $msg = "動態查詢服務已在下列網址完成。\n";
                    $msg .= "https://life-bot.ga/life-bot/bus/city/" . $messages[1] . "/route" . "/" . $messages[2];
                    $json["message"]["text"] = $msg;
                }
                else {
                    $json["message"]["text"] = "公車動態服務無法達成！\nthe dynamic bus service is not successful";
                }
            }
            else if(count($messages) === 2) {
                if(mb_stristr($messages[0], "客運") != false || mb_stristr($messages[0], "bus") != false) {
                    $msg = "動態公車詢服務已在下列網址完成。\n";
                    $msg .= "https://life-bot.ga/life-bot/bus/inter-city/route" . "/" . $messages[1];
                    $json["message"]["text"] = $msg;
                }
                else {
                    $json["message"]["text"] = "公車動態服務無法達成！\nthe dynamic bus service is not successful";
                }
            }
            else if(mb_stristr($this->message, "I have to book my ticket") != false || mb_stristr($this->message, "我需要訂票") != false) {
                $json["message"]["text"] = "Ok! please upload the 'ticket.txt'. \n 好的，請上傳『訂票檔』。";
            }
            else if(mb_stristr($this->message, "_dog_cat") != false) {
                $json["message"]["attachment"]["type"] = "image";
                $json["message"]["attachment"]["payload"]["url"] = $this->processPostBack();
            }
            else if(mb_stristr($this->message, "_") != false) {
                $msg = $this->processPostBack();
                $json["message"]["text"] = $msg;
            }
            else {
                $json["message"]["text"] = "Sorry, this service is not available! \n 很抱歉，你說的這項服務我無法完成！";
            }

            return $json;
        }

        private function getGif($animal) {
            $url = "http://api.giphy.com/v1/gifs/search?q=funny+" . $animal . "&api_key=dc6zaTOxFJmzC";
            $client = new Client();
            $response = $client->request("GET", $url);
            $json = json_decode($response->getBody(), true);
            $result = array();

            if($json["meta"]["status"] == 200) {
                $data = $json["data"];
                $dataLen = count($data);
                for($index=0;$index<$dataLen;$index++) {
                    $result[$index] = $data[$index]["images"]["original"]["url"];
                }

                shuffle($result);
                $res = array_rand($result);
                return $result[$res];
            }
            else {
                return "https://valleytechnologies.net/wp-content/uploads/2015/07/error.png";
            }
        }

    }
?>
