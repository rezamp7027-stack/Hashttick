-- Only the controlled settings RPC may mutate client settings.
create or replace function public.save_user_settings(p_daily_limit integer,p_pronunciation boolean)
returns jsonb
language plpgsql
security definer
set search_path=public, pg_catalog
as $$
declare
  uid uuid:=auth.uid();
  v_limit integer:=least(200,greatest(1,coalesce(p_daily_limit,20)));
  v_pron text:=case when coalesce(p_pronunciation,true) then '1' else '0' end;
begin
  if uid is null then raise exception 'not authenticated'; end if;

  insert into public.user_settings(user_id,setting_key,setting_value)
  values(uid,'daily_limit',v_limit::text),(uid,'pronunciation',v_pron)
  on conflict(user_id,setting_key) do update
    set setting_value=excluded.setting_value;

  return jsonb_build_object(
    'success',true,
    'daily_limit',v_limit,
    'pronunciation',v_pron
  );
end;
$$;

revoke execute on function public.save_user_settings(integer,boolean) from public,anon;
grant execute on function public.save_user_settings(integer,boolean) to authenticated;

revoke insert,update,delete on public.user_settings from authenticated;
grant select on public.user_settings to authenticated;

revoke update on public.profiles from authenticated;
