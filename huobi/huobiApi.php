<?php

class HuobiAPIException extends \ErrorException
{
}
;

class HuobiAPI
{
    protected $key;
    protected $secret;
    protected $url;
    protected $sslverify;
    protected $curl;

    function __construct($key, $secret, $url = 'https://api-aws.huobi.pro', $sslverify = true)
    {
        $this->key = $key ?? '';
        $this->secret = $secret ?? '';
        $this->url = $url;
        $this->sslverify = $sslverify;
        $this->curl = curl_init();

        curl_setopt_array($this->curl, [
            CURLOPT_SSL_VERIFYPEER => $sslverify,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Huobi PHP API Agent',
            CURLOPT_RETURNTRANSFER => true
        ]);
    }

    function __destruct()
    {
        curl_close($this->curl);
    }

    private function executeRequest($url, $postdata = null, $headers = [])
    {
        curl_reset($this->curl);
        curl_setopt_array($this->curl, [
            CURLOPT_SSL_VERIFYPEER => $this->sslverify,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Huobi PHP API Agent',
            CURLOPT_RETURNTRANSFER => true
        ]);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        if ($postdata !== null) {
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postdata);
        }

        $result = curl_exec($this->curl);
        if ($result === false) {
            throw new HuobiAPIException('CURL error: ' . curl_error($this->curl));
        }

        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new HuobiAPIException('JSON decode error');
        }

        return $decoded;
    }

    public function queryMarket($method, array $request = [])
    {
        $url = $this->url . '/market/' . $method;
        if (!empty($request)) {
            $params = http_build_query($request, '', '&');
            $url = $url . '?' . $params;
            return $this->executeRequest($url);
        } else {
            return $this->executeRequest($url);
        }
    }

    public function queryReference($method, array $request = [])
    {
        $url = $this->url . '/' . $method;
        if (!empty($request)) {
            $params = http_build_query($request, '', '&');
            $url = $url . '?' . $params;
            return $this->executeRequest($url);
        } else {
            return $this->executeRequest($url);
        }
    }

    public function QueryPrivate($httpMethod = 'POST', $method, array $bodyData = [])
    {
        if ($httpMethod == 'GET') {
            $params = $bodyData;
        }
        $params['AccessKeyId'] = $this->key;
        $params['SignatureMethod'] = 'HmacSHA256';
        $params['SignatureVersion'] = '2';
        $params['Timestamp'] = gmdate("Y-m-d\TH:i:s");

        ksort($params);
        $paramString = http_build_query($params);
        $domain = ltrim($this->url, 'https://');
        $signStr = "{$httpMethod}\n{$domain}\n/{$method}\n{$paramString}";
        $signature = base64_encode(hash_hmac('sha256', $signStr, $this->secret, true));
        $params['Signature'] = $signature;
        $postData = null;


        if ($httpMethod == 'POST') {
            $postData = json_encode($bodyData);
            $url = $this->url . '/' . $method . '?' . http_build_query($params);
        } else {
            $url = $this->url . '/' . $method . '?' . http_build_query($params);
        }
        $headers = [
            'Content-Type: application/json'
        ];

        return $this->executeRequest($url, $postData, $headers);
    }
}

$key = '6718d96c-1dc72f1a-bvrge3rf7j-5a3a5';
$secret = '3aca730a-e8f81396-086d45aa-37898';
$huobi = new HuobiAPI($key, $secret);

