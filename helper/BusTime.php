<?php
    class BusTime {
        public function __construct($messages) {
            $this -> messages = $messages;
        }

        //取得預估到站時間
        public function getEstTime() {
            $msgs = $this->messages;
            $msgLen = count($msgs);
            $result = null;

            $result = "server error happen,\nplease query after 15 seconds.\n
                伺服器發生錯誤\n請過 15 秒再查詢，謝謝。";
            
            try {
                $client = new Client();

                if($msgs[0] === "公車")
                    $reqUrl = "http://ptx.transportdata.tw/MOTC/v2/Bus/EstimatedTimeOfArrival/City/" . $msgs[1] . "/" . $msgs[2] . "?%24select=Direction%2CStopStatus%2CEstimateTime%2CStopName&%24format=JSON";
                else
                    $reqUrl = "http://ptx.transportdata.tw/MOTC/v2/Bus/EstimatedTimeOfArrival/InterCity/" . $msgs[1] . "?%24select=Direction%2CStopStatus%2CEstimateTime%2CStopName&%24format=JSON";
                
                $response = $client->request("GET", $reqUrl);
                $estJson = json_decode($response->getBody(), true);
                $routeJson = $this->getBusRoute($msgs);
                $directionJson = $this->getDirection($msgs);

                $result = $this->processBus($estJson, $routeJson);

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

        //輸出最後的 json
        private function processBus($json) {
            if(strlen($json) === 0) {
                return "查無此資訊！";
            }
            else {
                
            }
        }

        //get bus route (取得公車或客運路線)
        private function getBusRoute($message) {
            //e.g. http://ptx.transportdata.tw/MOTC/v2/Bus/StopOfRoute/City/Taipei/306?%24select=Stops%2CDirection&%24format=JSON
            //e.g. http://ptx.transportdata.tw/MOTC/v2/Bus/StopOfRoute/InterCity/9006?%24select=Stops%2CDirection&%24format=JSON
            if($message[0] === "公車") {
                $reqUrl = "";
            }
            else {

            }
        }

        //get direction (取得去回程) e.g. 去程：台北到基隆 e.g. 基隆到台北
        private function getDirection($message) {
            //e.g. http://ptx.transportdata.tw/MOTC/v2/Bus/Route/City/Taipei/307?%24select=DepartureStopNameZh%2CDestinationStopNameZh%2CSubRoutes&%24format=JSON
            //e.g. http://ptx.transportdata.tw/MOTC/v2/Bus/Route/InterCity/9006?%24select=DepartureStopNameZh%2CDestinationStopNameZh%2CSubRoutes&%24format=JSON
            if($msg[0] === "公車") {
                $reqUrl = "";
            }
            else {
                
            }
        }


    }
?>
