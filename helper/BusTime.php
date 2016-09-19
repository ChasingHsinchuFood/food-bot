<?php
    use \GuzzleHttp\Client;
    use \GuzzleHttp\Psr7;

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
            if(is_array($estJson) === false || is_array($routeJson) === false || is_array($directionJson) === false) {
                return "查無此資訊！";
            }
            else {
                $result = array();

                $routeZero = $directionJson["direction_0"];
                $routeOne = $directionJson["direction_1"];

                $result["direction_0_text"] = $routeZero;
                $result["direction_1_text"] = $routeOne;
                
                $dirZeroLen = count($routeJson["Direction_0"]);
                $dirOneLen = count($routeJson["Direction_1"]);

                $estLen = count($estJson);

                $resultZeroIndex = 0;
                $resultOneIndex = 0;


                for($routeIndex=0;$routeIndex<$dirZeroLen;$routeIndex++) {
                     $stopName = $routeJson[$routeIndex]["Direction_0"];

                     for($estIndex=0;$estIndex<$estLen;$estIndex++) {
                        if($estJson[$estIndex]["Direction"] == 0 && $stopName == $estJson[$estIndex]["StopName"]["Zh_tw"]) {
                            if(isset($estJson[$estIndex]["StopStatus"])) {
                                switch($estJson[$estIndex]["StopStatus"]) {
                                    case 1:
                                        $result["direction_0_stop_name"]["stop_status"] = "尚未發車";     
                                        break;
                                    case 2:
                                        $result["direction_0_stop_name"]["stop_status"] = "交管不停靠";
                                        break;
                                    case 3:
                                        $result["direction_0_stop_name"]["stop_status"] = "末班車已過";
                                        break;
                                    case 4:
                                        $result["direction_0_stop_name"]["stop_status"] = "今日未營運";
                                        break;
                                }
                            }
                            else {
                                $result["direction_0_stop_name"]["stop_status"] = "正常發車";
                            }

                            $result["direction_0_stop_name"][$resultZeroIndex] = $stopName;

                            $result["direction_0_stop_name"]["est_time"][$resultZeroIndex] = $estJson[$estIndex]["EstimateTime"];

                            $resultZeroIndex += 1;
                        }

                        if($estJson[$estIndex]["Direction"] == 1 && $stopName == $estJson[$estIndex]["StopName"]["Zh_tw"]) {
                            if(isset($estJson[$estIndex]["StopStatus"])) {
                                switch($estJson[$estIndex]["StopStatus"]) {
                                    case 1:
                                        $result["direction_0_stop_name"]["stop_status"] = "尚未發車";     
                                        break;
                                    case 2:
                                        $result["direction_0_stop_name"]["stop_status"] = "交管不停靠";
                                        break;
                                    case 3:
                                        $result["direction_0_stop_name"]["stop_status"] = "末班車已過";
                                        break;
                                    case 4:
                                        $result["direction_0_stop_name"]["stop_status"] = "今日未營運";
                                        break;
                                }
                            }
                            else {
                                $result["direction_0_stop_name"]["stop_status"] = "正常發車";
                            }

                            $result["direction_1_stop_name"][$resultOneIndex] = $stopName;

                            $result["direction_1_stop_name"]["est_time"][$resultOneIndex] = $estJson[$estIndex]["EstimateTime"];

                            $resultOneIndex += 1;
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

                if(count($result) === 0) {
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
            if($message[0] === "公車") {
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
                if(count($result) === 0) {
                    $result = "no-data";
                }
                else {
                    $res["direction_0"] = $result[count($result)-1]["DepartureStopNameZh"];
                    $res["direction_1"] = $result[count($result)-1]["DestinationStopNameZh"];
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
                
            $directionGo = array();
            $directionBack = array();

            $backNum = 0;
            $goNum = 0;

            $len = count($result);

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
