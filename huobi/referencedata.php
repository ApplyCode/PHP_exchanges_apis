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
}

$key = 'xxx';
$secret = 'xxx';
$huobi = new HuobiAPI($key, $secret);

try {


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


} catch (HuobiAPIException $e) {
    echo 'API call failed: ' . $e->getMessage();
}

?>