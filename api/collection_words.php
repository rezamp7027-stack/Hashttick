<?php
require_once '../config.php'; requireLogin(); header('Content-Type: application/json; charset=utf-8');
try{
  $id=(int)($_GET['collection_id']??$_POST['collection_id']??0);
  if(!$id)throw new RuntimeException('مجموعه نامعتبر است');
  $t=getAccessToken();

  if($_SERVER['REQUEST_METHOD']==='POST'){
    $result=sb_rpc('import_collection_words',['p_collection_id'=>$id],$t);
    echo json_encode(is_array($result)?$result:['success'=>true],JSON_UNESCAPED_UNICODE);
    exit;
  }

  if($_SERVER['REQUEST_METHOD']!=='GET'){
    http_response_code(405);header('Allow: GET, POST');
    throw new RuntimeException('متد غیرمجاز');
  }

  $rows=sb_query('collection_words',[
    'select'=>'id,english,farsi,example,unit,definition_en,example_en,part_of_speech,cefr_level,frequency_rank,phonetic_us,audio_us',
    'collection_id'=>'eq.'.$id,
    'order'=>'unit.asc,id.asc',
    'limit'=>5000
  ],$t);
  echo json_encode(['success'=>true,'words'=>$rows],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);
}
?>