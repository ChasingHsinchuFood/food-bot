<?php
    class BusTime {
        public function __construct($messages) {
            $this -> messages = $messages;
        }

        public function getStopTime() {
            $msgs = $this -> messages;
            $msgLen = count($msgs);
            $result = null;

            $result = "server error happen,\nplease query after 15 seconds.\n
                伺服器發生錯誤\n請過 15秒 再查詢，謝謝。";
            
            try {
                $client = new Client();
                $reqUrl = "http://ptx.transportdata.tw/MOTC/v2/Bus/EstimatedTimeOfArrival/City/" . $msgs[1] . "/" . $msgs[2] . "?%24select=Direction%2CStopStatus%2CEstimateTime%2CStopName&%24format=JSON";
                $response = $client -> request("GET", rawurlencode($reqUrl));
                $json = json_decode($response -> getBody(), true);
                $result = $this -> processBus($json, $msgs[3]);

            }
            catch(GuzzleHttp\Exception\ServerException $e) {}

            return $result;
            
        }

        private function processBus($json, $stopName) {
            if(isset($json["Message"])) {
                return $json["Message"];
            }
            else {
                if($json["StopName"]["Zh_tw"] === $stopName) {
                    //取得預估到站時間與（去回程）等資訊
                    $resStr = "";
                    
                    return $resStr;
                }
            }
        }
    }
?>
