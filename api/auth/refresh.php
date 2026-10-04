<?php
require_once '../../config.php';
header('Content-Type: application/json; charset=utf-8');

if($_SERVER['REQUEST_METHOD']!=='POST'){
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'متد غیرمجاز'],JSON_UNESCAPED_UNICODE);
    exit;
}

if(!isLoggedIn()){
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'جلسه‌ای وجود ندارد','redirect'=>'login.php'],JSON_UNESCAPED_UNICODE);
    exit;
}

validateCsrf();

if(!tryRefreshSession()){
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'جلسه منقضی شده است','redirect'=>'login.php'],JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success'=>true,
    'user'=>[
        'id'=>getUserId(),
        'username'=>getUsername(),
        'is_admin'=>isAdmin()
    ]
],JSON_UNESCAPED_UNICODE);
?>