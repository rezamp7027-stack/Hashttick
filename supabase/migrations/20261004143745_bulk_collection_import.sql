-- Bulk, idempotent collection import.
create or replace function private.import_collection_words_impl(p_collection_id bigint)
returns jsonb language plpgsql security definer set search_path=public, pg_catalog
as $$
declare uid uuid:=auth.uid(); total_count integer; added_count integer;
begin
  if uid is null then raise exception 'not authenticated'; end if;
  if not exists(select 1 from public.collections where id=p_collection_id) then raise exception 'collection not found'; end if;
  select count(*) into total_count from public.collection_words where collection_id=p_collection_id;
  insert into public.user_words(user_id,english,farsi,example)
  select uid,cw.english,cw.farsi,cw.example from public.collection_words cw
  where cw.collection_id=p_collection_id
  on conflict do nothing;
  get diagnostics added_count=row_count;
  return jsonb_build_object('success',true,'added',added_count,'skipped',greatest(0,total_count-added_count));
end;
$$;

create or replace function public.import_collection_words(p_collection_id bigint)
returns jsonb language sql security invoker set search_path=public, pg_catalog
as $$ select private.import_collection_words_impl(p_collection_id); $$;

revoke execute on function private.import_collection_words_impl(bigint) from public,anon;
grant execute on function private.import_collection_words_impl(bigint) to authenticated;
revoke execute on function public.import_collection_words(bigint) from public,anon;
grant execute on function public.import_collection_words(bigint) to authenticated;