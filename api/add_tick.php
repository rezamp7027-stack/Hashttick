<?php
require_once '../config.php';requireLogin();header('Content-Type: application/json; charset=utf-8');
try{$id=(int)($_POST['word_id']??0);if(!$id)throw new RuntimeException('واژه نامعتبر است');$r=sb_rpc('review_word',['p_word_id'=>$id,'p_correct'=>true],getAccessToken());echo json_encode(is_array($r)?$r:['success'=>true],JSON_UNESCAPED_UNICODE);}
catch(Throwable $e){$m=sb_error_message($e);$already=str_contains($m,'already reviewed');echo json_encode(['success'=>false,'error'=>$already?'این واژه امروز قبلاً مرور شده است':$m,'alreadyReviewed'=>$already],JSON_UNESCAPED_UNICODE);}
?>