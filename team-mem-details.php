<?
// include '../../d/db.php';
// include 'database.php';
header('Content-Type: text/html; charset=UTF-8');

include 'f.php';
include 'api_header.php';

$start=0;
$length = 25;
$web = mysql_real_escape_string($_REQUEST['web']);
$start = mysql_real_escape_string($_REQUEST['start']);
$length = mysql_real_escape_string($_REQUEST['length']);

function aes_encrypt2_hash( $string, $action = 'e' ) {    
  $secret_key = '7f1bb08187b372b82c5723a6cfdde8cb315db9ab';
  $secret_iv = '7f1bb08187b372b82c5723a6cfdde8cb315db9ab';

  $output = false;
  $encrypt_method = "AES-256-CBC";
  $key = hash( 'sha256', $secret_key );
  $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

  if( $action == 'e' ) {
    $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
  }
  else if( $action == 'd' ){
    $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
  }

  return $output;
}


if($web==1){
  header('Access-Control-Allow-Origin: '.$baseurlwslash);

  header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");
  header('X-Content-Type-Options: nosniff');
}


include 'encoding/encoding/vendor/autoload.php';
use \ForceUTF8\Encoding;

$columns = array( 
// datatable column index  => database column name
  0 => 'u.fname',  
  1 => 'u.fname',  
);


mysql_set_charset('utf8',$conn);
mysql_set_charset('utf8',$conn_write);




//Check if user exists [Starts HERE]
if($_COOKIE['login_pass']!='') {
  $checkauthbig=$_COOKIE["login_pass"]; 
  $checkauth=mysql_real_escape_string($checkauthbig);
  $brkauth=explode("-",$checkauth);
  $hash=mysql_real_escape_string($brkauth[1]);

  $getuserinfo=mysql_query("SELECT u.userid,u.managerid,ua.analytics,ua.team_logs,ua.admin FROM users u, user_access ua WHERE u.hash='$hash' AND u.status='0' AND u.userid=ua.userid ",$conn);
  $n_userinfo=mysql_num_rows($getuserinfo);

  if ($n_userinfo==0) {
    $result['status'] = "fail";
    $result_json=json_encode($result);
    echo $result_json;
    exit();
  }
}else{
  $result['status'] = "fail";
  $result_json=json_encode($result);
  echo $result_json;

  exit();
}
//Check if user exists [ENDS HERE]
$team_logs = 0;
$admin =0;
$userdata = mysql_fetch_array($getuserinfo);
$userid = $userdata["userid"];
$managerid = $userdata["managerid"];
$analytics = $userdata["analytics"];
$team_logs = $userdata["team_logs"];
$admin = $userdata["admin"];
$analytics_ua = $userdata["analytics"];
$is_manager=0;
if($managerid==0){
  $manid_f=$userid;
  $is_manager=1;
}else{
  $manid_f=$managerid;
}

if ($managerid==0 || $admin==1) {
  $team_logs=1;
}

if ($analytics==1) {
  $team_logs=1;
}



$result['status'] = "success";

//Teammate
if ($managerid != 0) {
  $manid=$managerid;
}else{
  $manid=$userid;
}

if($manid==0){
  $result['status'] = "fail";
  $result_json=json_encode($result);
  echo $result_json;
  exit();
}

$filter_val=explode("-",mysql_real_escape_string($_REQUEST['search']['value']));
$search_show_all=0;
$search_show_invited=0;
$search_show_iteammember=0;
$search_show_admin=0;
$search_show_owner=0;

if($filter_val[1]=="filter"){


  if($filter_val[0]=="all"){
    $search_val="";
    $search_show_all=1;
  }else if($filter_val[0]=="invited"){
    $search_show_invited=1;

  }else if($filter_val[0]=="teammember"){
    $search_show_iteammember=1;

  }else if($filter_val[0]=="admin"){
    $search_show_admin=1;

  }else{
    $search_show_all=1;
  }

}

