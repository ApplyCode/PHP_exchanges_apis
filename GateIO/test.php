<?php

class GateAPIException extends \Exception
{
}

class GateAPI
{
    protected $key;
    protected $secret;
    protected $url;
    protected $version;

    protected $sslverify;
    protected $curl;

    public function __construct($key, $secret, $url = 'https://api.gateio.ws', $version = 'v4', $sslverify = true)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->url = rtrim($url, '/');
        $this->version = $version;
        $this->sslverify = $sslverify;
        $this->curl = curl_init();

        curl_setopt_array($this->curl, [
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Gate.io PHP API Agent',
            CURLOPT_RETURNTRANSFER => true
        ]);
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }

    private function nonce()
    {
        return time(); // Gate.io generally uses milliseconds since epoch
    }

    private function getServerTime()
    {
        $response = $this->queryPublic("spot/time");
        if (isset($response['server_time'])) {
            return (int) ($response['server_time'] / 1000);
        } else {
            throw new APIException("Unable to fetch server time");
        }
    }

    private function signature($httpMethod, $requestPath, $request, $timestamp, $patchData = null)
    {
        $method = strtoupper($httpMethod);
        $queryString = '';
        $data = '';
        print_r($patchData);

        if ($method == 'POST') {
            $data = json_encode($request);
            $hashedPayload = hash("sha512", $data);
        } elseif ($method == 'PATCH') {
            $data = json_encode($request);
            print_r($data);
            $hashedPayload = hash("sha512", $data);
            $queryString = http_build_query($patchData, '', '&');
        } else {
            $queryString = http_build_query($request, '', '&');
            $hashedPayload = hash("sha512", '');
        }
        $signature_string = sprintf(
            "%s\n%s\n%s\n%s\n%s",
            $method,
            $requestPath,
            $queryString,
            $hashedPayload,
            $timestamp
        );
        print_r($signature_string);
        return hash_hmac('sha512', $signature_string, $this->secret);
    }

    private function executeRequest($url, $headers = [], $bodyData = null, $httpMethod = 'GET')
    {
        curl_reset($this->curl);
        curl_setopt_array($this->curl, [
            CURLOPT_SSL_VERIFYPEER => $this->sslverify,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Gate.io PHP API Agent',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        switch (strtoupper($httpMethod)) {
            case 'POST':
                curl_setopt($this->curl, CURLOPT_POST, true);
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $bodyData);
                break;
            case 'GET':
                break;
            default:
                // For other methods (PUT, PATCH, DELETE), use CURLOPT_CUSTOMREQUEST
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $httpMethod);
                if ($bodyData !== null) {
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $bodyData);
                }
                break;
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

    public function queryPublic($method, array $request = [])
    {
        $url = $this->url . '/api/' . $this->version . '/' . $method;
        if (!empty($request)) {
            $params = http_build_query($request, '', '&');
            $url = $url . '?' . $params;
        }
        return $this->executeRequest($url);
    }

    public function queryPrivate($httpMethod = 'POST', $method, array $request = [], array $patchData = null)
    {
        $timestamp = (string) $this->getServerTime();
        $params = http_build_query($request, '', '&');
        $path = '/api/' . $this->version . '/' . $method;
        $signature = $this->signature($httpMethod, $path, $request, $timestamp, $patchData);
        $bodyData = null;
        $headers = [
            'Accept: ' . 'application/json',
            'Content-Type: ' . 'application/json',
            'KEY: ' . $this->key,
            'SIGN: ' . $signature,
            'Timestamp: ' . $timestamp
        ];
        switch (strtoupper($httpMethod)) {
            case 'GET':
                $url = $this->url . $path . '?' . $params;
                print_r($url);
                break;
            case 'POST':
                $bodyData = json_encode($request);
                print_r($bodyData);
                $url = $this->url . $path;
                break;
            case 'PATCH':
                $bodyData = json_encode($request);
                print_r($bodyData);
                $url = $this->url . $path . '?' . http_build_query($patchData, '', '&');
                print_r($url);
                break;
            case 'DELETE':
                $url = $this->url . $path . '?' . $params;
                $headers['Custom-Request-Type'] = 'DELETE';
                break;
            default:
                throw new APIException("Unsupported HTTP method: $httpMethod");
        }
        return $this->executeRequest($url, $headers, $bodyData, $httpMethod);
    }
}

