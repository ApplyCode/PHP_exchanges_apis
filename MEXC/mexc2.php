<?php

class APIException extends \ErrorException
{
}

class MEXCAPI
{
    protected $key;
    protected $secret;
    protected $url;
    protected $version;
    protected $sslverify;
    protected $curl;

    function __construct($key, $secret, $url = 'https://api.mexc.com', $version = 'v3', $sslverify = true)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->url = rtrim($url, '/') . '/';
        $this->version = $version;
        $this->sslverify = $sslverify;
        $this->curl = curl_init();

        curl_setopt_array($this->curl, [
            CURLOPT_SSL_VERIFYPEER => $sslverify,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Exchange PHP API Agent',
            CURLOPT_RETURNTRANSFER => true
        ]);
    }

    function __destruct()
    {
        curl_close($this->curl);
    }

    private function executeRequest($url, $headers = [], $bodyData = null)
    {
        curl_reset($this->curl);
        curl_setopt_array($this->curl, [
            CURLOPT_SSL_VERIFYPEER => $this->sslverify,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Mexc API',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        // curl_setopt($this->curl, CURLOPT_URL, $url);

        // curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        print_r($headers);
        if ($bodyData !== null) {
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $bodyData);

        }else {
            if (isset($headers['Custom-Request-Type']) && $headers['Custom-Request-Type'] === 'DELETE') {
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                unset($headers['Custom-Request-Type']); // Remove the custom header before executing
            }
        }
        $result = curl_exec($this->curl);
        if ($result === false) {
            throw new APIException('CURL error: ' . curl_error($this->curl));
        }
        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new APIException('JSON decode error');
        }

        return $decoded;
    }

    public function QueryPublic($method, array $request = [])
    {
        // MEXC code for public API
        $url = $this->url . 'api/' . $this->version . '/' . $method;
        if (!empty($request)) {
            $postdata = http_build_query($request, '', '&');
            $url = $url . '?' . $postdata;
        }
        return $this->executeRequest($url);
    }

    //Get Servertime for timestamp
    private function getServerTime()
    {
        $response = $this->QueryPublic("time");
        if (isset($response['serverTime'])) {
            return $response['serverTime'];
        } else {
            throw new APIException("Unable to fetch server time");
        }
    }

    public function QueryPrivate($httpMethod = 'POST', $method, array $request = []) {
        $timeStamp = $this->getServerTime();
        $request['timestamp'] = $timeStamp;
    
        $postdata = http_build_query($request, '', '&');
        $sign = hash_hmac('sha256', $postdata, $this->secret);
        $bodyData = null;
        $headers = [
            'Content-Type: ' . 'application/json',
            'X-MEXC-APIKEY: ' . $this->key
        ];
    
        if ($method == 'batchOrders' && strtoupper($httpMethod) == 'POST') {
            $queryString = $postdata;
            $url = $this->url . 'api/' . $this->version . '/' . $method . '?' . $queryString . '&signature=' . $sign;
            $bodyData = '';
        } else {
            switch (strtoupper($httpMethod)) {
                case 'GET':
                    $url = $this->url . 'api/' . $this->version . '/' . $method . '?' . $postdata . '&signature=' . $sign;
                    break;
                case 'POST':
                    $bodyData = $postdata . '&signature=' . $sign;
                    $url = $this->url . 'api/' . $this->version . '/' . $method;
                    break;
                case 'DELETE':
                    $url = $this->url . 'api/' . $this->version . '/' . $method . '?' . $postdata . '&signature=' . $sign;
                    $headers['Custom-Request-Type'] = 'DELETE';
                    break;
                default:
                    throw new APIException("Unsupported HTTP method: $httpMethod");
            }
        }
    
        return $this->executeRequest($url, $headers, $bodyData);
    }
    

    public function deleteRequest($method, array $request = []) {
        // Get server time for timestamp
        $timeStamp = $this->getServerTime();
        $request['timestamp'] = $timeStamp;

        // Construct the query string
        $postdata = http_build_query($request, '', '&');
        $sign = hash_hmac('sha256', $postdata, $this->secret);

        // Build the URL with query parameters and signature
        $url = $this->url . 'api/' . $this->version . '/' . $method;
        $url .= '?' . $postdata . '&signature=' . $sign;
        // print_r($url);
        // Set the required headers
        curl_reset($this->curl);
        curl_setopt_array($this->curl, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => [
                'X-MEXC-APIKEY: ' . $this->key,
                // Add other headers as needed
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => $this->sslverify,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        // Execute the cURL request
        $result = curl_exec($this->curl);
        if ($result === false) {
            throw new APIException('CURL error: ' . curl_error($this->curl));
        }

        // Decode the response
        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new APIException('JSON decode error');
        }

        return $decoded;
    }

    public function QueryBatchOrders(array $ordersData)
    {
        // $timeStamp=$this->getServerTime();
        // $request['timestamp'] = $timeStamp;
            // $request['batchOrders'] = json_encode($request['batchOrders']);


        // Prepare batch orders
        // $encodedBatchOrders = urlencode($request);
        $method = 'batchOrders'; // Endpoint for batch orders
        $timeStamp = $this->getServerTime();

        // Prepare batch orders
        $batchOrderData = json_encode($ordersData);
        $encodedBatchOrders = urlencode($batchOrderData);
        // Build query string
        $queryString = "batchOrders={$encodedBatchOrders}&timestamp={$timeStamp}";
        
        // Generate Signature
        $sign = hash_hmac('sha256', $queryString, $this->secret);
        
        // Complete URL with signature
        $url = $this->url . 'api/' . $this->version . '/' . $method . '?' . $queryString . '&signature=' . $sign;
        print_r($url);
        $headers = [
            'Content-Type: ' . 'application/json',
            'X-MEXC-APIKEY: ' . $this->key,
        ];

        // // Configure cURL for a POST request
        // curl_setopt($this->curl, CURLOPT_POST, true);
        // curl_setopt($this->curl, CURLOPT_POSTFIELDS, ''); // Assuming no body data is required, or add body data if needed

        // return $this->executeRequest($url, null, $headers);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => $this->sslverify,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'MEXC PHP API Agent',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '' // No body data
        ]);

        // Execute cURL session
        $result = curl_exec($curl);
        if ($result === false) {
            throw new APIException('CURL error: ' . curl_error($curl));
        }

        // Close cURL session
        curl_close($curl);

        // Decode JSON response
        $decoded = json_decode($result, true);
        if (null === $decoded) {
            throw new APIException('JSON decode error. Raw response: ' . $result);
        }

        return $decoded;
    }
}

