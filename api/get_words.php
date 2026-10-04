<?php
require_once '../config.php'; requireLogin(); header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
try{
  $uid=getUserId();$token=getAccessToken();$today=date('Y-m-d');
  $settings=sb_query('user_settings',['select'=>'setting_key,setting_value','user_id'=>'eq.'.$uid,'limit'=>30],$token);
  $map=[];
  foreach($settings as $s)$map[$s['setting_key']]=$s['setting_value'];
  $dailyLimit=max(1,min(200,(int)($map['daily_limit']??20)));
  $startDate=preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($map['study_start_date']??''))?$map['study_start_date']:$today;
  $day=max(1,(new DateTime($startDate))->diff(new DateTime($today))->days+1);

  $rows=sb_query('user_words',[
    'select'=>'id,english,farsi,example,ticks,learned,in_re_review,last_reviewed,review_history,date_added',
    'user_id'=>'eq.'.$uid,
    'learned'=>'eq.false',
    'order'=>'in_re_review.desc,ticks.asc,date_added.asc,id.asc',
    'limit'=>5000
  ],$token);

  $out=[];
  foreach($rows as $w){
    if(!empty($w['last_reviewed'])&&substr($w['last_reviewed'],0,10)===$today)continue;
    $out[]=[
      'id'=>(int)$w['id'],
      'english'=>$w['english'],
      'farsi'=>$w['farsi'],
      'example'=>$w['example']??'',
      'ticks'=>(int)$w['ticks'],
      'learned'=>false,
      'inReReview'=>(bool)$w['in_re_review']
    ];
    if(count($out)>=$dailyLimit)break;
  }

  echo json_encode([
    'success'=>true,
    'currentDay'=>$day,
    'dailyLimit'=>$dailyLimit,
    'words'=>$out,
    'remainingEligible'=>count($out)
  ],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);
}
?>