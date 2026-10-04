<?php
require_once '../config.php'; requireLogin(); header('Content-Type: application/json; charset=utf-8');
try{
  $uid=getUserId();$t=getAccessToken();$today=date('Y-m-d');
  $settings=sb_query('user_settings',['select'=>'setting_key,setting_value','user_id'=>'eq.'.$uid,'limit'=>30],$t);
  $limit=20;$startDate=$today;$pronunciation='1';
  foreach($settings as $s){
    if($s['setting_key']==='daily_limit')$limit=max(1,min(200,(int)$s['setting_value']));
    if($s['setting_key']==='study_start_date' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$s['setting_value']))$startDate=$s['setting_value'];
    if($s['setting_key']==='pronunciation')$pronunciation=$s['setting_value']==='0'?'0':'1';
  }
  $day=max(1,(new DateTime($startDate))->diff(new DateTime($today))->days+1);
  $rows=sb_query('user_words',['select'=>'id,english,farsi,example,ticks,learned,in_re_review,last_reviewed','user_id'=>'eq.'.$uid,'learned'=>'eq.false','order'=>'in_re_review.desc,ticks.asc,date_added.asc,id.asc','limit'=>5000],$t);
  $words=[];
  foreach($rows as $w){
    if(!empty($w['last_reviewed'])&&substr($w['last_reviewed'],0,10)===$today)continue;
    $words[]=['id'=>(int)$w['id'],'english'=>$w['english'],'farsi'=>$w['farsi'],'example'=>$w['example']??'','ticks'=>(int)$w['ticks'],'inReReview'=>(bool)$w['in_re_review']];
    if(count($words)>=$limit)break;
  }
  echo json_encode(['success'=>true,'studyDay'=>$day,'dailyLimit'=>$limit,'pronunciation'=>$pronunciation,'words'=>$words,'remainingEligible'=>count($words)],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){http_response_code(500);echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);}
?>