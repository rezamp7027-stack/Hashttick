<?php
require_once '../config.php'; requireLogin(); header('Content-Type: application/json; charset=utf-8');
try{
  $uid=getUserId();$t=getAccessToken();$today=date('Y-m-d');
  $words=sb_query('user_words',['select'=>'id,ticks,learned,last_reviewed,in_re_review','user_id'=>'eq.'.$uid,'order'=>'date_added.asc,id.asc','limit'=>5000],$t);
  $stats=sb_query('user_stats',['select'=>'stat_date,total_reviews','user_id'=>'eq.'.$uid,'order'=>'stat_date.desc','limit'=>1000],$t);
  $settings=sb_query('user_settings',['select'=>'setting_key,setting_value','user_id'=>'eq.'.$uid,'limit'=>30],$t);
  $daily=20;$startDate=$today;
  foreach($settings as $s){if($s['setting_key']==='daily_limit')$daily=max(1,min(200,(int)$s['setting_value']));if($s['setting_key']==='study_start_date'&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$s['setting_value']))$startDate=$s['setting_value'];}
  $day=max(1,(new DateTime($startDate))->diff(new DateTime($today))->days+1);
  $todayReviews=0;$active=0;$learned=0;$dist=array_fill(0,7,0);$eligible=0;
  foreach($stats as $s)if($s['stat_date']===$today)$todayReviews=(int)$s['total_reviews'];
  foreach($words as $w){
    if(!empty($w['learned'])){$learned++;$dist[6]++;continue;}
    $active++;$dist[min(5,max(0,(int)$w['ticks']))]++;
    if(empty($w['last_reviewed'])||substr($w['last_reviewed'],0,10)!==$today)$eligible++;
  }
  $todayWords=min($eligible,$daily);
  echo json_encode(['success'=>true,'activeWords'=>$active,'learnedWords'=>$learned,'todayReviews'=>$todayReviews,'studyDay'=>$day,'todayWords'=>$todayWords,'distribution'=>$dist],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){http_response_code(500);echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);}
?>