$key = 'fe035dbaf38c20e5f05e240909f49727';
$secret = '9d56615c8e5baf4c1c5d7ba5945ce528d955b03020644b7bde904d7f3d480f26';
$gateApi = new GateAPI($key, $secret);

try {
    // // List chains supported for specified currency
    // $listChains = $gateApi->queryPublic('wallet/currency_chains', ['currency' => 'GT']);
    // echo "List chains supported for specified currency\n";
    // print_r($listChains);

    // // Generate currency deposit address
    // $depositAddress = $gateApi->queryPrivate('GET', 'wallet/deposit_address', ['currency' => 'USDT']);
    // echo "Generate currency deposit address\n";
    // print_r($depositAddress);

    // // Retrieve withdrawal records
    // // Parameter: currency, limit, from, to, offset
    // $withdrawalRecord = $gateApi->queryPrivate('GET', 'wallet/withdrawals', ['currency' => 'USDT']);
    // echo "Retrieve withdrawal records\n";
    // print_r($withdrawalRecord);

    // // Retrieve deposit records
    // // Parameter: currency, limit, from, to, offset
    // $depositRecord = $gateApi->queryPrivate('GET', 'wallet/deposits', ['currency' => 'USDT']);
    // echo "Retrieve deposit records\n";
    // print_r($depositRecord);

    // // Transfer between trading accounts
    // // Parameter: currency_pair, settle
    // $transfer = $gateApi->queryPrivate('POST', 'wallet/transfers', ["currency"=>"BTC", "from"=>"spot", "to"=>"margin", "amount"=>"1", "currency_pair" => "BTC_USDT"]);
    // echo "Transfer between trading accounts\n";
    // print_r($transfer);

    // // Retrieve withdrawal status
    // // Parameter: currency
    // $withdrawalStatus = $gateApi->queryPrivate('GET', 'wallet/withdraw_status', ['currency' => 'USDT']);
    // echo "Retrieve withdrawal status\n";
    // print_r($withdrawalStatus);

    // // Query saved address
    // // Parameter: chain, limit
    // $savedAddress = $gateApi->queryPrivate('GET', 'wallet/saved_address', ['currency' => 'USDT']);
    // echo "Query saved address\n";
    // print_r($savedAddress);

    // // Retrieve personal trading fee
    // // Parameter: currency_pair, settle
    // $personalTradingFee = $gateApi->queryPrivate('GET', 'wallet/fee', ['currency_pair' => 'ETH_USDT']);
    // echo "Retrieve personal trading fee\n";
    // print_r($personalTradingFee);

    // Retrieve user's total balances
    // Parameter: currency
    $totalBalance = $gateApi->queryPrivate('GET', 'wallet/total_balance');
    echo "Retrieve user's total balances\n";
    print_r($totalBalance);


    // <------------------------------Spot----------------
    // // List all currencies' details
    // $allCurrencys = $gateApi->queryPublic('spot/currencies');
    // echo "List all currencies' details\n";
    // print_r($allCurrencys);

    // // Get details of a specific currency
    // $specificCurrency = $gateApi->queryPublic('spot/currencies/USDT');
    // echo "Get details of a specific currency\n";
    // print_r($specificCurrency);

    // // List all currency pairs supported
    // $allCurrencyPairs = $gateApi->queryPublic('spot/currency_pairs');
    // echo "List all currency pairs supported\n";
    // print_r($allCurrencyPairs);

    // // Get details of a specifc currency pair
    // $specificCurrencyPair = $gateApi->queryPublic('spot/currency_pairs/ETH_BTC');
    // echo "Get details of a specifc currency pair\n";
    // print_r($specificCurrencyPair);

    // // Retrieve ticker information
    // // Parameter: timezone
    // $tickerInfo = $gateApi->queryPublic('spot/tickers', ['currency_pair' => 'BTC3L_USDT']);
    // echo "Retrieve ticker information\n";
    // print_r($tickerInfo);

    // // Retrieve order book
    // // Parameter: interval, limit, with_id
    // $orderBook = $gateApi->queryPublic('spot/order_book', ['currency_pair' => 'BTC_USDT']);
    // echo "Retrieve order book\n";
    // print_r($orderBook);

    // // Retrieve market trades
    // // Parameter: limit, last_id, reverse, from, to, page
    // $marketTrades = $gateApi->queryPublic('spot/trades', ['currency_pair' => 'BTC_USDT', 'limit' => '10']);
    // echo "Retrieve market trades\n";
    // print_r($marketTrades);

    // // Market candlesticks
    // // Parameter: limit, interval, from, to
    // $candlesticks = $gateApi->queryPublic('spot/candlesticks', ['currency_pair' => 'BTC_USDT', 'limit' => '10']);
    // echo "Market candlesticks\n";
    // print_r($candlesticks);

    // // Get server current time
    // $serverTime = $gateApi->queryPublic('spot/time');
    // echo "Get server current time\n";
    // print_r($serverTime);

    // // Query user trading fee rates
    // // Parameter: currency_pair
    // $tradingFee = $gateApi->queryPrivate('GET', 'spot/fee', ['currency_pair' => 'BTC_USDT']);
    // echo "Query user trading fee rates\n";
    // print_r($tradingFee);

    // // Query a batch of user trading fee rates
    // $batchTradingFee = $gateApi->queryPrivate('GET', 'spot/batch_fee', ['currency_pairs' => 'BTC_USDT']);
    // echo "Query a batch of user trading fee rates\n";
    // print_r($batchTradingFee);

    // // List spot accounts
    // // Parameter: currency
    // $spotAccounts = $gateApi->queryPrivate('GET', 'spot/accounts');
    // echo "List spot accounts\n";
    // print_r($spotAccounts);

    // // Query account book
    // // Parameter: currency, from, to, page, limit, type
    // $accountBook = $gateApi->queryPrivate('GET', 'spot/account_book');
    // echo "Query account book\n";
    // print_r($accountBook);

    // // Create a batch of orders
    // $orders = [
    //     [
    //         "text" => "t-123456",
    //         "currency_pair" => "ETH_BTC",
    //         "type" => "limit",
    //         "account" => "spot",
    //         "side" => "buy",
    //         "iceberg" => "0",
    //         "amount" => "1",
    //         "price" => "5.00032",
    //         "time_in_force" => "gtc",
    //         "auto_borrow" => false
    //     ]
    // ];
    // $createBatch = $gateApi->queryPrivate('POST', 'spot/batch_orders', $orders);
    // echo "Create a batch of orders\n";
    // print_r($createBatch);

    // // List all open orders
    // // Parameter: page, limit, account
    // $openOrders = $gateApi->queryPrivate('GET', 'spot/open_orders');
    // echo "List all open orders\n";
    // print_r($openOrders);

    // // close position when cross-currency is disabled
    // // Parameter: text, action_mode
    // $closePosition = $gateApi->queryPrivate('POST', 'spot/cross_liquidate_orders', ["currency_pair"=>"GT_USDT","amount"=>"12","price"=>"10.15","text"=>"t-34535"]);
    // echo "close position when cross-currency is disabled\n";
    // print_r($closePosition);

    // // Create an order
    // // Parameter: text, type, price, time_in_force, account, iceberg, auto_borrow, auto_repay, stp_act, action_mode
    // $createOrder = $gateApi->queryPrivate('POST', 'spot/orders', ["text"=>"t-123456","currency_pair"=>"ETH_BTC","type"=>"limit","account"=>"spot","side"=>"buy","iceberg"=>"0","amount"=>"1","price"=>"5.00032","time_in_force"=>"gtc","auto_borrow"=>false]);
    // echo "Create an order\n";
    // print_r($createOrder);

    // // List orders
    // // Parameter: page, limit, account, from, to, side
    // $listOrders = $gateApi->queryPrivate('GET', 'spot/orders', ['currency_pair' => 'BTC_USDT', 'status' => 'open']);
    // echo "List orders\n";
    // print_r($listOrders);

    // // Cancel all open orders in specified currency pair
    // // Parameter: side, account, action_mode
    // $cancelOrders = $gateApi->queryPrivate('DELETE', 'spot/orders', ["currency_pair"=>"GT_USDT"]);
    // echo "Cancel all open orders in specified currency pair\n";
    // print_r($cancelOrders);

    // // Cancel a batch of orders with an ID list
    // $cancelBatchID = $gateApi->queryPrivate('POST', 'spot/cancel_batch_orders', [["currency_pair"=>"GT_USDT", "id"=>"123456"]]);
    // echo "Cancel a batch of orders with an ID list\n";
    // print_r($cancelBatchID);

    // // Get a single order
    // // Parameter: account
    // $order_id = 11;
    // $getSingleOrder = $gateApi->queryPrivate('GET', 'spot/orders/'. $order_id, ['currency_pair' => 'BTC_USDT']);
    // echo "Get a single order\n";
    // print_r($getSingleOrder);

    // // Amend an order
    // // Parameter: account, action_mode, amount, price, amend_text
    // $order_id = 11;
    // $queryParams = ['currency_pair' => 'BTC_USDT']; // Example query parameters
    // $bodyData = [
    //     'price' => '14'
    // ];
    // $amendOrder = $gateApi->queryPrivate('PATCH', 'spot/orders/' . $order_id, $bodyData, $queryParams);
    // echo "Get a single order\n";
    // print_r($amendOrder);

    // // Cancel a single order
    // // Parameter: account, action_mode
    // $order_id = 1;
    // $cancelSingleOrder = $gateApi->queryPrivate('DELETE', 'spot/orders/' . $order_id, ["currency_pair"=>"GT_USDT"]);
    // echo "Cancel a single order\n";
    // print_r($cancelSingleOrder);

    // // List personal trading history
    // // Parameter: page, limit, account, from, to, currency_pair, order_id
    // $tradingHistory = $gateApi->queryPrivate('GET', 'spot/my_trades', ["currency_pair"=>"GT_USDT", "id"=>"123456"]);
    // echo "List personal trading history\n";
    // print_r($tradingHistory);

    // // Countdown cancel orders
    // // Parameter: currency_pair
    // $countdownCancel = $gateApi->queryPrivate('POST', 'spot/countdown_cancel_all', ["currency_pair"=>"GT_USDT", "timeout"=>"30"]);
    // echo "Countdown cancel orders\n";
    // print_r($countdownCancel);

    // // Batch modification of orders
    // $batchOrder = [
    //     [
    //         "order_id" => "121212",
    //         "currency_pair" => "BTC_USDT",
    //         "account" => "spot",
    //         "amount" => "1",
    //         "amend_text" => "test"
    //     ]
    // ];
    // $batchModification = $gateApi->queryPrivate('POST', 'spot/amend_batch_orders', $batchOrder);
    // echo "Batch modification of orders\n";
    // print_r($batchModification);

    // // Create a price-triggered order
    // // Parameter: type, time_in_force
    // $trigger = [
    //     "price" => "100",
    //     "rule" => ">=",
    //     "expiration" => 3600
    // ];
    // $put = [
    //     "type" => "limit",
    //     "side" => "buy",
    //     "price" => "2.15",
    //     "amount" => "2.00000000",
    //     "account" => "normal",
    //     "time_in_force" => "gtc"
    // ];
    // $creatPriceOrder = $gateApi->queryPrivate('POST', 'spot/price_orders', ["trigger" => $trigger, "put" => $put, "market" => "GT_USDT"]);
    // echo "Create a price-triggered order\n";
    // print_r($creatPriceOrder);

    // // Retrieve running auto order list
    // // Parameter: market, account, limit, offset, time_in_force
    // $runningList = $gateApi->queryPrivate('GET', 'spot/price_orders', ["status"=>"open"]);
    // echo "Retrieve running auto order list\n";
    // print_r($runningList);

    // // Cancel all open orders
    // // Parameter: market, account
    // $cancelAllOrders = $gateApi->queryPrivate('DELETE', 'spot/price_orders');
    // echo "Cancel all open orders\n";
    // print_r($cancelAllOrders);

    // // Get a price-triggered order
    // $order_id = '1223';
    // $getPriceOrder = $gateApi->queryPrivate('GET', 'spot/price_orders/' . $order_id);
    // echo "Get a price-triggered order\n";
    // print_r($getPriceOrder);

    // // cancel a price-triggered order
    // $order_id = 1223;
    // $cancelPriceOrder = $gateApi->queryPrivate('DELETE', 'spot/price_orders/' . $order_id);
    // echo "cancel a price-triggered order\n";
    // print_r($cancelPriceOrder);


    // For private endpoints, use $gateApi->queryPrivate(...)
} catch (GateAPIException $e) {
    echo "Error: " . $e->getMessage();
}
