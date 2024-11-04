<?php

class KrakenAPIException extends \ErrorException
{
}
;

class KrakenAPI
{
    protected $key;
    protected $secret;
    protected $url;
    protected $version;
    protected $sslverify;
    protected $curl;

    function __construct($key, $secret, $url = 'https://api.kraken.com', $version = '0', $sslverify = true)
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
            CURLOPT_USERAGENT => 'Kraken PHP API Agent',
            CURLOPT_RETURNTRANSFER => true
        ]);
    }

    function __destruct()
    {
        curl_close($this->curl);
    }

    private function executeRequest($url, $postdata = null, $headers = [], $isBinary = false)
    {
        curl_reset($this->curl);
        curl_setopt_array($this->curl, [
            CURLOPT_SSL_VERIFYPEER => $this->sslverify,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Kraken PHP API Agent',
            CURLOPT_RETURNTRANSFER => true
        ]);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        // curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        if ($postdata !== null) {
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postdata);
        }

        $result = curl_exec($this->curl);
        if ($result === false) {
            throw new KrakenAPIException('CURL error: ' . curl_error($this->curl));
        }

        if ($isBinary) {
            return $result; // Return binary data directly
        }

        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new KrakenAPIException('JSON decode error');
        }

        return $decoded;
    }

    public function QueryPublic($method, array $request = [])
    {
        $url = $this->url . $this->version . '/public/' . $method;
        if (!empty($request)) {
            $postdata = http_build_query($request, '', '&');
            $url = $url . '?' . $postdata;
            return $this->executeRequest($url);
        } else {
            // Use GET request for methods without parameters
        
            return $this->executeRequest($url);
        }
    }

    public function QueryPrivate($method, array $request = [])
    {
        if (!isset($request['nonce'])) {
            $nonce = explode(' ', microtime());
            $request['nonce'] = $nonce[1] . str_pad(substr($nonce[0], 2, 6), 6, '0');
        }

        $postdata = http_build_query($request, '', '&');
        $path = $this->version . '/private/' . $method;
        $sign = hash_hmac('sha512', '/' . $path . hash('sha256', $request['nonce'] . $postdata, true), base64_decode($this->secret), true);
        $headers = [
            'API-Key: ' . $this->key,
            'API-Sign: ' . base64_encode($sign)
        ];

        $isBinary = ($method == "RetrieveExport");

        return $this->executeRequest($this->url . $path, $postdata, $headers, $isBinary);
    }
}

// Usage example
$key = 'SI78jHB6nSk48J9GxJ2LIynqFA4gVQO76R2Or5X3tnqlV3kD69kgcx8l';
$secret = 'A2aESpGT3Fn51XtDhrnsP01QNVOQane4H5ZoBp6uQzAaQT2Aq6echDZSLzd/XtXyfIQenk6Pi1wYgmgtjVtO6A==';
$kraken = new KrakenAPI($key, $secret);

