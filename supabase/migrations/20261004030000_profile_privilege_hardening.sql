-- Restrict profile updates so users cannot self-promote to admin.
revoke update on public.profiles from authenticated;
grant update (username, email) on public.profiles to authenticated;

-- Keep admin authorization isolated from client-writable columns.
revoke execute on function public.is_admin(uuid) from public, anon, authenticated;
