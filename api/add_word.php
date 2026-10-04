<?php
require_once '../config.php';requireLogin();header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['success'=>false,'error'=>'متد غیرمجاز']);exit;}
$uid=getUserId();$token=getAccessToken();$en=trim($_POST['english']??'');$fa=trim($_POST['farsi']??'');
if($en===''||$fa===''){echo json_encode(['success'=>false,'error'=>'لطفاً هر دو فیلد را پر کنید'],JSON_UNESCAPED_UNICODE);exit;}
try{$existing=sb_query('user_words',['select'=>'id','user_id'=>'eq.'.$uid,'english'=>'eq.'.sb_filter_escape($en),'limit'=>1],$token);if($existing)throw new RuntimeException('این واژه قبلاً اضافه شده');sb_insert('user_words',['user_id'=>$uid,'english'=>$en,'farsi'=>$fa],$token,false);echo json_encode(['success'=>true,'message'=>'واژه با موفقیت اضافه شد'],JSON_UNESCAPED_UNICODE);}
catch(Throwable $e){echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);}
?>