<?php
require_once '../config.php';requireAdmin();header('Content-Type: application/json; charset=utf-8');
try{
 $t=getAccessToken();$a=$_GET['action']??'';
 if($a==='collections'){echo json_encode(['success'=>true,'collections'=>sb_query('collections',['select'=>'id,name,description,type,total_words,cover_color','order'=>'created_at.desc','limit'=>200],$t)],JSON_UNESCAPED_UNICODE);exit;}
 if($a==='users'){echo json_encode(['success'=>true,'users'=>sb_query('profiles',['select'=>'id,username,email,is_admin,created_at','order'=>'created_at.desc','limit'=>500],$t)],JSON_UNESCAPED_UNICODE);exit;}
 if($_SERVER['REQUEST_METHOD']!=='POST')throw new RuntimeException('متد غیرمجاز');
 if($a==='create_collection'){ $name=trim($_POST['name']??'');if($name==='')throw new RuntimeException('نام مجموعه الزامی است');$type=$_POST['type']??'dictionary';if(!in_array($type,['dictionary','story','pdf','video'],true))$type='dictionary';$r=sb_insert('collections',['name'=>$name,'description'=>trim($_POST['description']??''),'type'=>$type,'cover_color'=>trim($_POST['cover_color']??'#8b7cff')],$t,true);echo json_encode(['success'=>true,'collection'=>$r[0]??null],JSON_UNESCAPED_UNICODE);exit;}
 if($a==='delete_collection'){ $id=(int)($_POST['id']??0);if(!$id)throw new RuntimeException('شناسه نامعتبر است');sb_delete('collections',['id'=>$id],$t);echo json_encode(['success'=>true],JSON_UNESCAPED_UNICODE);exit;}
 throw new RuntimeException('عملیات نامعتبر است');
}catch(Throwable $e){http_response_code(400);echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);}
?>