-- Keep derived collection counters and client data boundaries enforced in PostgreSQL.

alter table public.user_words
  add column if not exists example text;

create or replace function public.sync_collection_total_words()
returns trigger
language plpgsql
security definer
set search_path=public, pg_catalog
as $$
begin
  if tg_op = 'DELETE' then
    update public.collections
       set total_words = (
         select count(*) from public.collection_words cw
         where cw.collection_id = old.collection_id
       )
     where id = old.collection_id;
    return old;
  end if;

  if tg_op = 'UPDATE' and old.collection_id <> new.collection_id then
    update public.collections
       set total_words = (
         select count(*) from public.collection_words cw
         where cw.collection_id = old.collection_id
       )
     where id = old.collection_id;
  end if;

  update public.collections
     set total_words = (
       select count(*) from public.collection_words cw
       where cw.collection_id = new.collection_id
     )
   where id = new.collection_id;

  return new;
end;
$$;

revoke execute on function public.sync_collection_total_words() from public, anon, authenticated;

drop trigger if exists sync_collection_total_words on public.collection_words;
create trigger sync_collection_total_words
after insert or update or delete on public.collection_words
for each row execute function public.sync_collection_total_words();

update public.collections c
set total_words = (
  select count(*) from public.collection_words cw
  where cw.collection_id = c.id
);

create unique index if not exists user_words_user_id_lower_english_key
  on public.user_words(user_id, lower(english));

create unique index if not exists collection_words_collection_id_lower_english_unit_key
  on public.collection_words(collection_id, lower(english), unit);

revoke insert on public.user_words from authenticated;
grant insert(user_id, english, farsi, example) on public.user_words to authenticated;

revoke update on public.user_words from authenticated;
grant update(english, farsi, example) on public.user_words to authenticated;

grant delete on public.user_words to authenticated;

revoke insert, update, delete on public.user_stats from authenticated;
grant select on public.user_stats to authenticated;

revoke insert, update, delete on public.total_reviews from authenticated;
grant select on public.total_reviews to authenticated;