try {
    // //Get Asset Info
    // $assets = $kraken->QueryPublic('Assets');
    // echo "Asset Information:\n";
    // print_r($assets);

    //Get Ticker(Price) Information
    $ticker = $kraken->QueryPublic('Ticker', ['pair' => 'XXBTZUSD']);
    echo "The current price is:\n";
    print_r($ticker);

    // // Get OHLC Data
    // // interval Enum: 1 5 15 30 60 240 1440 10080 21600
    // $ohlcData = $kraken->QueryPublic("OHLC", ["pair"=> "XXBTZUSD", "interval"=>1]);
    // echo "OHLC Data\n";
    // print_r($ohlcData);

    //Get Order Book Data
    //count: [ 1 .. 500 ]
    // $orderBookData = $kraken->QueryPublic("Depth", ["pair"=> "XXBTZUSD", "count"=>2]);
    // echo "Order Book Data\n";
    // print_r($orderBookData);

    // Get Recent Spreads
    // $recentSpreads = $kraken->QueryPublic("Spread", ["pair"=> "XXBTZUSD"]);
    // echo "Recent Spreads\n";
    // print_r($recentSpreads);

    // // Get Recent Trades
    // //count: integer [ 1 .. 1000 ]
    // $recentTrades = $kraken->QueryPublic("Trades", ["pair"=> "XXBTZUSD", "since"=> 1707684437]);
    // echo "Recent Trades\n";
    // print_r($recentTrades);

    // // Get Server Time
    // $serverTime = $kraken->QueryPublic('Time');
    // echo "Server Time\n";
    // print_r($serverTime);

    //Get System Status
    // $systemStatus = $kraken->QueryPublic("SystemStatus");
    // echo "System Status\n";
    // print_r($systemStatus);

    // Get Tradable Asset Pairs
    // $tradableAssetPairs= $kraken->QueryPublic("AssetPairs", ["pair"=> "XXBTZUSD"]);
    // echo "Tradable Asset Pairs";
    // print_r($tradableAssetPairs);

    //Get Account Balance
    // $accountBalance = $kraken->QueryPrivate("Balance");
    // echo "Balance\n";
    // print_r($accountBalance);

    //Get Extended Balance
    // $extendedBalance= $kraken->QueryPrivate("BalanceEx");
    // echo "Extended Balance\n";
    // print_r($extendedBalance);

    //Get Trade Balance
    // $tradeBalance= $kraken->QueryPrivate("TradeBalance");
    // echo "Trade Balance\n";
    // print_r($tradeBalance);

    //Get Open Orders
    // $openOrders = $kraken->QueryPrivate("OpenOrders");
    // echo "Open Orders";
    // print_r($openOrders);

    //Get Closed Orders
    // $closedOrders = $kraken->QueryPrivate("ClosedOrders",["trades"=> true]);
    // echo "Closed Orders";
    // print_r($closedOrders);

    //Query Orders Info
    // $ordersInfo = $kraken->QueryPrivate("QueryOrders", ["txid"=> "YOUR_TRANSACTION_ID"]);
    // echo "Query Orders Info";
    // print_r($ordersInfo);

    //Get Trades History
    //Default: "all"
    // Enum: "all" "any position" "closed position" "closing position" "no position"
    // $tradesHistory = $kraken->QueryPrivate("TradesHistory");
    // echo "Trades History\n";
    // print_r($tradesHistory);

    // //Query Trades Info
    // $tradesInfo = $kraken->QueryPrivate("QueryTrades", ['txid'=>'TRWCIF-3MJWU-5DYJG5,TNGJFU-5CD67-ZV3AEO']);//YOUR_TRANSACTION_ID
    // echo "Trades Info\n";
    // print_r($tradesInfo);

    // //Get Open Positions
    // $openPositions=$kraken->QueryPrivate("OpenPositions", ['txid'=>'TRWCIF-3MJWU-5DYJG5,TNGJFU-5CD67-ZV3AEO']);//YOUR_TRANSACTION_ID
    // echo "Open Positions\n";
    // print_r($openPositions);

    // //Get Ledgers Info
    // //type Enum: "all" "trade" "deposit" "withdrawal" "transfer" "margin" "adjustment" "rollover" "credit" "settled" "staking" "dividend" "sale" "nft_rebate"
    // $ledgersInfo = $kraken->QueryPrivate("Ledgers", ["type"=> "sale"]);
    // echo "Ledgers Info\n";
    // print_r($ledgersInfo);

    // //Query Ledgers
    // $queryLedgers = $kraken->QueryPrivate("QueryLedgers", ["id" => "LGBRJU-SQZ4L-5HLS3C,L3S26P-BHIOV-TTWYYI"]);
    // echo "Ledgers\n";
    // print_r($queryLedgers);

    // //Get Trade Volume
    // $tradeVolume = $kraken->QueryPrivate("TradeVolume", ["pair" => "XETCXETH"]);
    // echo "Trade Volume";
    // print_r($tradeVolume);

    // //Request Export Report
    // //required-> report Enum: "trades" "ledgers", description
    // $exportReport = $kraken->QueryPrivate("AddExport", ["report" => "trades", "description" => "my_trades_report"]);
    // echo "Export Report:\n";
    // print_r($exportReport);

    // //Get Export Report Status
    // //required-> report Enum: "trades" "ledgers"
    // $exportReportStatus = $kraken->QueryPrivate("ExportStatus", ["report" => "trades"]);
    // echo "Export Report Status";
    // print_r($exportReportStatus);

    // //Retrieve Data Export
    // //required-> id
    // $retrieveDataExport = $kraken->QueryPrivate("RetrieveExport", ["id" => "XPHJ"]);
    // file_put_contents("export.zip", $retrieveDataExport);
    // echo "Data export retrieved and saved as export.zip to your current directory\n";

    // //Delete Export Report
    // //required-> id, type Enum: "cancel" "delete"
    // $deleteExportReport = $kraken->QueryPrivate("RemoveExport", ["id" => "XPHJ", "type" => "delete"]);
    // echo "Delete Export Report\n";
    // print_r($deleteExportReport);

    // //<--------------------------------Trading---------------------------------------->
    // //Add Order
    // //required-> ordertype Enum: "market" "limit" "stop-loss" "take-profit" "stop-loss-limit" "take-profit-limit" "settle-position", type Enum: 'buy' 'sell', volume, pair
    // $addOrder = $kraken->QueryPrivate("AddOrder", [
    //     "ordertype" => "limit",
    //     "type" => "buy",
    //     "volume" => "1.123",
    //     "pair" => "XXBTZUSD",
    //     "price" => "10"]);
    // echo "Add Order";
    // print_r($addOrder);

    // //Add Order Batch
    // //required->orders, pair
    // $ordersData = [
    //     [
    //         "close" => [
    //             "ordertype" => "stop-loss-limit",
    //             "price" => "37000",
    //             "price2" => "36000"
    //         ],
    //         "ordertype" => "limit",
    //         "price" => "40000",
    //         "type" => "buy",
    //         "volume" => "1.2",
    //     ],
    //     [
    //         "ordertype" => "limit",
    //         "price" => "42000",
    //         "type" => "sell",
    //         "volume" => "1.2",
    //     ]
    // ];
    // $addOrderBatch=$kraken->QueryPrivate("AddOrderBatch", ["pair"=> "BTC/USD", "orders"=>$ordersData]);
    // echo "Add Order Batch\n";
    // print_r($addOrderBatch);

    // // Edit Order
    // // required-> txid, pair
    // $editOrder = $kraken->QueryPrivate("EditOrder", [
    //     "pair" => "XXBTZUSD",
    //     "txid" => "OHYO67-6LP66-HMQ437",
    //     "ordertype" => "limit",
    //     "type" => "buy",
    //     "volume" => "2.123"]);
    // echo "Edit Order\n";
    // print_r($editOrder);



    // //Cancel Order
    // //required-> txid
    // $cancelOrder=$kraken->QueryPrivate("CancelOrder", ["txid"=> "OYVGEW-VYV5B-UUEXSK"]);
    // echo"Cancel Order\n";
    // print_r($cancelOrder);

    // // Cancel All Orders
    // $cancelAllOrder=$kraken->QueryPrivate("CancelAll");
    // echo "Cancel All Orders";
    // print_r($cancelAllOrder);

    // // Cancel All Orders After X
    // $cancelAllOrderAfter=$kraken->QueryPrivate("CancelAllOrdersAfter", ["timeout"=> 60]);
    // echo "Cancel All Orders After 60s\n";
    // print_r($cancelAllOrderAfter);
    
    // // Cancel Order Batch
    // $cancelOrderBatch=$kraken->QueryPrivate("CancelOrderBatch", ["orders"=> ["OG5V2Y-RYKVL-DT3V3B","OP5V2Y-RYKVL-ET3V3B"]]);
    // echo "Cancel Order Batch\n";
    // print_r($cancelOrderBatch);







} catch (KrakenAPIException $e) {
    echo 'API call failed: ' . $e->getMessage();
}

?>