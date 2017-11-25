<?php
    use \GuzzleHttp\Client;

    class ProcessMessage {
        private $confidence = null;

        private $entities = [
            'greeting', 'datetime',
            'location', 'local_search_query',
        ];

        public function __construct($message, $sender) {
            $this->message =  strtolower($message);
            $this->sender = $sender;
        }

        public function processText() {
            $json = array();
            $json["recipient"]["id"] = $this->sender;
            if($this->message === '你好') {
                $json["message"]["text"] = '嗨，有什麼事可以為你效勞的嘛？';
            } else {
                $json["message"]["text"] = $this->processPostBack();
            }

            return $json;
        }

        public function processGuessText($entitity, $term) {
            $json = array();
            $json["recipient"]["id"] = $this->sender;

            if($entitity == 'greeting') {
                $json["message"]["text"] = 'Hi 你好阿！';
                return $json;
            }

            if($entitity == 'location') {
                // location
                $json["message"]["text"] = 'Hi 你好阿！';
                return $json;
            }

            if($entitity == 'local_search_query') {
                // local_search_query
                $message = "你想要的結果已經在下面網址了! \n";
                $message .= 'https://hsinchu.life/eat_search_map/'.urlencode($term);
                $json["message"]["text"] = $message;
                return $json;
            }
        }

        public function processPostBack() {
            $message = "";

            if($this->message === "what_do_you_want_to_eat") {
                $message = "為你推薦的美食在下面連結裡：\n";
                $message .= "https://hsinchu.life/eat_map";
            }
            else if($this->message === "give_me_command_lists") {
                $message = "使用說明在下列網址：\n";
                $message .= "https://hsinchu.life/need_help";
            }
            else {
                $message = "無此項目服務！";
            }

            return $message;
        }
    }
?>
