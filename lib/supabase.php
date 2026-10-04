<?php
declare(strict_types=1);

$GLOBALS['HASHTTICK_LOCAL_CONFIG'] = is_file(__DIR__.'/../config.local.php')
    ? (require __DIR__.'/../config.local.php')
    : [];

function env_value(string $key, ?string $default=null): ?string {
    $local = $GLOBALS['HASHTTICK_LOCAL_CONFIG'][$key] ?? null;
    if (is_string($local) && $local !== '') return $local;
    $v = getenv($key);
    return ($v===false||$v==='')?$default:$v;
}
function sb_url(): string { $u=rtrim((string)env_value('SUPABASE_URL',''),'/'); if($u==='')throw new RuntimeException('SUPABASE_URL is not configured'); return $u; }
function sb_key_candidates(): array {
    $configured = trim((string)env_value('SUPABASE_PUBLISHABLE_KEY',''));
    if ($configured === '') throw new RuntimeException('SUPABASE_PUBLISHABLE_KEY is not configured');
    return [$configured];
}
function sb_key(): string { return sb_key_candidates()[0]; }
function sb_is_invalid_api_key(?int $status, mixed $data): bool {
    if ($status !== 401 || !is_array($data)) return false;
    $message = strtolower((string)($data['msg'] ?? $data['message'] ?? $data['error_description'] ?? $data['error'] ?? ''));
    return str_contains($message, 'invalid api key');
}

