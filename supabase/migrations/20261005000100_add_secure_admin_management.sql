-- Hashttick: secure promotion/revocation of admin access.
-- Applied to Supabase project htfrixfcirgrhlmwtkjs.

create or replace function public.admin_set_user_role(
  target_user_id uuid,
  make_admin boolean
)
returns jsonb
language plpgsql
security definer
set search_path = pg_catalog
as $$
declare
  caller uuid := auth.uid();
  target_exists boolean;
  admin_count integer;
begin
  if caller is null then
    raise exception 'not authenticated';
  end if;

  if not private.is_admin(caller) then
    raise exception 'admin access required';
  end if;

  if target_user_id is null then
    raise exception 'target user is required';
  end if;

  if target_user_id = caller then
    raise exception 'cannot change your own admin status';
  end if;

  select exists(select 1 from public.profiles where id = target_user_id)
    into target_exists;

  if not target_exists then
    raise exception 'target user not found';
  end if;

  if make_admin = false then
    select count(*) into admin_count
    from public.profiles
    where is_admin = true;

    if admin_count <= 1 then
      raise exception 'cannot remove the last admin';
    end if;
  end if;

  update public.profiles
  set is_admin = make_admin,
      updated_at = now()
  where id = target_user_id;

  return jsonb_build_object(
    'success', true,
    'user_id', target_user_id,
    'is_admin', make_admin
  );
end;
$$;

revoke all on function public.admin_set_user_role(uuid, boolean) from public;
grant execute on function public.admin_set_user_role(uuid, boolean) to authenticated;
