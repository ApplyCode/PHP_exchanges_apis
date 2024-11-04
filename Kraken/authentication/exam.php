<?php

namespace Payward;

class KrakenAPIException extends \ErrorException {};

class KrakenAPI {
    protected $key;
    protected $secret;
    protected $url;
    protected $version;
    protected $sslverify;
    protected $curl;

    function __construct($key, $secret, $url = 'https://api.kraken.com', $version = '0', $sslverify = true) {
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

    function __destruct() {
        curl_close($this->curl);
    }

    private function executeRequest($url, $postdata = null, $headers = []) {
        // Reset cURL options
        curl_reset($this->curl);

        // Set common cURL options
        curl_setopt_array($this->curl, [
            CURLOPT_SSL_VERIFYPEER => $this->sslverify,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Kraken PHP API Agent',
            CURLOPT_RETURNTRANSFER => true
        ]);

        // Set URL and headers
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        // Determine if this is a POST request
        if ($postdata !== null) {
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postdata);
        }

        // Execute the request
        $result = curl_exec($this->curl);
        if ($result === false) {
            throw new KrakenAPIException('CURL error: ' . curl_error($this->curl));
        }

        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new KrakenAPIException('JSON decode error');
        }

        return $decoded;
    }

    public function QueryPublic($method, array $request = []) {
        $url = $this->url . $this->version . '/public/' . $method;
        if (!empty($request)) {
            // Use POST request for methods with parameters
            $postdata = http_build_query($request, '', '&');
            return $this->executeRequest($url, $postdata);
        } else {
            // Use GET request for methods without parameters
            return $this->executeRequest($url);
        }
    }

    public function QueryPrivate($method, array $request = []) {
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

        return $this->executeRequest($this->url . $path, $postdata, $headers);
    }
}

// Usage example
$key = 'YOUR API KEY';
$secret = 'YOUR API SECRET';
$kraken = new KrakenAPI($key, $secret);

try {
    // Get Recent Trades
    $recentTrades = $kraken->QueryPublic("Trades", ["pair" => "XXBTZUSD", "count" => 10, "since" => 1616663618]);
    echo "Recent Trades\n";
    print_r($recentTrades);

    // Get Server Time
    $serverTime = $kraken->QueryPublic('Time');
    echo "Server Time\n";
    print_r($serverTime);

    $accountBalance = $kraken->QueryPrivate("Balance");
    echo "Balance\n";
    print_r($accountBalance);
} catch (KrakenAPIException $e) {
    echo 'API call failed: ' . $e->getMessage();
}
?>
