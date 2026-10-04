<?php
require_once '../config.php'; requireLogin(); header('Content-Type: application/json; charset=utf-8');
try{
  $id=(int)($_GET['collection_id']??$_POST['collection_id']??0);if(!$id)throw new RuntimeException('مجموعه نامعتبر است');$t=getAccessToken();$uid=getUserId();
  $rows=sb_query('collection_words',['select'=>'id,english,farsi,example,unit','collection_id'=>'eq.'.$id,'order'=>'unit.asc,id.asc','limit'=>1000],$t);
  if($_SERVER['REQUEST_METHOD']==='POST'){
    $existing=sb_query('user_words',['select'=>'english','user_id'=>'eq.'.$uid,'limit'=>5000],$t);
    $seen=[];foreach($existing as $e)$seen[mb_strtolower(trim((string)$e['english']))]=true;
    $count=0;
    foreach($rows as $w){
      $key=mb_strtolower(trim((string)$w['english']));
      if($key===''||isset($seen[$key]))continue;
      try{
        sb_insert('user_words',['user_id'=>$uid,'english'=>$w['english'],'farsi'=>$w['farsi'],'example'=>$w['example']??null],$t,false);
        $seen[$key]=true;$count++;
      }catch(Throwable $ignore){}
    }
    echo json_encode(['success'=>true,'added'=>$count,'skipped'=>count($rows)-$count],JSON_UNESCAPED_UNICODE);exit;
  }
  if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);header('Allow: GET, POST');throw new RuntimeException('متد غیرمجاز');}
  echo json_encode(['success'=>true,'words'=>$rows],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){http_response_code(400);echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);}
?>