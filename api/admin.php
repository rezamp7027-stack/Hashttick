<?php
require_once '../config.php';
requireAdmin();
header('Content-Type: application/json; charset=utf-8');

function adminJson(array $data): never {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function requirePost(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new RuntimeException('متد غیرمجاز');
    }
}
try {
    $t = getAccessToken();
    $a = $_GET['action'] ?? '';

    if ($a === 'collections') {
        adminJson(['success'=>true,'collections'=>sb_query('collections',[
            'select'=>'id,name,description,type,cover_image,cover_color,total_words,video_url,pdf_url,created_at',
            'order'=>'created_at.desc','limit'=>200
        ],$t)]);
    }

    if ($a === 'users') {
        adminJson(['success'=>true,'users'=>sb_query('profiles',[
            'select'=>'id,username,email,is_admin,created_at',
            'order'=>'created_at.desc','limit'=>500
        ],$t)]);
    }

    if ($a === 'collection') {
        $id=(int)($_GET['id']??0);
        if(!$id) throw new RuntimeException('شناسه مجموعه نامعتبر است');
        $c=sb_query('collections',['select'=>'id,name,description,type,cover_image,cover_color,total_words,video_url,pdf_url','id'=>'eq.'.$id,'limit'=>1],$t);
        if(!$c) throw new RuntimeException('مجموعه پیدا نشد');
        $words=sb_query('collection_words',['select'=>'id,english,farsi,example,unit','collection_id'=>'eq.'.$id,'order'=>'unit.asc,id.asc','limit'=>1000],$t);
        $stories=sb_query('collection_stories',['select'=>'id,chapter_number,chapter_title,content','collection_id'=>'eq.'.$id,'order'=>'chapter_number.asc','limit'=>200],$t);
        adminJson(['success'=>true,'collection'=>$c[0],'words'=>$words,'stories'=>$stories]);
    }

    requirePost();

    if ($a === 'create_collection') {
        $name=trim($_POST['name']??'');
        if($name==='') throw new RuntimeException('نام مجموعه الزامی است');
        $type=$_POST['type']??'dictionary';
        if(!in_array($type,['dictionary','story','pdf','video'],true)) $type='dictionary';
        $color=trim($_POST['cover_color']??'#8b7cff');
        if(!preg_match('/^#[0-9a-fA-F]{6}$/',$color)) $color='#8b7cff';
        $r=sb_insert('collections',[
            'name'=>$name,
            'description'=>trim($_POST['description']??''),
            'type'=>$type,
            'cover_color'=>$color,
            'cover_image'=>trim($_POST['cover_image']??'') ?: null,
            'video_url'=>trim($_POST['video_url']??'') ?: null,
            'pdf_url'=>trim($_POST['pdf_url']??'') ?: null
        ],$t,true);
        adminJson(['success'=>true,'collection'=>$r[0]??null]);
    }

    if ($a === 'update_collection') {
        $id=(int)($_POST['id']??0); if(!$id) throw new RuntimeException('شناسه نامعتبر است');
        $type=$_POST['type']??'dictionary';
        if(!in_array($type,['dictionary','story','pdf','video'],true)) $type='dictionary';
        $data=[
            'name'=>trim($_POST['name']??''),
            'description'=>trim($_POST['description']??''),
            'type'=>$type,
            'cover_color'=>trim($_POST['cover_color']??'#8b7cff'),
            'cover_image'=>trim($_POST['cover_image']??'') ?: null,
            'video_url'=>trim($_POST['video_url']??'') ?: null,
            'pdf_url'=>trim($_POST['pdf_url']??'') ?: null
        ];
        if($data['name']==='') throw new RuntimeException('نام مجموعه الزامی است');
        $r=sb_update('collections',$data,['id'=>$id],$t,true);
        adminJson(['success'=>true,'collection'=>$r[0]??null]);
    }

    if ($a === 'delete_collection') {
        $id=(int)($_POST['id']??0); if(!$id) throw new RuntimeException('شناسه نامعتبر است');
        sb_delete('collections',['id'=>$id],$t);
        adminJson(['success'=>true]);
    }

    if ($a === 'create_word' || $a === 'update_word') {
        $collection=(int)($_POST['collection_id']??0);
        if(!$collection) throw new RuntimeException('مجموعه نامعتبر است');
        $english=trim($_POST['english']??'');
        $farsi=trim($_POST['farsi']??'');
        $example=trim($_POST['example']??'');
        $unit=max(1,(int)($_POST['unit']??1));
        if($english===''||$farsi==='') throw new RuntimeException('واژه انگلیسی و معنی فارسی الزامی است');
        if($a==='create_word'){
            $r=sb_insert('collection_words',['collection_id'=>$collection,'english'=>$english,'farsi'=>$farsi,'example'=>$example,'unit'=>$unit],$t,true);
        }else{
            $id=(int)($_POST['id']??0); if(!$id) throw new RuntimeException('شناسه واژه نامعتبر است');
            $r=sb_update('collection_words',['english'=>$english,'farsi'=>$farsi,'example'=>$example,'unit'=>$unit],['id'=>$id,'collection_id'=>$collection],$t,true);
        }
        $count=count(sb_query('collection_words',['select'=>'id','collection_id'=>'eq.'.$collection,'limit'=>1],$t));
        $all=sb_query('collection_words',['select'=>'id','collection_id'=>'eq.'.$collection,'limit'=>1000],$t);
        sb_update('collections',['total_words'=>count($all)],['id'=>$collection],$t);
        adminJson(['success'=>true,'word'=>$r[0]??null]);
    }

    if ($a === 'delete_word') {
        $id=(int)($_POST['id']??0); if(!$id) throw new RuntimeException('شناسه واژه نامعتبر است');
        $collection=(int)($_POST['collection_id']??0); if(!$collection) throw new RuntimeException('مجموعه نامعتبر است');
        sb_delete('collection_words',['id'=>$id,'collection_id'=>$collection],$t);
        $all=sb_query('collection_words',['select'=>'id','collection_id'=>'eq.'.$collection,'limit'=>1000],$t);
        sb_update('collections',['total_words'=>count($all)],['id'=>$collection],$t);
        adminJson(['success'=>true]);
    }

    if ($a === 'create_story' || $a === 'update_story') {
        $collection=(int)($_POST['collection_id']??0);
        $title=trim($_POST['chapter_title']??'');
        $content=trim($_POST['content']??'');
        $chapter=max(1,(int)($_POST['chapter_number']??1));
        if(!$collection||$title===''||$content==='') throw new RuntimeException('مجموعه، عنوان و متن فصل الزامی است');
        $data=['chapter_number'=>$chapter,'chapter_title'=>$title,'content'=>$content,'collection_id'=>$collection];
        if($a==='create_story') $r=sb_insert('collection_stories',$data,$t,true);
        else {$id=(int)($_POST['id']??0);if(!$id)throw new RuntimeException('شناسه فصل نامعتبر است');$r=sb_update('collection_stories',['chapter_number'=>$chapter,'chapter_title'=>$title,'content'=>$content],['id'=>$id,'collection_id'=>$collection],$t,true);}
        adminJson(['success'=>true,'story'=>$r[0]??null]);
    }

    if ($a === 'delete_story') {
        $id=(int)($_POST['id']??0);if(!$id)throw new RuntimeException('شناسه فصل نامعتبر است');
        sb_delete('collection_stories',['id'=>$id],$t);
        adminJson(['success'=>true]);
    }

    throw new RuntimeException('عملیات نامعتبر است');
} catch(Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);
}
?>