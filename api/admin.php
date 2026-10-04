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
function validColor(string $color): string {
    return preg_match('/^#[0-9a-fA-F]{6}$/',$color) ? $color : '#8b7cff';
}
function optionalHttpUrl(string $value): ?string {
    $value=trim($value);
    if($value==='') return null;
    if(!filter_var($value,FILTER_VALIDATE_URL)) throw new RuntimeException('آدرس واردشده معتبر نیست');
    $scheme=strtolower((string)parse_url($value,PHP_URL_SCHEME));
    if(!in_array($scheme,['http','https'],true)) throw new RuntimeException('فقط آدرس‌های HTTP/HTTPS مجاز هستند');
    return $value;
}

try {
    $t = getAccessToken();
    $a = $_GET['action'] ?? '';

    if ($a === 'collections') {
        adminJson(['success'=>true,'collections'=>sb_query('collections',[
            'select'=>'id,name,description,type,cover_image,cover_color,total_words,video_url,pdf_url,created_at,updated_at',
            'order'=>'created_at.desc','limit'=>200
        ],$t)]);
    }

    if ($a === 'users') {
        adminJson(['success'=>true,'users'=>sb_query('profiles',[
            'select'=>'id,username,email,is_admin,created_at',
            'order'=>'created_at.desc','limit'=>500
        ],$t)]);
    }

    if ($a === 'seed_defaults') {
        requirePost();
        $file=__DIR__.'/../assets/default-content.json';
        if(!is_file($file)) throw new RuntimeException('فایل محتوای اولیه پیدا نشد');
        $seed=json_decode((string)file_get_contents($file),true);
        if(!is_array($seed)) throw new RuntimeException('محتوای اولیه نامعتبر است');

        $collectionsSeeded=0;$addedWords=0;
        foreach($seed as $item){
            $name=trim((string)($item['name']??''));
            $description=trim((string)($item['description']??''));
            $type=(string)($item['type']??'dictionary');
            if($name===''||!in_array($type,['dictionary','story','pdf','video'],true))continue;

            $found=sb_query('collections',['select'=>'id,name,description,type','name'=>'eq.'.$name,'limit'=>1],$t);
            if($found){
                $collectionId=(int)$found[0]['id'];
            }else{
                $created=sb_insert('collections',[
                    'name'=>$name,
                    'description'=>$description,
                    'type'=>$type,
                    'cover_color'=>'#8b7cff'
                ],$t,true);
                if(empty($created[0]['id']))continue;
                $collectionId=(int)$created[0]['id'];
                $collectionsSeeded++;
            }

            $existing=sb_query('collection_words',[
                'select'=>'english,unit',
                'collection_id'=>'eq.'.$collectionId,
                'limit'=>5000
            ],$t);
            $seen=[];
            foreach($existing as $row){
                $seen[strtolower(trim((string)$row['english'])).'|'.(int)$row['unit']]=true;
            }

            $rows=[];
            foreach((array)($item['words']??[]) as $i=>$word){
                $english=trim((string)($word[0]??''));
                $farsi=trim((string)($word[1]??''));
                $example=trim((string)($word[2]??''));
                $unit=(int)floor($i/10)+1;
                $key=strtolower($english).'|'.$unit;
                if($english===''||$farsi===''||isset($seen[$key]))continue;
                $rows[]=['collection_id'=>$collectionId,'english'=>$english,'farsi'=>$farsi,'example'=>$example,'unit'=>$unit];
                $seen[$key]=true;
            }
            if($rows){
                $inserted=sb_insert_many('collection_words',$rows,$t);
                $addedWords+=count($inserted);
            }

            $count=sb_query('collection_words',[
                'select'=>'id',
                'collection_id'=>'eq.'.$collectionId,
                'limit'=>5000
            ],$t);
            sb_update('collections',['id'=>$collectionId],['total_words'=>count($count)],$t);
        }

        adminJson(['success'=>true,'collectionsSeeded'=>$collectionsSeeded,'addedWords'=>$addedWords]);
    }

    if ($a === 'collection') {
        $id=(int)($_GET['id']??0);
        if(!$id) throw new RuntimeException('شناسه مجموعه نامعتبر است');
        $c=sb_query('collections',[
            'select'=>'id,name,description,type,cover_image,cover_color,total_words,video_url,pdf_url',
            'id'=>'eq.'.$id,'limit'=>1
        ],$t);
        if(!$c) throw new RuntimeException('مجموعه پیدا نشد');
        $words=sb_query('collection_words',[
            'select'=>'id,english,farsi,example,unit',
            'collection_id'=>'eq.'.$id,
            'order'=>'unit.asc,id.asc',
            'limit'=>5000
        ],$t);
        $stories=sb_query('collection_stories',[
            'select'=>'id,chapter_number,chapter_title,content',
            'collection_id'=>'eq.'.$id,
            'order'=>'chapter_number.asc',
            'limit'=>200
        ],$t);
        adminJson(['success'=>true,'collection'=>$c[0],'words'=>$words,'stories'=>$stories]);
    }

    requirePost();

    if ($a === 'create_collection' || $a === 'update_collection') {
        $name=trim($_POST['name']??'');
        if($name==='') throw new RuntimeException('نام مجموعه الزامی است');
        $type=$_POST['type']??'dictionary';
        if(!in_array($type,['dictionary','story','pdf','video'],true)) $type='dictionary';
        if(mb_strlen($name)>180||mb_strlen(trim($_POST['description']??''))>500) throw new RuntimeException('طول نام یا توضیح بیش از حد مجاز است');
        $data=[
            'name'=>$name,
            'description'=>trim($_POST['description']??''),
            'type'=>$type,
            'cover_color'=>validColor(trim($_POST['cover_color']??'#8b7cff')),
            'cover_image'=>optionalHttpUrl($_POST['cover_image']??''),
            'video_url'=>optionalHttpUrl($_POST['video_url']??''),
            'pdf_url'=>optionalHttpUrl($_POST['pdf_url']??'')
        ];
        if($a==='create_collection'){
            $r=sb_insert('collections',$data,$t,true);
        }else{
            $id=(int)($_POST['id']??0);
            if(!$id) throw new RuntimeException('شناسه مجموعه نامعتبر است');
            $r=sb_update('collections',['id'=>$id],$data,$t);
        }
        adminJson(['success'=>true,'collection'=>$r[0]??null]);
    }

    if ($a === 'delete_collection') {
        $id=(int)($_POST['id']??0);
        if(!$id) throw new RuntimeException('شناسه مجموعه نامعتبر است');
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
        if(mb_strlen($english)>120||mb_strlen($farsi)>240||mb_strlen($example)>500||$unit>999) throw new RuntimeException('طول یا مقدار یکی از فیلدهای واژه بیش از حد مجاز است');

        if($a==='create_word'){
            $r=sb_insert('collection_words',[
                'collection_id'=>$collection,'english'=>$english,'farsi'=>$farsi,'example'=>$example,'unit'=>$unit
            ],$t,true);
        }else{
            $id=(int)($_POST['id']??0);
            if(!$id) throw new RuntimeException('شناسه واژه نامعتبر است');
            $r=sb_update('collection_words',['id'=>$id,'collection_id'=>$collection],[
                'english'=>$english,'farsi'=>$farsi,'example'=>$example,'unit'=>$unit
            ],$t);
        }
        adminJson(['success'=>true,'word'=>$r[0]??null]);
    }

    if ($a === 'delete_word') {
        $id=(int)($_POST['id']??0);
        $collection=(int)($_POST['collection_id']??0);
        if(!$id||!$collection) throw new RuntimeException('شناسه واژه نامعتبر است');
        sb_delete('collection_words',['id'=>$id,'collection_id'=>$collection],$t);
        adminJson(['success'=>true]);
    }

    if ($a === 'create_story' || $a === 'update_story') {
        $collection=(int)($_POST['collection_id']??0);
        $title=trim($_POST['chapter_title']??'');
        $content=trim($_POST['content']??'');
        $chapter=max(1,(int)($_POST['chapter_number']??1));
        if(!$collection||$title===''||$content==='') throw new RuntimeException('مجموعه، عنوان و متن فصل الزامی است');
        if(mb_strlen($title)>240||mb_strlen($content)>20000||$chapter>999) throw new RuntimeException('طول یا شماره فصل بیش از حد مجاز است');
        $data=['chapter_number'=>$chapter,'chapter_title'=>$title,'content'=>$content];

        if($a==='create_story'){
            $r=sb_insert('collection_stories',$data+['collection_id'=>$collection],$t,true);
        }else{
            $id=(int)($_POST['id']??0);
            if(!$id) throw new RuntimeException('شناسه فصل نامعتبر است');
            $r=sb_update('collection_stories',['id'=>$id,'collection_id'=>$collection],$data,$t);
        }
        adminJson(['success'=>true,'story'=>$r[0]??null]);
    }

    if ($a === 'delete_story') {
        $id=(int)($_POST['id']??0);
        if(!$id) throw new RuntimeException('شناسه فصل نامعتبر است');
        sb_delete('collection_stories',['id'=>$id],$t);
        adminJson(['success'=>true]);
    }

    throw new RuntimeException('عملیات نامعتبر است');
} catch(Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);
}
?>