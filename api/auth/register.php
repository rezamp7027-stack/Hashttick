<?php
require_once '../../config.php'; header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['success'=>false,'error'=>'متد غیرمجاز'],JSON_UNESCAPED_UNICODE);exit;}
$username=trim($_POST['username']??'');$email=strtolower(trim($_POST['email']??''));$password=$_POST['password']??'';
if(!preg_match('/^[\p{L}\p{N}_.-]{3,32}$/u',$username)){echo json_encode(['success'=>false,'error'=>'نام کاربری باید ۳ تا ۳۲ کاراکتر باشد'],JSON_UNESCAPED_UNICODE);exit;}
if(!filter_var($email,FILTER_VALIDATE_EMAIL)){echo json_encode(['success'=>false,'error'=>'ایمیل معتبر نیست'],JSON_UNESCAPED_UNICODE);exit;}
if(strlen($password)<8){echo json_encode(['success'=>false,'error'=>'رمز عبور حداقل ۸ کاراکتر باشد'],JSON_UNESCAPED_UNICODE);exit;}
try{$auth=sb_auth('signup',['email'=>$email,'password'=>$password,'data'=>['username'=>$username]]);
if(!empty($auth['access_token'])){setAuthSession($auth);echo json_encode(['success'=>true,'message'=>'ثبت‌نام با موفقیت انجام شد','user'=>['id'=>getUserId(),'username'=>getUsername()]],JSON_UNESCAPED_UNICODE);}
else echo json_encode(['success'=>true,'message'=>'حساب ساخته شد. ایمیل خود را برای فعال‌سازی تأیید کنید، سپس وارد شوید.','requiresEmailConfirmation'=>true],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);}
?>