$key = 'mx0vgldC9tjd5OPtva';
$secret = 'db6bb3eb01d74add8fdc6498fba733b8';

$mexc = new MEXCAPI($key, $secret);

try {
    // // Test Connectivity
    // $testConnect = $mexc->QueryPublic("ping");
    // echo "Test Connecttivity\n";
    // print_r($testConnect);

    // // Check Server Time
    // $serverTime = $mexc->QueryPublic("time");
    // echo "Check Server Time\n";
    // print_r($serverTime);

    // // API default symbol
    // $apiSymbol = $mexc->QueryPublic("defaultSymbols");
    // echo "API Default Symbol\n";
    // print_r($apiSymbol);

    // // Exchange Information
    // $exchangeInfo = $mexc->QueryPublic('exchangeInfo', ['symbol' => 'MXUSDT']);
    // echo "Exchange Information:\n";
    // print_r($exchangeInfo);

    // //Order Book-> required: symbol
    // $orderBook = $mexc->QueryPublic("depth", ["symbol" => "BTCUSDT", "limit" => "3"]);
    // echo "Order Book\n";
    // print_r($orderBook);

    // // Recent Trades List-> required: symbol
    // $recentTrades= $mexc->QueryPublic("trades", ["symbol"=> "MXUSDT", "limit"=> "4"]);
    // echo "Recent Trades List\n";
    // print_r($recentTrades);

    // // Compressed/Aggregate Trades List-> required: symbol
    // $aggTrades= $mexc->QueryPublic("aggTrades", ["symbol"=>"MXUSDT", "limit"=> "4"]);
    // echo "Compressed/Aggregate Trades List\n";
    // print_r($aggTrades);

    // // Kline/Candlestick Data-> required: symbol, interval
    // $klineData = $mexc->QueryPublic("klines", ["symbol"=>"BTCUSDT", "interval"=> "1m", "limit"=> "3"]);
    // echo "Kline Data\n";
    // print_r($klineData);

    // // Current Average Price-> required: symbol
    // $averagePrice = $mexc->QueryPublic("avgPrice", ["symbol"=> "MXUSDT"]);
    // echo "Current Anverage Price\n";
    // print_r($averagePrice);

    // // 24hr Ticker Price Change Statistics
    // // If the symbol is not sent, all symbols will be returned in an array.
    // $dayTicker = $mexc->QueryPublic("ticker/24hr", ["symbol"=>"MXUSDT"]);
    // echo "24hr Ticker Price Chage Statistics\n";
    // print_r($dayTicker);

    // // Symbol Price Ticker
    // // If the symbol is not sent, all symbols will be returned in an array.
    // $symbolTicker = $mexc->QueryPublic("ticker/price", ["symbol"=>"BTCUSDT"]);
    // echo "Symbol Price Ticker\n";
    // print_r($symbolTicker);

    // // Symbol Order Book Ticker
    // // If the symbol is not sent, all symbols will be returned in an array
    // $symbolBookTicker = $mexc->QueryPublic("ticker/bookTicker", ["symbol"=>"BTCUSDT"]);
    // echo "Symbol Order Book Ticker\n";
    // print_r($symbolBookTicker);

    //<---------------------------Start Spot Trade/Account API Here----------------------------------

    // // User API default symbol
    // $defaultSymbol=$mexc->QueryPrivate("GET", "selfSymbols");
    // echo "User API default symbol\n";
    // print_r($defaultSymbol);

    // // Test New Order
    // $testOrder=$mexc->QueryPrivate('POST', "order/test", ["symbol"=> "BTCUSDT", "side"=> "BUY", "type"=>"LIMIT", "price"=>"1000"]);
    // echo "Test New Order\n";
    // print_r($testOrder);

    // // New Order -> required: symbol, side, type
    // $orderResult = $mexc->QueryPrivate('POST', 'order', [
    //     'symbol' => 'MXTUSDT',
    //     'side' => 'BUY',
    //     'type' => 'LIMIT',
    //     'quantity' => 1,
    //     'price' => 10000
    // ] );
    // echo "Order result:\n";
    // print_r($orderResult);

    // // Batch Orders
    // $ordersData = [
    //     [
    //         'symbol' => 'MXTUSDT',
    //         'side' => 'BUY',
    //         'type' => 'LIMIT',
    //         'quantity' => 1,
    //         'price' => 10000
    //     ],
    //     [
    //         'symbol' => 'MXTUSDT',
    //         'side' => 'SELL',
    //         'type' => 'LIMIT',
    //         'quantity' => 0.003,
    //         'price' => 10000
    //     ]
    // ];
    // $jsonEncodedOrdersData = json_encode($ordersData);

    // $batchOrders = $mexc->QueryPrivate("POST", "batchOrders", ["batchOrders" => $jsonEncodedOrdersData]);
    // echo "Batch Orders";
    // print_r($batchOrders);

    // $batchOrders = $mexc->QueryBatchOrders($ordersData);
    // echo"Batch Orders\n";
    // print_r($batchOrders);

    // // Cancel Order - Cancel an active order
    // $cancelOrder= $mexc->deleteRequest("order", ["symbol"=>"MXUSDT", "orderId"=>"3"]);
    // echo "Cancel Order\n";
    // print_r($cancelOrder);

    // Cancel Order - Cancel an active order
    $cancelOrder= $mexc->QueryPrivate("DELETE", "order", ["symbol"=>"MXUSDT", "orderId"=>"3"]);
    echo "Cancel Order\n";
    print_r($cancelOrder);

    // // Cancel all Open Orders on a Symbol - Cancel all pending orders for a single symbol, including OCO pending orders
    // $cancelAllOpenOrder= $mexc->QueryPrivate("DEL","openOrders", ["symbol"=>"MXUSDT"]);
    // echo "Cancel all open orders\n";
    // print_r($cancelAllOpenOrder);

    // // Query Order
    // $queryOrder = $mexc->QueryPrivate("GET", "order", ["symbol"=>"LTCBTC", "orderId"=> "1"]);
    // echo "Query Order\n";
    // print_r($queryOrder);

    // // Current Open Orders
    // $openOrders= $mexc->QueryPrivate("GET","openOrders", ["symbol"=>"MXUSDT"]);
    // echo "Current Open Orders\n";
    // print_r($openOrders);

    // // All Orders
    // $allOrders=$mexc->QueryPrivate("GET","allOrders", ["symbol"=> "MXUSDT"]);
    // echo "All Orders\n";
    // print_r($allOrders);

    // // Account Information
    // $accountInfo = $mexc->QueryPrivate("GET", "account");
    // echo"Account Information\n";
    // print_r($accountInfo);

    // // Account Trade List
    // $tradeList=$mexc->QueryPrivate("GET","myTrades", ["symbol"=> "MXUSDT"]);
    // echo "Account Trade List\n";
    // print_r($tradeList);

    // // Enable MX Deduct
    // $mxDeduct= $mexc->QueryPrivate("POST", "mxDeduct/enable", ["mxDeductEnable"=>"true"]);
    // echo "Enable MX Deduct\n";
    // print_r($mxDeduct);

    // // Query MX Deduct Status
    // $mxDeductStatus=$mexc->QueryPrivate("GET","mxDeduct/enable");
    // echo "Query MX Deduct Status\n";
    // print_r($mxDeductStatus);

} catch (APIException $e) {
    echo 'API call failed: ' . $e->getMessage();
}



?>