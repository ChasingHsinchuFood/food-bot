<?php
    class processMessage {
        public function __construct($message, $sender) {
            $this -> message =  $message;
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
            else if(mb_strlen($this -> message) >= 20) {
                  $json["message"]["text"] = "You have to input less texts because I cannot understand what do you mean \n 你必須打更少的字好讓我看的懂！";
            }
            else {
                if($this -> message === "I have to book my ticket" ||  $this -> message === "我需要訂票") {
                      $json["message"]["text"] = "Ok! what ticket do you want? \n 好的，你想要訂什麼票呢？";
                }
                else if($this -> message === "Give me a image" ||  $this -> message === "給我一張圖片") {
                      //$json["message"]["text"] = "Ok! please wait...";
                      $json["message"]["attachment"]["type"] = "image";
                      $json["message"]["attachment"]["payload"]["url"] = "http://i.imgur.com/mwkJtDj.jpg";
                }
                else {
                      $json["message"]["text"] = "Sorry, this service is not available! \n 很抱歉，你說的這項服務我無法完成！";
                }
            }

            return $json;
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

    }
?>
