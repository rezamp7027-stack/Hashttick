<?php
require_once '../config.php';requireLogin();header('Content-Type: application/json; charset=utf-8');
try{
 $id=(int)($_GET['collection_id']??$_POST['collection_id']??0);if(!$id)throw new RuntimeException('مجموعه نامعتبر است');$t=getAccessToken();
 if($_SERVER['REQUEST_METHOD']==='POST'){
   $rows=sb_query('collection_words',['select'=>'english,farsi,example','collection_id'=>'eq.'.$id,'order'=>'unit.asc,id.asc','limit'=>500],$t);
   $count=0;foreach($rows as $w){try{sb_upsert('user_words',['user_id'=>getUserId(),'english'=>$w['english'],'farsi'=>$w['farsi']],['user_id','english'],$t);$count++;}catch(Throwable $ignore){}}
   echo json_encode(['success'=>true,'added'=>$count],JSON_UNESCAPED_UNICODE);exit;
 }
 $rows=sb_query('collection_words',['select'=>'id,english,farsi,example,unit','collection_id'=>'eq.'.$id,'order'=>'unit.asc,id.asc','limit'=>1000],$t);
 echo json_encode(['success'=>true,'words'=>$rows],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){http_response_code(400);echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);}
?>