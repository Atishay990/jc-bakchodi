<?
mysql_set_charset('utf8');

function deleteUser($teamId,$userId){
    $res->teamId  = $teamId;
    $res->userIds = $userId;
    $json = json_encode($res);
    
    $timestamp = time();
    $toEncode = "v0:$timestamp:$json";
    $signature = base64_encode(hash_hmac('sha256', $toEncode, '0$&*ex@1@XG0qP7X0hgdPPnALiXs4gH9KAFD',true));
    
    $url = 'https://atolia-api-development-pr1588.osc-fr1.scalingo.io/auth/justcall-bulk-delete';
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json),
        "x-atolia-signature: $signature",
        "x-atolia-request-timestamp: $timestamp",
    
    ));
    
    $response = curl_exec($ch);
    if(curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
    } else {
        echo $response;
    }
    curl_close($ch);    
   

}

deleteUser(150544,array(1919481));
    

