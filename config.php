<?php
declare(strict_types=1);
session_name('hashttick_session');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/lib/supabase.php';

function isLoggedIn(): bool { return !empty($_SESSION['access_token']) && !empty($_SESSION['user_id']); }
function getUserId(): ?string { return $_SESSION['user_id'] ?? null; }
function getUsername(): ?string { return $_SESSION['username'] ?? null; }
function getAccessToken(): ?string { return $_SESSION['access_token'] ?? null; }
function isAdmin(): bool { return !empty($_SESSION['is_admin']); }

function setAuthSession(array $auth): void {
    $user=$auth['user']??null; $access=$auth['access_token']??null;
    if(!$user||!$access) throw new RuntimeException('Supabase did not return a login session.');
    $_SESSION['access_token']=$access; $_SESSION['refresh_token']=$auth['refresh_token']??null;
    $_SESSION['user_id']=$user['id']; $_SESSION['email']=$user['email']??'';
    $_SESSION['username']=$user['user_metadata']['username']??($user['email']??'');
    $_SESSION['is_admin']=false;
    try {
        $p=sb_query('profiles',['select'=>'username,email,is_admin','id'=>'eq.'.$user['id'],'limit'=>1],$access);
        if(!empty($p[0])) { $_SESSION['username']=$p[0]['username']; $_SESSION['email']=$p[0]['email']; $_SESSION['is_admin']=(bool)$p[0]['is_admin']; }
    } catch(Throwable $e) {}
}
function clearAuthSession(): void {
    $_SESSION=[];
    if(ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',
      time()-42000,$p['path'],$p['domain']??'',(bool)$p['secure'],(bool)$p['httponly']); }
    session_destroy();
}
function tryRefreshSession(): bool {
    $refresh=$_SESSION['refresh_token']??null; if(!$refresh)return false;
    try { setAuthSession(sb_auth('refresh',['refresh_token'=>$refresh])); return true; }
    catch(Throwable $e){ clearAuthSession(); return false; }
}
function requireLogin(): void {
    if(!isLoggedIn()){ header('Content-Type: application/json; charset=utf-8'); http_response_code(401);
      echo json_encode(['success'=>false,'error'=>'لطفاً وارد شوید','redirect'=>'login.php'],JSON_UNESCAPED_UNICODE); exit; }
}
function requireAdmin(): void {
    requireLogin();
    if(!isAdmin()) {
        try { $p=sb_query('profiles',['select'=>'is_admin,username,email','id'=>'eq.'.getUserId(),'limit'=>1],getAccessToken()); $_SESSION['is_admin']=!empty($p[0]['is_admin']); } catch(Throwable $e) {}
    }
    if(!isAdmin()){ header('Content-Type: application/json; charset=utf-8'); http_response_code(403);
      echo json_encode(['success'=>false,'error'=>'دسترسی ادمین لازم است'],JSON_UNESCAPED_UNICODE); exit; }
}
function requireLoginForPage(): void { if(!isLoggedIn()){ header('Location: login.php'); exit; } }
?>