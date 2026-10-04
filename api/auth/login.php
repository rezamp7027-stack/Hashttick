<?php
require_once '../../config.php'; header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['success'=>false,'error'=>'متد غیرمجاز'],JSON_UNESCAPED_UNICODE);exit;}
validateCsrf();
$email=strtolower(trim($_POST['username']??$_POST['email']??''));$password=$_POST['password']??'';
if(!filter_var($email,FILTER_VALIDATE_EMAIL)){echo json_encode(['success'=>false,'error'=>'برای ورود، ایمیل حساب را وارد کنید'],JSON_UNESCAPED_UNICODE);exit;}
if($password===''){echo json_encode(['success'=>false,'error'=>'رمز عبور را وارد کنید'],JSON_UNESCAPED_UNICODE);exit;}
try{$auth=sb_auth('password',['email'=>$email,'password'=>$password]);setAuthSession($auth);echo json_encode(['success'=>true,'message'=>'خوش آمدید '.(getUsername()?:$email),'user'=>['id'=>getUserId(),'username'=>getUsername(),'is_admin'=>isAdmin()]],JSON_UNESCAPED_UNICODE);}
catch(Throwable $e){
    $message=sb_error_message($e);
    http_response_code(str_contains(strtolower($e->getMessage()),'invalid login credentials')?401:400);
    echo json_encode(['success'=>false,'error'=>$message],JSON_UNESCAPED_UNICODE);
}
?>