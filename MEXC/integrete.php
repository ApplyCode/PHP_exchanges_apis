<?php

class APIException extends \ErrorException
{
}

class ExchangeAPI
{
    protected $key;
    protected $secret;
    protected $url;
    protected $version;
    protected $sslverify;
    protected $curl;
    protected $exchange;

    function __construct($key, $secret, $exchange, $url = 'https://api.kraken.com', $version = '0', $sslverify = true)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->exchange = $exchange;

        // Determine which URL and version to use based on the exchange
        if ($exchange === 'KRKN') {
            $this->url = rtrim($url, '/') . '/';
            $this->version = $version;
        } elseif ($exchange === 'MEXC') {
            // Set MEXC URL and version here
            $this->url = 'https://api.mexc.com';
            $this->version = 'v3';
        } else {
            throw new APIException('Unsupported exchange');
        }

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

    private function executeRequest($url, $postdata = null, $headers = [])
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

        if ($this->exchange === 'KRKN') {
            // Kraken specific code
            $url = $this->url . $this->version . '/public/' . $method;
            if (!empty($request)) {
                $postdata = http_build_query($request, '', '&');
                return $this->executeRequest($url, $postdata);

            } else {
                return $this->executeRequest($url);
            }
        } elseif ($this->exchange === 'MEXC') {
            // MEXC code for public API
            $url = $this->url . '/api/' . $this->version . '/' . $method;
            if (!empty($request)) {
                $postdata = http_build_query($request, '', '&');
                $url = $url . '?' . $postdata;
            }
            return $this->executeRequest($url);
        } else {
            throw new APIException('Unsupported exchange');
        }
    }

    public function QueryPrivate($method, array $request = [])
    {
        if ($this->exchange === 'KRKN') {
            // Kraken specific code for private API
            // ... existing Kraken private query implementation
        } elseif ($this->exchange === 'MEXC') {
            // MEXC specific code for private API
            if (!isset($request['timestamp'])) {
                $request['timestamp'] = time() * 1000;
            }

            $postdata = http_build_query($request, '', '&');
            $path = '/api/' . $this->version . '/' . $method;
            // Implement MEXC signature generation here
            $sign = hash_hmac('sha512', '/' . $path . hash('sha256', $request['nonce'] . $postdata, true), base64_decode($this->secret), true);
            $headers = [
                'X-MEXC-APIKEY: ' . $this->key,
                'X-MEXC-SIGNATURE: ' . $sign
            ];

            return $this->executeRequest($this->url . $path, $postdata, $headers);
        } else {
            throw new APIException('Unsupported exchange');
        }
    }
}

$key = 'api_key';
$secret = 'secret_key';
$exchange = 'MEXC'; // $exchange = 'KRKN'

$api = new ExchangeAPI($key, $secret, $exchange);

if ($exchange === 'MEXC') {

    try {
        // Example usage
        $exchangeInfo = $api->QueryPublic('exchangeInfo', ['symbol' => 'MXUSDT']);
        echo "Exchange Information:\n";
        print_r($exchangeInfo);

        //Order Book
        $orderBook = $api->QueryPublic("depth", ["symbol" => "BTCUSDT", "limit" => "3"]);
        echo "Order Book\n";
        print_r($orderBook);

        // // Add order example for MEXC
        // $orderResult = $mexc->QueryPrivate('order', [
        //     'symbol' => 'BTCUSDT',
        //     'side' => 'BUY',
        //     'type' => 'LIMIT',
        //     'quantity' => 1,
        //     'price' => 10000
        // ]);
        // echo "Order result:\n";
        // print_r($orderResult);

    } catch (APIException $e) {
        echo 'API call failed: ' . $e->getMessage();
    }
} else if ($exchange === 'KRKN') {
    try {

        //<------------------------Market Data----------------------------------->
        //Get Order Book Data
        //count: [ 1 .. 500 ]
        $orderBookData = $api->QueryPublic("Depth", ["pair" => "XXBTZUSD", "count" => 2]);
        echo "Order Book Data\n";
        print_r($orderBookData);

        //Get Ticker(Price) Information
        $ticker = $api->QueryPublic('Ticker', ['pair' => 'XXBTZUSD']);
        echo "The current price is:\n";
        print_r($ticker);
    } catch (APIException $e) {
        echo 'API call failed: ' . $e->getMessage();
    }
} else {
    throw new APIException('Unsupported exchange');
}


?>