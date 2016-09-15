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
                伺服器發生錯誤\n請過 15 秒再查詢，謝謝。";
            
            try {
                $client = new Client();
                $reqUrl = "http://ptx.transportdata.tw/MOTC/v2/Bus/EstimatedTimeOfArrival/City/" . $msgs[1] . "/" . $msgs[2] . "?%24select=Direction%2CStopStatus%2CEstimateTime%2CStopName&%24format=JSON";
                $response = $client -> request("GET", rawurlencode($reqUrl));
                $json = json_decode($response -> getBody(), true);
                $result = $this -> processBus($json);

            }
            catch(GuzzleHttp\Exception\ServerException $e) {
                if ($e->hasResponse()) {
                    $response = Psr7\str($e->getResponse());
                    $response = json_decode($response, true);
                    if(isset($response["message"]))
                        $result = $response["message"];
                }
            }

            return $result;
            
        }

        private function processBus($json, $stopName) {
            if(strlen($json) === 0) {
                return "發生錯誤，請過 15 秒再查詢，謝謝！";
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
