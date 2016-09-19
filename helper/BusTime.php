<?php
    use \GuzzleHttp\Client;

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

                $result = $this->processBus($estJson, $routeJson, $directionJson);

            }
            catch(GuzzleHttp\Exception\ServerException $e) {
                if($e->hasResponse()) {
                    $response = Psr7\str($e->getResponse());
                    $response = json_decode($response, true);
                    if(isset($response["message"]))
                        $result = $response["message"];
                }
            }

            return $result;
            
        }

        //輸出最後的 "table data"
        private function processBus($estJson, $routeJson, $directionJson) {
            if(is_array($estJson) === false) {
                return "查無此資訊！";
            }
            else {
                $result = array();
                $resultIndex = 0;

                $routeZero = $routeJson["direction_0"];
                $routeOne = $routeJson["direction_1"];

                $result["direction_0_text"] = $routeZero;
                $result["direction_1_text"] = $routeOne;
                
                $dirZeroLen = count($routeJson["Direction_0"]);
                $dirOneLen = count($routeJson["Direction_1"]);

                $estLen = count($estJson);

                for($routeIndex=0;$routeIndex<$dirZeroLen;$routeIndex++) {
                    $stopName = $routeJson["Direction_0"][$routeIndex];
                    for($estIndex=0;$estIndex<$estLen;$estIndex++) {
                        if($stopName == $estJson[$estIndex]["StopName"] && $estJson[$estIndex]["Direction"] == 0) {
                            $result["direction_0_stop_name"][$resultIndex] = $estJson[$estIndex]["StopName"];
                            $result["direction_0_stop_name"]["est_time"][$resultIndex] = $estJson[$estIndex]["EstimateTime"];
                            $resultIndex += 1;
                        }
                    }
                }

                for($routeIndex=0;$routeIndex<$dirOneLen;$routeIndex++) {
                    $stopName = $routeJson["Direction_1"][$routeIndex];
                    for($estIndex=0;$estIndex<$estLen;$estIndex++) {
                        if($stopName == $estJson[$estIndex]["StopName"] && $estJson[$estIndex]["Direction"] == 1) {
                            $result["direction_1_stop_name"][$resultIndex] = $estJson[$estIndex]["StopName"];
                            $result["direction_1_stop_name"]["est_time"][$resultIndex] = $estJson[$estIndex]["EstimateTime"];
                            $resultIndex += 1;
                        }
                    }
                }

                return $result;
            }
        }

        //get bus route (取得公車或客運路線)
        private function getBusRoute($message) {
            if($message[0] === "公車") {
                $reqUrl = "http://ptx.transportdata.tw/MOTC/v2/Bus/StopOfRoute/City/" . $message[1] . "/" . $message[2] . "?%24select=Stops%2CDirection&%24format=JSON";
            }
            else {
                $reqUrl = "http://ptx.transportdata.tw/MOTC/v2/Bus/StopOfRoute/InterCity/" . $message[1] . "?%24select=Stops%2CDirection&%24format=JSON";
            }

            try {
                $client = new Client();
                $response = $client->request("GET", $reqUrl);
                $result = json_decode($response->getBody(), true);

                if(strlen($result) === 0) {
                    $result = "no-data";
                    return $result;
                }

                $len = count($result);

                $res = $this->processRoute($result);

                $result = $res;
            }
            catch(GuzzleHttp\Exception\ServerException $e) {
                if($e->hasResponse()) {
                    $response = Psr7\str($e->getResponse());
                    $response = json_decode($response, true);
                    if(isset($response["message"]))
                        $result = $response["message"];
                }
            }

            return $result;
        }

        //get direction (取得去回程) e.g. 去程：台北到基隆 e.g. 基隆到台北
        private function getDirection($message) {
            if($msg[0] === "公車") {
                $reqUrl = "http://ptx.transportdata.tw/MOTC/v2/Bus/Route/City/" . $message[1] . "/" . $message[2] . "?%24select=DepartureStopNameZh%2CDestinationStopNameZh%2CSubRoutes&%24format=JSON";
            }
            else {
                $reqUrl = "http://ptx.transportdata.tw/MOTC/v2/Bus/Route/InterCity/" . $message[1] . "?%24select=DepartureStopNameZh%2CDestinationStopNameZh%2CSubRoutes&%24format=JSON";
            }

            try {
                $res = array();
                $client = new Client();
                $response = $client->request("GET", $reqUrl);
                $result = json_decode($response->getBody(), true);
                if(strlen($result) === 0) {
                    $result = "no-data";
                }
                else {
                    $res["direction_0"] = $result[strlen($result)-1]["DepartureStopNameZh"];
                    $res["direction_1"] = $result[strlen($result)-1]["DestinationStopNameZh"];
                    $result = $res;
                }
            }
            catch(GuzzleHttp\Exception\ServerException $e) {
                if($e->hasResponse()) {
                    $response = Psr7\str($e->getResponse());
                    $response = json_decode($response, true);
                    if(isset($response["message"]))
                        $result = $response["message"];
                }
            }

            return $result;
        }

        private function processRoute($result) {
            $res = array();
            $res["Direction_0"] = array();
            $res["Direction_1"] = array();
                
            $directionGo = null;
            $directionBack = null;

            $backNum = 0;
            $goNum = 0;

            for($index=0;$index<$len;$index++) {
                if($result[$index]["Direction"] == 0) {
                    $stops = $result[$index]["Stops"];
                    $lenStops = count($stops);
                    if($lenStops > $backNum) {
                        $backNum = $lenStops;
                        $directionBack = $stops;
                    }
                }
                else {
                    $stops = $result[$index]["Stops"];
                    $lenStops = count($stops);
                    if($lenStops > $goNum) {
                        $goNum = $lenStops;
                        $directionGo = $stops;
                    }
                }
            }

            for($i=0;$i<$backNum;$i++) {
                $res["Direction_0"][$i] = $directionBack[$i]["StopName"]["Zh_tw"];
            }

            for($i=0;$i<$goNum;$i++) {
                $res["Direction_1"][$i] = $directionGo[$i]["StopName"]["Zh_tw"];
            }

            return $res;
        }


    }
?>
