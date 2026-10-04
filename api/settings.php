<?php
require_once '../config.php'; requireLogin(); header('Content-Type: application/json; charset=utf-8');
try{
  $uid=getUserId();$t=getAccessToken();
  if($_SERVER['REQUEST_METHOD']==='POST'){
    $limit=max(1,min(200,(int)($_POST['daily_limit']??20)));
    $pronunciation=isset($_POST['pronunciation']);
    $result=sb_rpc('save_user_settings',['p_daily_limit'=>$limit,'p_pronunciation'=>$pronunciation],$t);
    echo json_encode(is_array($result)?$result:['success'=>true],JSON_UNESCAPED_UNICODE);exit;
  }
  if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);header('Allow: GET, POST');throw new RuntimeException('متد غیرمجاز');}
  $rows=sb_query('user_settings',['select'=>'setting_key,setting_value','user_id'=>'eq.'.$uid,'limit'=>30],$t);
  $out=['daily_limit'=>'20','pronunciation'=>'1'];
  foreach($rows as $r){
    if($r['setting_key']==='daily_limit')$out['daily_limit']=$r['setting_value'];
    if($r['setting_key']==='pronunciation')$out['pronunciation']=$r['setting_value']==='0'?'0':'1';
  }
  echo json_encode(['success'=>true]+$out,JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){http_response_code(400);echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);}
?>