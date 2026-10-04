<?php
require_once '../config.php'; requireLogin(); header('Content-Type: application/json; charset=utf-8');
try{
  $uid=getUserId();$t=getAccessToken();
  $words=sb_query('user_words',['select'=>'id,ticks,learned','user_id'=>'eq.'.$uid,'limit'=>5000],$t);
  $reviews=sb_query('total_reviews',['select'=>'word_id,reviewed_at,is_correct,tick_after,response','user_id'=>'eq.'.$uid,'order'=>'reviewed_at.desc','limit'=>1000],$t);
  $stats=sb_query('user_stats',['select'=>'stat_date,total_reviews,correct_answers,wrong_answers','user_id'=>'eq.'.$uid,'order'=>'stat_date.asc','limit'=>1000],$t);
  $total=count($reviews);$correct=0;
  foreach($reviews as $r)$correct+=(int)$r['is_correct'];
  $dist=array_fill(0,7,0);$learned=0;$active=0;
  foreach($words as $w){if(!empty($w['learned'])){$learned++;$dist[6]++;}else{$active++;$dist[min(5,max(0,(int)$w['ticks']))]++;}}
  $map=[];foreach($stats as $s)$map[$s['stat_date']]=(int)$s['total_reviews'];
  $dates=[];$cursor=new DateTime('-13 days');$end=new DateTime('today');
  while($cursor<=$end){$d=$cursor->format('Y-m-d');$dates[]=['date'=>$d,'total_reviews'=>$map[$d]??0];$cursor->modify('+1 day');}
  $best=0;$run=0;$prev=null;
  foreach($stats as $s){
    if((int)$s['total_reviews']>0){
      if($prev!==null){$prevDate=new DateTime($prev);$curDate=new DateTime($s['stat_date']);if($prevDate->diff($curDate)->days===1)$run++;else $run=1;}else $run=1;
      $best=max($best,$run);
    }else{$run=0;}
    $prev=$s['stat_date'];
  }
  $recent=[];
  foreach(array_slice($reviews,0,10) as $r){
    $response=is_array($r['response']??null)?$r['response']:[];
    $recent[]=['english'=>(string)($response['english']??('#'.$r['word_id'])),'is_correct'=>(bool)$r['is_correct'],'reviewed_at'=>$r['reviewed_at'],'tick_after'=>(int)$r['tick_after']];
  }
  echo json_encode(['success'=>true,'totalReviews'=>$total,'accuracy'=>$total?round($correct/$total*100):0,'learnedWords'=>$learned,'activeWords'=>$active,'bestStreak'=>$best,'distribution'=>$dist,'daily'=>$dates,'recent'=>$recent],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){http_response_code(500);echo json_encode(['success'=>false,'error'=>sb_error_message($e)],JSON_UNESCAPED_UNICODE);}
?>