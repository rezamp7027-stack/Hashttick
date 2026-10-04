<?php
require_once '../config.php'; requireLogin(); header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');echo json_encode(['success'=>false,'error'=>'متد غیرمجاز'],JSON_UNESCAPED_UNICODE);exit;}
$uid=getUserId();$token=getAccessToken();
$en=trim($_POST['english']??'');$fa=trim($_POST['farsi']??'');$example=trim($_POST['example']??'');
if($en===''||$fa===''){http_response_code(422);echo json_encode(['success'=>false,'error'=>'لطفاً انگلیسی و معنی فارسی را وارد کنید'],JSON_UNESCAPED_UNICODE);exit;}
if(mb_strlen($en)>120||mb_strlen($fa)>240||mb_strlen($example)>500){http_response_code(422);echo json_encode(['success'=>false,'error'=>'طول یکی از فیلدها بیش از حد مجاز است'],JSON_UNESCAPED_UNICODE);exit;}
try{
  $existing=sb_query('user_words',['select'=>'id','user_id'=>'eq.'.$uid,'english'=>'eq.'.sb_filter_escape($en),'limit'=>1],$token);
  if($existing)throw new RuntimeException('این واژه قبلاً اضافه شده');
  sb_insert('user_words',['user_id'=>$uid,'english'=>$en,'farsi'=>$fa,'example'=>$example?:null],$token,false);
  echo json_encode(['success'=>true,'message'=>'واژه با موفقیت اضافه شد'],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){http_response_code(400);echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);}
?>