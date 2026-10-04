-- Enrich dictionary and imported vocabulary with learner metadata.
alter table public.collection_words
  add column if not exists openjam_id uuid,
  add column if not exists definition_en text,
  add column if not exists example_en text,
  add column if not exists part_of_speech text,
  add column if not exists cefr_level text,
  add column if not exists frequency_rank integer,
  add column if not exists phonetic_us text,
  add column if not exists audio_us text;

alter table public.user_words
  add column if not exists definition_en text,
  add column if not exists example_en text,
  add column if not exists part_of_speech text,
  add column if not exists cefr_level text,
  add column if not exists frequency_rank integer,
  add column if not exists phonetic_us text,
  add column if not exists audio_us text;

create index if not exists idx_collection_words_openjam_id
  on public.collection_words(openjam_id);

create or replace function private.import_collection_words_impl(p_collection_id bigint)
returns jsonb
language plpgsql
security definer
set search_path=public,pg_catalog
as $$
declare
  uid uuid:=auth.uid();
  total_count integer;
  added_count integer;
begin
  if uid is null then raise exception 'not authenticated'; end if;
  if not exists(select 1 from public.collections where id=p_collection_id) then
    raise exception 'collection not found';
  end if;

  select count(*) into total_count
  from public.collection_words
  where collection_id=p_collection_id;

  insert into public.user_words(
    user_id,english,farsi,example,
    definition_en,example_en,part_of_speech,cefr_level,frequency_rank,phonetic_us,audio_us
  )
  select
    uid,cw.english,cw.farsi,cw.example,
    cw.definition_en,cw.example_en,cw.part_of_speech,cw.cefr_level,cw.frequency_rank,cw.phonetic_us,cw.audio_us
  from public.collection_words cw
  where cw.collection_id=p_collection_id
  on conflict do nothing;

  get diagnostics added_count=row_count;

  return jsonb_build_object(
    'success',true,
    'added',added_count,
    'skipped',greatest(0,total_count-added_count)
  );
end;
$$;

revoke execute on function private.import_collection_words_impl(bigint) from public,anon;
grant execute on function private.import_collection_words_impl(bigint) to authenticated;
