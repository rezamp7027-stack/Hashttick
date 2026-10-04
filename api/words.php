<?php
require_once '../config.php'; requireLogin(); header('Content-Type: application/json; charset=utf-8');
try{
  $uid=getUserId();$t=getAccessToken();
  if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'create';
    if($action==='delete'){
      $id=(int)($_POST['id']??0);if(!$id)throw new RuntimeException('شناسه واژه نامعتبر است');
      sb_delete('user_words',['id'=>$id,'user_id'=>$uid],$t);
      echo json_encode(['success'=>true],JSON_UNESCAPED_UNICODE);exit;
    }
    $en=trim($_POST['english']??'');$fa=trim($_POST['farsi']??'');$example=trim($_POST['example']??'');
    if($en===''||$fa==='')throw new RuntimeException('انگلیسی و معنی فارسی الزامی است');
    if(mb_strlen($en)>120||mb_strlen($fa)>240||mb_strlen($example)>500)throw new RuntimeException('طول یکی از فیلدها بیش از حد مجاز است');
    if($action==='update'){
      $id=(int)($_POST['id']??0);if(!$id)throw new RuntimeException('شناسه واژه نامعتبر است');
      $r=sb_update('user_words',['id'=>$id,'user_id'=>$uid],['english'=>$en,'farsi'=>$fa,'example'=>$example?:null],$t);
      if(!$r)throw new RuntimeException('واژه پیدا نشد');
      echo json_encode(['success'=>true,'word'=>$r[0]??null],JSON_UNESCAPED_UNICODE);exit;
    }
    if($action!=='create')throw new RuntimeException('عملیات نامعتبر است');
    sb_insert('user_words',['user_id'=>$uid,'english'=>$en,'farsi'=>$fa,'example'=>$example?:null],$t,false);
    echo json_encode(['success'=>true],JSON_UNESCAPED_UNICODE);exit;
  }
  if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);header('Allow: GET, POST');throw new RuntimeException('متد غیرمجاز');}
  $rows=sb_query('user_words',['select'=>'id,english,farsi,example,ticks,learned,in_re_review,date_added','user_id'=>'eq.'.$uid,'order'=>'date_added.desc,id.desc','limit'=>5000],$t);
  echo json_encode(['success'=>true,'words'=>array_map(fn($w)=>['id'=>(int)$w['id'],'english'=>$w['english'],'farsi'=>$w['farsi'],'example'=>$w['example']??'','ticks'=>(int)$w['ticks'],'learned'=>(bool)$w['learned'],'in_re_review'=>(bool)$w['in_re_review']],$rows)],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){if(http_response_code()===200)http_response_code(400);echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);}
?>