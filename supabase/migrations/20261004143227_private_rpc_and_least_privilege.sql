-- Hide privileged RPC implementations in the private schema and keep public wrappers invoker-only.
create or replace function private.review_word_impl(p_word_id bigint,p_correct boolean)
returns jsonb language plpgsql security definer set search_path=public, pg_catalog as $$
declare uid uuid:=auth.uid(); w public.user_words%rowtype; hist jsonb; reds integer:=0; new_ticks integer; learned_now boolean;
begin
  if uid is null then raise exception 'not authenticated'; end if;
  select * into w from public.user_words where id=p_word_id and user_id=uid for update;
  if not found then raise exception 'word not found'; end if;
  if w.learned then raise exception 'word already learned'; end if;
  if w.last_reviewed is not null and (w.last_reviewed at time zone 'Asia/Tehran')::date=(now() at time zone 'Asia/Tehran')::date then raise exception 'already reviewed today'; end if;
  hist:=coalesce(w.review_history,'[]'::jsonb);
  if p_correct then
    new_ticks:=least(8,w.ticks+1);
    hist:=hist||jsonb_build_array(jsonb_build_object('date',to_char(now() at time zone 'Asia/Tehran','YYYY-MM-DD HH24:MI:SS'),'type','green','tick_number',new_ticks));
    learned_now:=new_ticks>=6;
    update public.user_words set ticks=new_ticks,last_reviewed=now(),learned=learned_now,in_re_review=false,review_history=hist where id=w.id;
  else
    hist:=hist||jsonb_build_array(jsonb_build_object('date',to_char(now() at time zone 'Asia/Tehran','YYYY-MM-DD HH24:MI:SS'),'type','red','previous_ticks',w.ticks));
    select count(*) into reds from jsonb_array_elements(hist) x where x->>'type'='red';
    if reds>=3 then
      update public.user_words set ticks=0,last_reviewed=now(),learned=false,in_re_review=true,review_history='[]'::jsonb where id=w.id;
      new_ticks:=0; learned_now:=false;
    else
      update public.user_words set last_reviewed=now(),in_re_review=false,review_history=hist where id=w.id;
      new_ticks:=w.ticks; learned_now:=w.learned;
    end if;
  end if;
  insert into public.total_reviews(user_id,word_id,is_correct,tick_after,response) values(uid,w.id,p_correct,new_ticks,jsonb_build_object('english',w.english,'farsi',w.farsi,'example',coalesce(w.example,''),'red_count',reds));
  insert into public.user_stats(user_id,stat_date,total_reviews,correct_answers,wrong_answers) values(uid,(now() at time zone 'Asia/Tehran')::date,1,case when p_correct then 1 else 0 end,case when p_correct then 0 else 1 end)
  on conflict(user_id,stat_date) do update set total_reviews=public.user_stats.total_reviews+1,correct_answers=public.user_stats.correct_answers+excluded.correct_answers,wrong_answers=public.user_stats.wrong_answers+excluded.wrong_answers,updated_at=now();
  return jsonb_build_object('success',true,'newTicks',new_ticks,'learned',learned_now,'inReReview',(select in_re_review from public.user_words where id=w.id),'totalReds',reds);
end; $$;

create or replace function public.review_word(p_word_id bigint,p_correct boolean)
returns jsonb language sql security invoker set search_path=public, pg_catalog
as $$ select private.review_word_impl(p_word_id,p_correct); $$;

create or replace function private.save_user_settings_impl(p_daily_limit integer,p_pronunciation boolean)
returns jsonb language plpgsql security definer set search_path=public, pg_catalog as $$
declare uid uuid:=auth.uid(); v_limit integer:=least(200,greatest(1,coalesce(p_daily_limit,20))); v_pron text:=case when coalesce(p_pronunciation,true) then '1' else '0' end;
begin
  if uid is null then raise exception 'not authenticated'; end if;
  insert into public.user_settings(user_id,setting_key,setting_value) values(uid,'daily_limit',v_limit::text),(uid,'pronunciation',v_pron)
  on conflict(user_id,setting_key) do update set setting_value=excluded.setting_value;
  return jsonb_build_object('success',true,'daily_limit',v_limit,'pronunciation',v_pron);
end; $$;

create or replace function public.save_user_settings(p_daily_limit integer,p_pronunciation boolean)
returns jsonb language sql security invoker set search_path=public, pg_catalog
as $$ select private.save_user_settings_impl(p_daily_limit,p_pronunciation); $$;

revoke execute on function private.review_word_impl(bigint,boolean) from public,anon;
grant execute on function private.review_word_impl(bigint,boolean) to authenticated;
revoke execute on function private.save_user_settings_impl(integer,boolean) from public,anon;
grant execute on function private.save_user_settings_impl(integer,boolean) to authenticated;

revoke all on public.profiles from authenticated;
grant select on public.profiles to authenticated;
revoke all on public.user_settings from authenticated;
grant select on public.user_settings to authenticated;
revoke all on public.user_words from authenticated;
grant select,delete on public.user_words to authenticated;
grant insert(user_id,english,farsi,example) on public.user_words to authenticated;
grant update(english,farsi,example) on public.user_words to authenticated;
revoke all on public.user_stats from authenticated;
grant select on public.user_stats to authenticated;
revoke all on public.total_reviews from authenticated;
grant select on public.total_reviews to authenticated;