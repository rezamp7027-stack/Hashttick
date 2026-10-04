drop function if exists public.admin_set_user_role(uuid, boolean);

revoke update on table public.profiles from authenticated;
grant update (is_admin) on table public.profiles to authenticated;

drop policy if exists profiles_update_admin_role on public.profiles;
create policy profiles_update_admin_role
on public.profiles
for update
to authenticated
using (
  (select private.is_admin((select auth.uid())))
  and id <> (select auth.uid())
)
with check (
  (select private.is_admin((select auth.uid())))
);