try {

    // <------------------------------Market Data---------------------------------------

    // Get Market Status
    $getMarketStatus = $huobi->queryReference('v2/market-status');
    echo "Get Market Status:\n";
    print_r($getMarketStatus);

    // Get all Supported Trading Symbol(V2)
    // Parameter: ts
    $getAllSymbol = $huobi->queryReference('v2/settings/common/symbols');
    echo "Get all Supported Trading Symbol(V2):\n";
    print_r($getAllSymbol);

    // Get all Supported Currencies(V2)
    // Parameter: ts
    $getAllCurrencies = $huobi->queryReference('v2/settings/common/currencies');
    echo "Get all Supported Currencies(V2):\n";
    print_r($getAllCurrencies);

    // Get Currencys Settings
    // Parameter: ts
    $getCurrentSetting = $huobi->queryReference('v1/settings/common/currencys');
    echo "Get Currencys Settings:\n";
    print_r($getCurrentSetting);

    // Get Symbols Setting
    // Parameter: ts
    $getSymbolSetting = $huobi->queryReference('v1/settings/common/symbols');
    echo "Get Symbols Setting:\n";
    print_r($getSymbolSetting);

    // Get Market Symbols Setting
    // Parameter: ts
    $getMarketSetting = $huobi->queryReference('v1/settings/common/market-symbols');
    echo "Get Market Symbols Setting:\n";
    print_r($getMarketSetting);

    // Get Klines(Candles)
    // Parameter: size  [1-2000]
    $getKlines = $huobi->queryMarket('history/kline', ['symbol' => 'btcusdt', 'period' => '1min']);
    echo "Get Klines:\n";
    print_r($getKlines);

    // Get Latest Aggregated Ticker
    $getAggregatedTicker = $huobi->queryMarket('detail/merged', ['symbol' => 'btcusdt']);
    echo "Get Latest Aggregated Ticker:\n";
    print_r($getAggregatedTicker);

    // Get Latest Tickers for All Pairs
    $getAllPairs = $huobi->queryMarket('tickers');
    echo "Get Latest Tickers for All Pairs:\n";
    print_r($getAllPairs);

    // Get Market Depth
    // Parameter: depth  [5, 10, 20]
    $getMarketDepth = $huobi->queryMarket('depth', ['symbol' => 'btcusdt', 'type' => 'step0']);
    echo "Get Market Depth:\n";
    print_r($getMarketDepth);

    // Get the Last Trade
    $getLastTrade = $huobi->queryMarket('trade', ['symbol' => 'btcusdt']);
    echo "Get the Last Trade:\n";
    print_r($getLastTrade);

    // Get the Most Recent Trades
    // Parameter: size  [1-2000]
    $getRecentTrades = $huobi->queryMarket('history/trade', ['symbol' => 'btcusdt', 'size' => '20']);
    echo "Get the Most Recent Trades:\n";
    print_r($getRecentTrades);

    // Get the Last 24h Market Summary
    $getDaySummary = $huobi->queryMarket('detail', ['symbol' => 'btcusdt']);
    echo "Get the Last 24h Market Summary:\n";
    print_r($getDaySummary);

    // ------------------------------Market Data--------------------------------------->

    // <-----------------------------Account ------------------------------------------

    // Get all Accounts of the Current User
    $getAllAccount = $huobi->QueryPrivate('GET', 'v1/account/accounts');
    echo "Get all Accounts of the Current User:\n";
    print_r($getAllAccount);

    // Get Account Balance of a Specific Account
    $account_id = 60885837;
    $getAccountBalance = $huobi->QueryPrivate('GET', "v1/account/accounts/$account_id/balance");
    echo "Get Account Balance of a Specific Account:\n";
    print_r($getAccountBalance);

    // Get The Total Valuation of Platform Assets
    // Parameter: accountType, valuationCurrency
    $getTotalValuation = $huobi->QueryPrivate('GET', 'v2/account/valuation');
    echo "Get The Total Valuation of Platform Assets:\n";
    print_r($getTotalValuation);

    // Get Asset Valuation
    // Parameter: subUid, valuationCurrency
    $getAssetValuation = $huobi->QueryPrivate('GET', 'v2/account/asset-valuation', ['accountType' => 'spot']);
    echo "Get Asset Valuation:\n";
    print_r($getAssetValuation);

    // Asset Transfer
    $assetTransfer = $huobi->QueryPrivate('POST', 'v1/account/transfer', ['from-user' => '60885837', 'from-account-type' => 'spot', 'from-account' => '60885837', 'to-user' => '178911', 'to-account-type' => 'spot', 'to-account' => '178911', 'currency' => 'usdt', 'amount' => '10']);
    echo "Asset Transfer:\n";
    print_r($assetTransfer);

    // Get Account History
    // Parameter: currency, transact-types, start-time, end-time, sort, from-id, size  [1-500]
    $account_id = 60885837;
    $getAccountHistory = $huobi->QueryPrivate('GET', "v1/account/history", ['account-id' => "$account_id", 'currency' => 'usdt']);
    echo "Get Account Balance of a Specific Account:\n";
    print_r($getAccountHistory);

    // Get Account Ledger
    // Parameter: currency, transactTypes, startTime, endTime, sort, fromId, limit  [1-500]
    $account_id = 60885837;
    $getAccountLedger = $huobi->QueryPrivate('GET', "v2/account/ledger", ['accountId' => "$account_id", 'currency' => 'usdt']);
    echo "Get Account Ledger:\n";
    print_r($getAccountLedger);

    // Transfer Fund Between Spot Account and Future Contract Account
    $transferFund = $huobi->QueryPrivate('POST', "v1/futures/transfer", ['amount' => '10', 'currency' => 'usdt', 'type' => 'pro-to-futures']);
    echo "Transfer Fund Between Spot and Future:\n";
    print_r($transferFund);

    // Get Point Balance
    // Parameter: subUid
    $getPointBalance = $huobi->QueryPrivate('GET', 'v2/point/account');
    echo "Get Point Balance:\n";
    print_r($getPointBalance);

    // Point Transfer
    $pointTransfer = $huobi->QueryPrivate('POST', 'v2/point/transfer', ['fromUid' => '10', 'toUid' => '11', 'groupId' => '6', 'amount' => '10']);
    echo "Point Transfer:\n";
    print_r($pointTransfer);

    // -----------------------------------------------------Account-------------------------------------------->

    // <----------------------------------------------------Trading---------------------------------------------

    // Place a New Order
    // Parameter: price, source, client-order-id, self-match-prevent, stop-price, operator
    $account_id = 60885837;
    $placeNewOrder = $huobi->QueryPrivate('POST', 'v1/order/orders/place', ['account-id' => "$account_id", 'symbol' => 'btcusdt', 'type' => 'buy-limit', 'amount' => '10', 'price' => '7080.5', 'source' => 'spot-api']);
    echo "Place a New Order:\n";
    print_r($placeNewOrder);

    // Place a Batch of Orders
    // Parameter: price, source, client-order-id, self-match-prevent, stop-price, operator
    $account_id = 60885837;
    $orderData = [
        ['account-id' => "$account_id", 'symbol' => 'btcusdt', 'type' => 'buy-market', 'amount' => '10'],
        ['account-id' => "$account_id", 'symbol' => 'btcusdt', 'type' => 'buy-market', 'amount' => '20']
    ];
    $placeBatchOrder = $huobi->QueryPrivate('POST', 'v1/order/batch-orders', $orderData);
    echo "Place a Batch of Orders:\n";
    print_r($placeBatchOrder);

    // Submit Cancel for an Order
    // Parameter: symbol
    $order_id = 60885;
    $cancelOrder = $huobi->QueryPrivate('POST', "v1/order/orders/{$order_id}/submitcancel", ['order-id' => "$order_id"]);
    echo "Submit Cancel for an Order:\n";
    print_r($cancelOrder);

    // Submit Cancel for an Order (based on client order ID)
    $client_order_id = 60885;
    $cancelOrderID = $huobi->QueryPrivate('POST', "v1/order/orders/submitCancelClientOrder", ['client-order-id' => "$client_order_id"]);
    echo "Submit Cancel for an Order (based on client order ID):\n";
    print_r($cancelOrderID);

    // Get All Open Orders
    // Parameter: account-id, symbol, side, from, direct, size [1, 100]
    $account_id = 60885837;
    $getAllOrders = $huobi->QueryPrivate('GET', "v1/order/openOrders", ['account-id' => "$account_id"]);
    echo "Get All Open Orders:\n";
    print_r($getAllOrders);

    // Submit Cancel for Multiple Orders by Criteria
    // Parameter: account-id, symbol, types, side, size [1, 100]
    $account_id = '60885837';
    $batchCancelOpenOrders = $huobi->QueryPrivate('POST', "v1/order/orders/batchCancelOpenOrders", ['account-id' => $account_id, 'symbol' => 'btcusdt']);
    echo "Submit Cancel for Multiple Orders by Criteria:\n";
    print_r($batchCancelOpenOrders);

    // Submit Cancel for Multiple Orders by IDs
    // Parameter: client-order-ids, order-ids
    $client_order_ids = ['608', '85837'];
    $batchCancel = $huobi->QueryPrivate('POST', "v1/order/orders/batchcancel", ['client-order-ids' => $client_order_ids]);
    echo "Submit Cancel for Multiple Orders by IDs:\n";
    print_r($batchCancel);

    // Dead man’s switch
    $deadSwitch = $huobi->QueryPrivate('POST', "v2/algo-orders/cancel-all-after", ['timeout' => 0]);
    echo "Dead man’s switch:\n";
    print_r($deadSwitch);

    // Get the Order Detail of an Order
    $order_id = 60853;
    $getOrderDetails = $huobi->QueryPrivate('GET', "v1/order/orders/{$order_id}", ['order-id' => "$order_id"]);
    echo "Get the Order Detail of an Order:\n";
    print_r($getOrderDetails);

    // Get the Order Detail of an Order (based on client order ID)
    $client_order_id = 60885;
    $getClientOrder = $huobi->QueryPrivate('GET', "v1/order/orders/getClientOrder", ['clientOrderId' => "$client_order_id"]);
    echo "Get the Order Detail of an Order (based on client order ID):\n";
    print_r($getClientOrder);

    // Get the Match Result of an Order
    $order_id = 60853;
    $matchResults = $huobi->QueryPrivate('GET', "v1/order/orders/{$order_id}/matchresults", ['order-id' => "$order_id"]);
    echo "Get the Match Result of an Order:\n";
    print_r($matchResults);

    // Search Past Orders
    // Parameter: types, start-time, end-time, states, from, direct, size [1-100]
    $searchPastOrders = $huobi->QueryPrivate('GET', 'v1/order/orders', ['symbol' => 'btcusdt', 'states' => 'canceled']);
    echo "Search Past Orders:\n";
    print_r($searchPastOrders);

    // Search Historical Orders within 48 Hours
    // Parameter: symbol, start-time, end-time, direct, size [10-1000]
    $searchHistoricalOrders = $huobi->QueryPrivate('GET', 'v1/order/history', ['symbol' => 'btcusdt']);
    echo "Search Historical Orders within 48 Hours:\n";
    print_r($searchHistoricalOrders);

    // Search Match Results
    // Parameter: types, start-time, end-time, from, direct, size [1-500]
    $searchMatchResults = $huobi->QueryPrivate('GET', 'v1/order/matchresults', ['symbol' => 'btcusdt']);
    echo "Search Match Results:\n";
    print_r($searchMatchResults);

    // Get Current Fee Rate Applied to The User
    $getCurrentFeeRate = $huobi->QueryPrivate('GET', 'v2/reference/transact-fee-rate', ['symbols' => 'btcusdt']);
    echo "Get Current Fee Rate Applied to The User:\n";
    print_r($getCurrentFeeRate);

    // ----------------------------------------------------Trading--------------------------------------------->


} catch (HuobiAPIException $e) {
    echo 'API call failed: ' . $e->getMessage();
}

?>