if($_REQUEST['order'][0]['column']==1){

  $sort_value_dir=$_REQUEST['order'][0]['dir'];
  $clmn_name=$columns[$_REQUEST['order'][0]['column']];
}else{

  $sort_value_dir="ASC";
  $clmn_name="u.joindate";

}

if(!empty(mysql_real_escape_string($_REQUEST['search']['value'])) || $search_show_all==1){
  $search_val = mysql_real_escape_string($_REQUEST['search']['value']);


  if($search_show_all==1){
    $search_val="";
  }

  if(strpos($search_val," ")!=false){
    $explode_val = explode(" ",$search_val);
    $phla_name = $explode_val[0];
    $dusra_name = $explode_val[1];
    $sql = mysql_query("SELECT u.userid,u.managerid,u.fname,u.lname,u.inviteid,u.hash,u.email,u.ext,ua.admin FROM users u, user_access ua WHERE (u.userid='$manid' OR u.managerid='$manid') AND u.status in (0,3,4) AND u.userid=ua.userid AND u.fname LIKE '%$phla_name%' AND u.lname LIKE '%$dusra_name%' ORDER BY ".$clmn_name." ".$sort_value_dir." Limit ".$start.",".$length."",$conn);

    $sql1=mysql_query("SELECT count(u.userid) as total FROM users u, user_access ua WHERE (u.userid='$manid' OR u.managerid='$manid') AND u.status in (0,3,4) AND u.userid=ua.userid AND u.fname LIKE '%$phla_name%' AND u.lname LIKE '%$dusra_name%' ORDER BY ".$clmn_name." ".$sort_value_dir."",$conn);
    // echo mysql_error($conn);
  }else{

    $sql = mysql_query("SELECT u.userid,u.managerid,u.fname,u.lname,u.inviteid,u.hash,u.email,u.ext,ua.admin,u.status FROM users u, user_access ua WHERE (u.userid='$manid' OR u.managerid='$manid') AND u.status in (0,3,4) AND u.userid=ua.userid AND (u.fname LIKE '%$search_val%' OR u.lname LIKE '%$search_val%' OR u.email LIKE '%$search_val%' ) ORDER BY ".$clmn_name." ".$sort_value_dir." Limit ".$start.",".$length."",$conn);

    $sql1=mysql_query("SELECT count(u.userid) as total FROM users u, user_access ua WHERE (u.userid='$manid' OR u.managerid='$manid') AND u.status in (0,3,4) AND u.userid=ua.userid AND (u.fname LIKE '%$search_val%' OR u.lname LIKE '%$search_val%' OR u.email LIKE '%$search_val%' ) ORDER BY ".$clmn_name." ".$sort_value_dir."",$conn);
    // echo mysql_error($conn);

  }

  if($search_show_invited==1){

    $sql=mysql_query("SELECT u.userid,u.managerid,u.fname,u.lname,u.inviteid,u.hash,u.email,u.ext,ua.admin FROM users u, user_access ua WHERE (u.userid='$manid' OR u.managerid='$manid') AND u.status=0 AND u.inviteid!=0 AND u.userid=ua.userid ORDER BY ".$clmn_name." ".$sort_value_dir." Limit ".$start.",".$length."",$conn);

    $sql1=mysql_query("SELECT count(u.userid) as total FROM users u, user_access ua WHERE (u.userid='$manid' OR u.managerid='$manid') AND u.status=0 AND u.inviteid!=0 AND u.userid=ua.userid ORDER BY ".$clmn_name." ".$sort_value_dir."",$conn);


  }

  if($search_show_admin==1){


    $sql=mysql_query("SELECT u.userid,u.managerid,u.fname,u.lname,u.inviteid,u.hash,u.email,u.ext,ua.admin FROM users u, user_access ua WHERE (u.userid='$manid' OR u.managerid='$manid') AND u.managerid!=0 AND u.status=0 AND  ua.admin=1 AND  u.userid=ua.userid ORDER BY ".$clmn_name." ".$sort_value_dir." Limit ".$start.",".$length."",$conn);

    $sql1=mysql_query("SELECT count(u.userid) as total FROM users u, user_access ua WHERE (u.userid='$manid' OR u.managerid='$manid') AND u.managerid!=0  AND u.status=0 AND ua.admin=1 AND u.userid=ua.userid ORDER BY ".$clmn_name." ".$sort_value_dir."",$conn);


  }

  if($search_show_iteammember==1){


    $sql=mysql_query("SELECT u.userid,u.managerid,u.fname,u.lname,u.inviteid,u.hash,u.email,u.ext,ua.admin FROM users u, user_access ua WHERE (u.userid='$manid' OR u.managerid='$manid') AND u.managerid!=0 AND u.status=0 AND  ua.admin=0 AND  u.userid=ua.userid ORDER BY ".$clmn_name." ".$sort_value_dir." Limit ".$start.",".$length."",$conn);

    $sql1=mysql_query("SELECT count(u.userid) as total FROM users u, user_access ua WHERE (u.userid='$manid' OR u.managerid='$manid') AND u.managerid!=0 AND u.status=0 AND ua.admin=0 AND u.userid=ua.userid ORDER BY ".$clmn_name." ".$sort_value_dir."",$conn);


  }




}else{


  $sql=mysql_query("SELECT u.userid,u.managerid,u.fname,u.lname,u.inviteid,u.hash,u.email,u.ext,ua.admin,u.status FROM users u, user_access ua WHERE (u.userid='$manid' OR u.managerid='$manid') AND u.status in (0,3,4) AND u.userid=ua.userid ORDER BY ".$clmn_name." ".$sort_value_dir." Limit ".$start.",".$length."",$conn);


  // echo "SELECT u.userid,u.managerid,u.fname,u.lname,u.inviteid,u.hash,u.email,u.ext,ua.admin FROM users u, user_access ua WHERE (u.userid='$manid' OR u.managerid='$manid') AND u.status=0 AND u.userid=ua.userid ORDER BY ".$clmn_name." ".$sort_value_dir." Limit ".$start.",".$length."";

  // exit;

  $sql1=mysql_query("SELECT count(u.userid) as total FROM users u, user_access ua WHERE (u.userid='$manid' OR u.managerid='$manid') AND u.status in (0,3,4) AND u.userid=ua.userid ORDER BY ".$clmn_name." ".$sort_value_dir."",$conn);
  // echo mysql_error($conn);

}

