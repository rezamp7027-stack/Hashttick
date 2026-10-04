<?php
declare(strict_types=1);

session_name('hashttick_session');
session_start([
    'cookie_httponly'=>true,
    'cookie_samesite'=>'Lax',
    'cookie_secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'),
    'use_strict_mode'=>true
]);
date_default_timezone_set('Asia/Tehran');

require_once __DIR__.'/lib/supabase.php';

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token']=bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
csrfToken();

function jwtExp(?string $token): ?int{
    if(!$token)return null;
    $parts=explode('.',$token);
    if(count($parts)!==3)return null;
    $payload=strtr($parts[1],'-_','+/');
    $pad=strlen($payload)%4;
    if($pad)$payload.=str_repeat('=',4-$pad);
    $decoded=base64_decode($payload,true);
    if($decoded===false)return null;
    $data=json_decode($decoded,true);
    return is_array($data)&&isset($data['exp'])&&is_numeric($data['exp'])?(int)$data['exp']:null;
}

function accessTokenNeedsRefresh(int $leeway=60): bool{
    $token=getAccessToken();
    if(!$token)return true;
    $exp=jwtExp($token);
    if($exp===null)return false;
    return $exp<=time()+$leeway;
}

function ensureFreshSession(): bool{
    if(!isLoggedIn())return false;
    if(!accessTokenNeedsRefresh(60))return true;
    return tryRefreshSession();
}

function isLoggedIn(): bool{return !empty($_SESSION['access_token'])&&!empty($_SESSION['user_id']);}
function getUserId(): ?string{return $_SESSION['user_id']??null;}
function getUsername(): ?string{return $_SESSION['username']??null;}
function getAccessToken(): ?string{return $_SESSION['access_token']??null;}
function isAdmin(): bool{return !empty($_SESSION['is_admin']);}

function validateCsrf(): void {
    $method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');
    if (!in_array($method,['POST','PUT','PATCH','DELETE'],true)) return;
    $expected=(string)($_SESSION['csrf_token']??'');
    $provided=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??($_POST['_csrf']??''));
    if ($expected==='' || $provided==='' || !hash_equals($expected,$provided)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'درخواست امنیتی نامعتبر است'],JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function syncAdminState(): bool {
    if (!isLoggedIn()) {
        $_SESSION['is_admin']=false;
        return false;
    }
    try {
        $p=sb_query(
            'profiles',
            ['select'=>'username,email,is_admin','id'=>'eq.'.getUserId(),'limit'=>1],
            getAccessToken()
        );
        if (!empty($p[0])) {
            $_SESSION['username']=$p[0]['username'];
            $_SESSION['email']=$p[0]['email'];
            $_SESSION['is_admin']=(bool)$p[0]['is_admin'];
        } else {
            $_SESSION['is_admin']=false;
        }
    } catch(Throwable $e) {
        $_SESSION['is_admin']=false;
    }
    return isAdmin();
}

function setAuthSession(array $auth): void{
    $user=$auth['user']??null;
    $access=$auth['access_token']??null;
    if(!$user||!$access)throw new RuntimeException('Supabase did not return a login session.');
    if(session_status()===PHP_SESSION_ACTIVE)session_regenerate_id(true);
    $_SESSION['access_token']=$access;
    $_SESSION['refresh_token']=$auth['refresh_token']??null;
    $_SESSION['user_id']=$user['id'];
    $_SESSION['email']=$user['email']??'';
    $_SESSION['username']=$user['user_metadata']['username']??($user['email']??'');
    $_SESSION['is_admin']=false;
    csrfToken();
    try{
        $p=sb_query('profiles',['select'=>'username,email,is_admin','id'=>'eq.'.$user['id'],'limit'=>1],$access);
        if(!empty($p[0])){
            $_SESSION['username']=$p[0]['username'];
            $_SESSION['email']=$p[0]['email'];
            $_SESSION['is_admin']=(bool)$p[0]['is_admin'];
        }
    }catch(Throwable $e){}
}

function clearAuthSession(): void{
    $_SESSION=[];
    if(ini_get('session.use_cookies')){
        $p=session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time()-42000,
            $p['path'],
            $p['domain']??'',
            (bool)$p['secure'],
            (bool)$p['httponly']
        );
    }
    session_destroy();
}

function tryRefreshSession(): bool{
    $refresh=$_SESSION['refresh_token']??null;
    if(!$refresh)return false;
    try{
        setAuthSession(sb_auth('refresh',['refresh_token'=>$refresh]));
        return true;
    }catch(Throwable $e){
        clearAuthSession();
        return false;
    }
}

function requireLogin(): void{
    validateCsrf();
    if(!isLoggedIn()||!ensureFreshSession()){
        clearAuthSession();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success'=>false,'error'=>'جلسه منقضی شده است','code'=>'AUTH_SESSION_EXPIRED','redirect'=>'login.php'],JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function requireAdmin(): void{
    requireLogin();
    syncAdminState();
    if(!isAdmin()){
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'دسترسی ادمین لازم است'],JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function requireLoginForPage(): void{
    if(!isLoggedIn()||!ensureFreshSession()){
        clearAuthSession();
        header('Location: login.php');
        exit;
    }
}

function requireAdminForPage(): void{
    requireLoginForPage();
    if(!syncAdminState()){http_response_code(403);exit('دسترسی غیرمجاز');}
}
?>