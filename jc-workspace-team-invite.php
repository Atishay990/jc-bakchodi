<?
include '../api/database.php';
mysql_set_charset('utf8');

$data = json_decode(file_get_contents('php://input'), true);

$str     =  implode ("', '", $data['emails']);
$result = mysql_query("SELECT userid,fname,lname,email,company,managerid,companyid FROM users WHERE email IN ('". $str . "') ");

$res->teamId = $data['teamId'];
$users = array();

// echo json_encode($res);
// echo "hello world";
while($row  = mysql_fetch_assoc($result)){
    // echo json_encode($row);
    $role = 'user';
    if($row['managerid']==0){
        $role = 'admin';
    }
     
    $userdata = array(
        'userId' => $row['userid'],
        'userFirstName' => $row['fname'],
        'userLastName' => $row['lname'],
        'userEmail' => $row['email'],
        'userRole' => $role,
    );
    array_push($users,$userdata);
}
$res->users = $users;

$json = json_encode($res);
$queryParams = http_build_query($json);
$timestamp = time();
$toEncode = "v0:$timestamp:$json";
$signature = base64_encode(hash_hmac('sha256', $toEncode, '0$&*ex@1@XG0qP7X0hgdPPnALiXs4gH9KAFD',true));

$url = 'https://atolia-api-development-pr1588.osc-fr1.scalingo.io/auth/justcall-bulk-assert';
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