function sb_request(string $method,string $path,?array $body=null,array $query=[],?string $accessToken=null,array $extraHeaders=[]): array {
    $url=sb_url().'/'.ltrim($path,'/');
    if($query){$pairs=[];foreach($query as $k=>$v){if($v===null)continue;if(is_array($v)){foreach($v as $x)$pairs[]=rawurlencode($k).'='.rawurlencode((string)$x);}else{$pairs[]=rawurlencode($k).'='.rawurlencode((string)$v);}}if($pairs)$url.='?'.implode('&',$pairs);}
    $keys=sb_key_candidates();
    foreach($keys as $i=>$key){
        $headers=['apikey: '.$key,'Accept: application/json','Content-Type: application/json'];
        if($accessToken)$headers[]='Authorization: Bearer '.$accessToken; foreach($extraHeaders as $h)$headers[]=$h;
        $ch=curl_init($url); $opts=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>strtoupper($method),CURLOPT_HTTPHEADER=>$headers,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>25,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2];
        if($body!==null)$opts[CURLOPT_POSTFIELDS]=json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); curl_setopt_array($ch,$opts);
        $raw=curl_exec($ch);$errno=curl_errno($ch);$error=curl_error($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        if($errno)throw new RuntimeException('Supabase connection failed: '.$error);
        $data=$raw!==false&&$raw!==''?json_decode($raw,true):null;
        if($status>=200&&$status<300)return ['status'=>$status,'data'=>$data];
        if($i<count($keys)-1 && sb_is_invalid_api_key($status,$data))continue;
        $message=is_array($data)?($data['msg']??$data['message']??$data['error_description']??$data['error']??'Supabase request failed'):'Supabase request failed';
        $e=new RuntimeException((string)$message);$e->supabase_status=$status;$e->supabase_body=$data;throw $e;
    }
    throw new RuntimeException('Supabase request failed');
}
function sb_auth(string $action,array $payload): array {
    $path=match($action){'signup'=>'/auth/v1/signup','password'=>'/auth/v1/token?grant_type=password','refresh'=>'/auth/v1/token?grant_type=refresh_token','user'=>'/auth/v1/user',default=>throw new InvalidArgumentException('Unsupported auth action')};
    $url=sb_url().$path;
    $accessToken=$payload['_access_token']??null;
    if(array_key_exists('_access_token',$payload))unset($payload['_access_token']);
    $keys=sb_key_candidates();
    foreach($keys as $i=>$key){
        $headers=['apikey: '.$key,'Content-Type: application/json','Accept: application/json'];
        if($accessToken)$headers[]='Authorization: Bearer '.$accessToken;
        $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>'POST',CURLOPT_HTTPHEADER=>$headers,CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE),CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
        $raw=curl_exec($ch);$errno=curl_errno($ch);$error=curl_error($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        if($errno)throw new RuntimeException('Auth connection failed: '.$error);
        $data=$raw!==false&&$raw!==''?json_decode($raw,true):null;
        if($status>=200&&$status<300)return $data?:[];
        if($i<count($keys)-1 && sb_is_invalid_api_key($status,$data))continue;
        $message=is_array($data)?($data['msg']??$data['message']??$data['error_description']??$data['error']??'Authentication failed'):'Authentication failed';
        $e=new RuntimeException((string)$message);$e->supabase_status=$status;$e->supabase_body=$data;throw $e;
    }
    throw new RuntimeException('Authentication failed');
}
function sb_query(string $table,array $query=[],?string $token=null): array{return sb_request('GET','/rest/v1/'.$table,null,$query,$token)['data']??[];}
function sb_insert(string $table,array $row,?string $token=null,bool $returnRepresentation=true): array{$h=$returnRepresentation?['Prefer: return=representation']:['Prefer: return=minimal'];return sb_request('POST','/rest/v1/'.$table,$row,[],$token,$h)['data']??[];}
function sb_insert_many(string $table,array $rows,?string $token=null): array{
    if(!$rows)return [];
    return sb_request('POST','/rest/v1/'.$table,$rows,[],$token,['Prefer: return=representation'])['data']??[];
}
function sb_update(string $table,array $filters,array $row,?string $token=null): array{$q=[];foreach($filters as $k=>$v)$q[$k]='eq.'.$v;return sb_request('PATCH','/rest/v1/'.$table,$row,$q,$token,['Prefer: return=representation'])['data']??[];}
function sb_delete(string $table,array $filters,?string $token=null): array{$q=[];foreach($filters as $k=>$v)$q[$k]='eq.'.$v;return sb_request('DELETE','/rest/v1/'.$table,null,$q,$token,['Prefer: return=representation'])['data']??[];}
function sb_upsert(string $table,array $row,array $onConflict,?string $token=null): array{return sb_request('POST','/rest/v1/'.$table,$row,['on_conflict'=>implode(',',$onConflict)],$token,['Prefer: resolution=merge-duplicates,return=representation'])['data']??[];}
function sb_rpc(string $function,array $args=[],?string $token=null): mixed{return sb_request('POST','/rest/v1/rpc/'.$function,$args,[],$token)['data']??null;}
function sb_error_message(Throwable $e): string {
    $m=$e->getMessage();
    $lower=strtolower($m);
    if(str_contains($m,'Invalid login credentials'))return 'ایمیل یا رمز عبور اشتباه است';
    if(str_contains($m,'User already registered'))return 'این ایمیل قبلاً ثبت شده است';
    if(str_contains($m,'Email not confirmed'))return 'ایمیل شما هنوز تأیید نشده است';
    if(str_contains($m,'duplicate key'))return 'این مقدار قبلاً ثبت شده است';
    if(str_contains($lower,'jwt expired')||str_contains($lower,'token is expired'))return 'جلسه ورود منقضی شده است؛ دوباره وارد شوید';
    if(str_contains($lower,'already reviewed today'))return 'این واژه امروز قبلاً مرور شده است';
    if(str_contains($lower,'word already learned'))return 'این واژه قبلاً یادگرفته شده است';
    if(str_contains($lower,'word not found'))return 'واژه پیدا نشد یا دیگر متعلق به این حساب نیست';
    if(str_contains($lower,'collection not found'))return 'مجموعه پیدا نشد';
    if(str_contains($lower,'not authenticated'))return 'جلسه ورود معتبر نیست؛ دوباره وارد شوید';
    if(str_contains($lower,'refresh token')&&(str_contains($lower,'invalid')||str_contains($lower,'not found')||str_contains($lower,'already used')))return 'جلسه ورود دیگر معتبر نیست؛ دوباره وارد شوید';
    return $m?:'خطای نامشخص';
}
function sb_filter_escape(string $value): string{return str_replace(['\\',','],['\\\\','\\,'],$value);}
?>