try {
sleep(2);
$price_data = get_24hr_high_low_price($exchange_symbol, $market);
} catch (MexCAPIException $e) {
writeLOG(1, "API Call get_24hr_high_low_price('MEXC') Failed: $e->getMessage()\n");


$ohlcData = $mexc->QueryPublic("ticker/24hr", ["symbol" => $market]);
}