//manager_hash

$manage_hash="";
$user_admin=0;
$select_manager=mysql_query("SELECT hash from users where userid='$manid_f' and status=0");

if(mysql_num_rows($select_manager)>0){

  $resto=mysql_fetch_array($select_manager);
  // echo $resto["hash"];

  // exit;

  $manage_hash=aes_encrypt2_hash($resto["hash"],'e');


  $user_access_admin=mysql_query("SELECT admin FROM user_access where userid='$userid'");

  if(mysql_num_rows($user_access_admin)>0){

    $rest=mysql_fetch_array($user_access_admin);
    $user_admin=$rest['admin'];

  }



}

if($managerid==0){

  $user_admin=1;


}





// echo mysql_error($conn);
$fetching_count = mysql_fetch_array($sql1);

$total_n = $fetching_count['total'];



$n=mysql_num_rows($sql);
$i=0;
if($n>0){
  $nesteddata1 = array();
  $result["count"]=$n;
  $k = 0;
  while($data=mysql_fetch_array($sql)){

    // if($manage_hash!=""){
    //   $nesteddata1[$k][] = '<div style="display:none" manager_hash="'.$manage_hash.'"></div>';
    // }






    $name_for_avatar = $result["data"][$i]['name']=ucfirst($data['fname']." ".$data['lname']);
    
    if($manid==7417||$manid==18548) {

     $name_for_avatar = $result["data"][$i]['name'] = Encoding::toLatin1(htmlspecialchars($result["data"][$i]['name']));
   }
   $result["data"][$i]['hash']=$data["hash"];

   $result["data"][$i]['hash_enc']=aes_encrypt2_hash($data["hash"],'e');

   $email_f = $result["data"][$i]['email']=htmlspecialchars($data["email"]);
   $ext_val = $result["data"][$i]['ext']=intval($data["ext"]);
   $result["data"][$i]['managerid']=intval($data["managerid"]);


   $u_userid=$data["userid"];
   $u_managerid=$data["managerid"];

   if ($u_managerid==0) {
    $data["admin"]=1;
  }

  $current_user_status = $data['status'];

  $admin_check = $result["data"][$i]['admin']=intval($data["admin"]);
  $result["data"][$i]['availability']=user_availability($data["hash"],$conn);

  $inviteid=$data["inviteid"];

  if ($inviteid==0) {
    $result["data"][$i]['active']=1;
    $result["data"][$i]['invitation_link']="";

  }else{
    $invitelink = "https://justcall.io/invitation.php?code=".$inviteid."&email=".$data["email"];
    $result["data"][$i]['active']=0;
    $result["data"][$i]['invitation_link']=htmlspecialchars($invitelink);
  }


  $result["data"][$i]['analytics']=1;
  if ($team_logs==0) {
      // if ($u_userid==$userid) {
        // $result["data"][$i]['analytics']=1;
      // }else{
    $result["data"][$i]['analytics']=0;
      // }
  }

  if ($manid==7791) {
    if ($userid!=8650) {
      if ($u_userid==8650) {
        $result["data"][$i]['analytics']=0;
      }
    }
  }

  if ($manid==28026) {
    if ($userid!=28026) {
        // if ($u_userid==8650) {
      $result["data"][$i]['analytics']=0;
        // }
    }
  }

    /////


  
    // $nesteddata1[$k][] = '';  



  
    // $nesteddata1[$k][] = '<span data-link='.$result["data"][$i]['hash_enc'].' class="hash_for"></span>';
  






// echo $append_val;
  // echo $name_for_avatar;
  
  // echo $fullname;
  // exit;
  if($admin_check==1){


    $fullname = strtoupper(htmlspecialchars($data['fname'][0])).''.strtoupper(htmlspecialchars($data['lname'][0]));
    if($fullname==''){

      $append_val = "<span class='mdi mdi-account'></span>";
    }else{
      $append_val = strtoupper(htmlspecialchars($name_for_avatar[0])).''.strtoupper(htmlspecialchars($data['lname'][0]));
    }
    // float:right; margin-right:6px;
    if($inviteid==0){
      $nesteddata1[$k][] = '<div style=" float:left; margin-left:7px; "><div style="display: flex; flex-direction: row;">
      <span email-link="'.$result["data"][$i]['email'].'" data-link="'.$result["data"][$i]['hash_enc'].'" class="hash_for"><div style="position:absolute;width: 11px;height: 11px;border-radius: 50%;border: 2px solid white;">
      <svg fill="currentColor" viewBox="0 0 20 20" style="width: 13px; margin-left: -4px; margin-top: -2px; border-radius: 5px; display: initial; display: block; fill: #fca629; background: white; border: 1px solid #fca729; "><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
      </span>
      </div>
      <div class="avatar-circle" style="width: 35px; height: 35px; background-color:dodgerblue; text-align: center; border-radius: 50%; -webkit-border-radius: 50%; -moz-border-radius: 50%;">
      <span class="initials" data-invited="No" style="position: relative; top: 0px; /* 25% of parent */ font-size: 13px; /* 50% of parent */ line-height: 35px; /* 50% of parent */ color: #fff; ">'.$append_val.'</span></div><div>'; 
    }else{
      $nesteddata1[$k][] = '<div style=" float:left; margin-left:7px; "><div style="display: flex; flex-direction: row;">
      <span email-link="'.$result["data"][$i]['email'].'"  data-link="'.$result["data"][$i]['hash_enc'].'" class="hash_for">
      </span>
      </div>
      <div class="avatar-circle" style="width: 35px; height: 35px; background-color:dodgerblue; text-align: center; border-radius: 50%; -webkit-border-radius: 50%; -moz-border-radius: 50%;">
      <span class="initials" data-invited="No" style="position: relative; top: 0px; /* 25% of parent */ font-size: 13px; /* 50% of parent */ line-height: 35px; /* 50% of parent */ color: #fff; ">'.$append_val.'</span></div><div>';  
    } 



    // <div style="position:absolute;width: 11px;height: 11px;border-radius: 50%;border: 2px solid white;background-color: #ffffff;">
    //   <div style="width: 11px;margin-top: -5px;margin-left: -2px;border-radius: 5px;color: #f5a640;"><svg fill="currentColor" viewBox="0 0 20 20" style="
    //   /* background: #f4a440; */
    //   /* border-radius: 50%; */
    //   /* border-radius: 5px; */
    //   "><path fill-rule="evenodd" d="M2.94 6.412A2 2 0 002 8.108V16a2 2 0 002 2h12a2 2 0 002-2V8.108a2 2 0 00-.94-1.696l-6-3.75a2 2 0 00-2.12 0l-6 3.75zm2.615 2.423a1 1 0 10-1.11 1.664l5 3.333a1 1 0 001.11 0l5-3.333a1 1 0 00-1.11-1.664L10 11.798 5.555 8.835z" clip-rule="evenodd"></path></svg></div>
    //   </div>
  }else{
    $fullname = strtoupper(htmlspecialchars($data['fname'][0])).''.strtoupper(htmlspecialchars($data['lname'][0]));
    if($fullname==''){
      $append_val = "<span class='mdi mdi-account'></span>";

    }else{
      $append_val = strtoupper(htmlspecialchars($name_for_avatar[0])).''.strtoupper(htmlspecialchars($data['lname'][0]));
    }
    // echo $result["data"][$i]['hash_enc'];
    if($inviteid==0){
      $nesteddata1[$k][] = '<div style="float:left;margin-left:7px;  "><div style="display: flex; flex-direction: row; align-items: center;"><span email-link="'.$result["data"][$i]['email'].'" data-link="'.$result["data"][$i]['hash_enc'].'" class="hash_for"></span><div class="avatar-circle" style="width: 35px; height: 35px; background-color:dodgerblue; text-align: center; border-radius: 50%; -webkit-border-radius: 50%; -moz-border-radius: 50%;margin-right: 6px;"> <span class="initials" data-invited="No" style="position: relative; top: 0px; /* 25% of parent */ font-size: 13px; /* 50% of parent */ line-height: 35px; /* 50% of parent */ color: #fff; ">'.$append_val.'
      </span>
      </div>
      </div></div>';  
    }else{
      $nesteddata1[$k][] = '<div style=" float:left; margin-left:7px; "><div style="display: flex; flex-direction: row;">
      <span  email-link="'.$result["data"][$i]['email'].'" data-link="'.$result["data"][$i]['hash_enc'].'" class="hash_for">
      </span>
      </div>
      <div class="avatar-circle" style="width: 35px; height: 35px; background-color:dodgerblue; text-align: center; border-radius: 50%; -webkit-border-radius: 50%; -moz-border-radius: 50%;">
      <span class="initials" data-invited="No" style="position: relative; top: 0px; /* 25% of parent */ font-size: 13px; /* 50% of parent */ line-height: 35px; /* 50% of parent */ color: #fff; ">'.$append_val.'</span></div><div>';  
    }
    
    // <div style="position:absolute;width: 11px;height: 11px;border-radius: 50%;border: 2px solid white;background-color: #ffffff;">
    //   <div style="width: 11px;margin-top: -5px;margin-left: -2px;border-radius: 5px;color: #f5a640;"><svg fill="currentColor" viewBox="0 0 20 20" style="
    //   /* background: #f4a440; */
    //   /* border-radius: 50%; */
    //   /* border-radius: 5px; */
    //   "><path fill-rule="evenodd" d="M2.94 6.412A2 2 0 002 8.108V16a2 2 0 002 2h12a2 2 0 002-2V8.108a2 2 0 00-.94-1.696l-6-3.75a2 2 0 00-2.12 0l-6 3.75zm2.615 2.423a1 1 0 10-1.11 1.664l5 3.333a1 1 0 001.11 0l5-3.333a1 1 0 00-1.11-1.664L10 11.798 5.555 8.835z" clip-rule="evenodd"></path></svg></div>
    //   </div>
  }
  $fullname = strtoupper(htmlspecialchars($data['fname'][0])).''.strtoupper(htmlspecialchars($data['lname'][0]));
  if($fullname==''){
    $nesteddata1[$k][] = "<span style='color: #161e2e;' data-link=".$u_managerid." class='name_tbl'></span><div class='emailhover' style='color: #161e2e;' data-toggle='tooltip' data-original-title='click to copy' data-placement='top' data-link='".$email_f."' onclick='copyemail(this)'>".$email_f."</div>";
  }else{
    $fname_new = str_replace(' ','_', $data['fname']);
    $lname_new = str_replace(' ','_', $data['lname']);
    $nesteddata1[$k][] = "<span style='color: #161e2e;' data-link='".$u_managerid."' data-fname='".$fname_new."' data-lname='".$lname_new."' class='name_tbl'>".$name_for_avatar."</span><br><div class='emailhover' data-toggle='tooltip' data-original-title='click to copy' data-placement='top' data-link='".$email_f."' onclick='copyemail(this)' style='color: #6b7280;'>".$email_f."</div>";
  }
  if($ext_val==-1||$ext_val==null){
    $nesteddata1[$k][] = '<div style="text-align: center;"><span class="label label-success" style="text-align: center;background: lightgrey;color: gray;" id="ext_hover">Not set</span></div>';

  }else{
    $nesteddata1[$k][] = "<div style='text-align: center;'><span class='label label-success' style='text-align: center;' id='ext_hover'>Ext ".$ext_val."</span></div>";

  }
  


  if($u_managerid==0 && $admin_check==1){
    if($current_user_status==3){
      $nesteddata1[$k][] = '<div style="color: #e88585; text-align: center;" data-link="'.$result["data"][$i]['hash_enc'].'">Locked</div>';

    }else if($current_user_status==4){
      $nesteddata1[$k][] = '<div style="color: #e88585; text-align: center;" data-link="'.$result["data"][$i]['hash_enc'].'">Locked</div>';

    }else{
      $nesteddata1[$k][] = '<div style="color: #6b7280; text-align: center;" id="ownerkiid" data-link="'.$result["data"][$i]['hash_enc'].'">Account Owner</div>';
    }
  }else if($u_managerid!=0 && $admin_check==1){
    if($inviteid==0){
      if($current_user_status==3){
        $nesteddata1[$k][] = '<div style="color: #e88585; text-align: center;" data-link="'.$result["data"][$i]['hash_enc'].'">Locked</div>';

      }else if($current_user_status==4){
        $nesteddata1[$k][] = '<div style="color: #e88585; text-align: center;" data-link="'.$result["data"][$i]['hash_enc'].'">Locked</div>';

      }else{
        $nesteddata1[$k][] = '<div style="color: #6b7280; text-align: center;" data-link="'.$result["data"][$i]['hash_enc'].'">Admin</div>';

      }
    }else{
      $nesteddata1[$k][] = '<div style="color: #f99c58; text-align: center;" data-link="'.$result["data"][$i]['hash_enc'].'">Invited</div>';
    }
  }else{
   if($inviteid==0){
    if($current_user_status==3){
      $nesteddata1[$k][] = '<div style="color: #e88585; text-align: center;" data-link="'.$result["data"][$i]['hash_enc'].'">Locked</div>';

    }else if($current_user_status==4){
      $nesteddata1[$k][] = '<div style="color: #e88585; text-align: center;" data-link="'.$result["data"][$i]['hash_enc'].'">Locked</div>';

    }else{

      $nesteddata1[$k][] = '<div style="color: #6b7280; text-align: center;" data-link="'.$result["data"][$i]['hash_enc'].'">Team Member</div>';

    }
  }else{
    $nesteddata1[$k][] = '<div style="color: #f99c58; text-align: center;" data-link="'.$result["data"][$i]['hash_enc'].'">Invited</div>';
  }
}
#757c89;

if($managerid==0){

  if($u_managerid==0){

    $edit_span = "<div style='display:flex;' id='ownerkiid2'><span class='seeforadd' data-link='".$result["data"][$i]['hash_enc']."+adminhai123' ><span style='margin-left:20px;margin-right: 10px;color:#757c89; ' id='editidbtn' class='hover-btn' onclick='opensidebar(this)' >Edit</span></span>";
  }else{    

    if($current_user_status==3){
     $edit_span = "";


   }else if($current_user_status==4){
     $edit_span = "";

   }else{


    $edit_span = "<div style='display:flex;' id='ownerkiid3'><span class='seeforadd' data-link='".$result["data"][$i]['hash_enc']."+adminhai123' ><span style='margin-left:20px;margin-right: 10px;color:#757c89; ' class='hover-btn' onclick='opensidebar(this)' >Edit</span></span>";

  }

}
}else if($admin==1 && $managerid!=0){
  if($u_managerid==0){
    $edit_span = "";
  }else{    
    if($current_user_status==3){
      $edit_span = "";
    }else if($current_user_status==4){
      $edit_span = "";
    }else{
      $edit_span = "<div style='display:flex;' id='ownerkiid3'><span class='seeforadd' data-link='".$result["data"][$i]['hash_enc']."+adminhai123' ><span style='margin-left:20px;margin-right: 10px;color:#757c89; ' class='hover-btn' onclick='opensidebar(this)' >Edit</span></span>";
    }
  }
}else{
  $edit_span = '';
    // $margin_variable = 1; 
}

$edit_span .= '';
if($result["data"][$i]['active']!=1){
 if($managerid==0||$admin==1){

  $edit_span .= "<div style='display:flex'> <div><button data-member-hash=".$result["data"][$i]['hash_enc']."  editbtn' type='button'  onclick='resendinvite(this)' class='hover-btn' style='color:#757c89;background:transparent;border: none;border-left: 1px solid #868383; padding-top: 0;padding-bottom: 0; padding-left:10px; outline:none !important;' onmouseout='hide(this)' onmouseover='unhide(this)' >Resend Invite</button></div><span id='resend".$k."' onmouseover='unhide1(this)' onmouseout='hide1(this)' class='mdi mdi-copy resend' style='color:lightslategray; visibility:hidden; margin-top:2px;'  onclick='copylink(this)' data-link ='".$result["data"][$i]['invitation_link']."' data-toggle='tooltip' data-original-title='copy link'></span></div></div>";
}else if($analytics_ua==1||$team_logs==1){
  $edit_span .= "<div style='display:flex'><div><button data-member-hash=".$result["data"][$i]['hash_enc']."  editbtn' type='button' class='hover-btn' onclick='resendinvite(this)' onmouseout='hide(this)' onmouseover='unhide(this)' style='color:#757c89;background:transparent;border: none; margin-left:25px; outline:none !important;'>Resend Invite</button></div><span id='resend".$k."' onmouseover='unhide1(this)' onmouseout='hide1(this)' class='mdi mdi-copy resend' style='color:lightslategray; visibility:hidden; margin-top:2px;' onclick='copylink(this)' data-link ='".$result["data"][$i]['invitation_link']."' data-toggle='tooltip' data-original-title='copy link'></span></div></div>";
}

}else{
  if($managerid==0){
    if($u_managerid==0){
      if($current_user_status==3){
        $edit_span .="";


      }else if($current_user_status==4){
        $edit_span .="";

      }else{
        $edit_span .= '<form method="post" action="team-members-analytics.php"><input type="hidden" name="memberHash" value="'.$result["data"][$i]['hash_enc'].'"><button type="submit" class="hover-btn" id="analyticsbtnstyle" style="color:#757c89;background:transparent;border: none;border-left: 1px solid #868383;padding-top: 0;padding-bottom: 0;padding-left:10px; outline:none !important;">Analytics</button></form></div>';

      }
    }else {
      if($current_user_status==3){
        $edit_span .="";


      }else if($current_user_status==4){
        $edit_span .="";

      }else{
        $edit_span .= '<form method="post" action="team-members-analytics.php"><input type="hidden" name="memberHash" value="'.$result["data"][$i]['hash_enc'].'"><button type="submit" class="hover-btn" id="analyticsbtnstyle" style="color:#757c89;background:transparent;border: none;border-left: 1px solid #868383;padding-top: 0;padding-bottom: 0;padding-left:10px; outline:none !important;">Analytics</button></form></div>';
      }

    }
  }else if($admin==1 && $managerid!=0){
    if($u_managerid==0){
      if($current_user_status==3){
        $edit_span .="";


      }else if($current_user_status==4){
        $edit_span .="";

      }else{
        $edit_span .= '<form method="post" action="team-members-analytics.php"><input type="hidden" name="memberHash" value="'.$result["data"][$i]['hash_enc'].'"><button type="submit" class="hover-btn" id="analyticsbtnstyle" style="color:#757c89;background:transparent;border: none;padding-top: 0;padding-bottom: 0;padding-left:27px; outline:none !important;">Analytics</button></form></div>';
      }

    }else{
      if($current_user_status==3){
        $edit_span .="";


      }else if($current_user_status==4){
        $edit_span .="";

      }else{
        $edit_span .= '<form method="post" action="team-members-analytics.php"><input type="hidden" name="memberHash" value="'.$result["data"][$i]['hash_enc'].'"><button type="submit" class="hover-btn" id="analyticsbtnstyle" style="color:#757c89;background:transparent;border: none;border-left: 1px solid #868383;padding-top: 0;padding-bottom: 0;padding-left:10px; outline:none !important;">Analytics</button></form></div>';
      }

    }

  }
  else if($analytics_ua==1||$team_logs==1){
    if($current_user_status==3){
      $edit_span .="";


    }else if($current_user_status==4){
      $edit_span .="";

    }else{
      $edit_span .= '<form method="post" action="team-members-analytics.php"><input type="hidden" name="memberHash" value="'.$result["data"][$i]['hash_enc'].'"><button  type="submit" class="hover-btn" style="color:#757c89; background:transparent;border: none;margin-left:25px; outline:none !important;
      ">Analytics</button></form></div>';
    }
  }

}

$nesteddata1[$k][] = $edit_span;



$k++; 
$i++;
}

}else{
  $result["count"]=0;
  $result["error"]="No member found";
}

$check_count = count($nesteddata1);


if($check_count>0){

}else{
  $nesteddata1 = array();
}

$nesteddata["recordsFiltered"] = intval($total_n);
$nesteddata['recordsTotal'] = intval($total_n);
$nesteddata['data'] = $nesteddata1;
$nesteddata['draw']= intval($_REQUEST['draw']);
$nesteddata['team_logs'] = $team_logs;
$nesteddata['analytics'] = $analytics_ua;
$nesteddata['manage_hash'] = $manage_hash;
$nesteddata['user_admin'] = $user_admin;
$nesteddata['manager'] = $is_manager;





$result_json = json_encode($nesteddata);

 // if($userid=="84126" || $managerid=="84126"){

 //    print_r($nesteddata);

 //  }
echo $result_json; 

?>


