function http_request($method = 'GET', $url, $headers = [], $data = null)
{
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);

    if ($method == 'POST') {
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }

    if ($method == 'DELETE') {
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    if(!empty($headers)) {
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    $output = curl_exec($curl);
    curl_close($curl);

    return $output;
} 

function signature($request_path='', $body='', $timestamp = false, $method='GET') {
    global $secret;

    $body = is_array($body) ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $body;
    $timestamp = $timestamp ? $timestamp : time();

    $what = $timestamp.$method.$request_path.$body;

    return base64_encode(hash_hmac("sha256", $what, $secret, true));
}
 