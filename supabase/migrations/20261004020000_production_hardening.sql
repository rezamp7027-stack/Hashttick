-- Production hardening for the Hashttick database.
-- Keep the runtime schema aligned with the repository's RLS contract.

-- Remove an accidental duplicate permissive SELECT policy.
drop policy if exists collections_admin_select on public.collections;

-- The client only needs the private admin helper through RLS evaluation.
-- Never expose helper execution to anon/public.
revoke execute on function private.is_admin(uuid) from public, anon;
grant execute on function private.is_admin(uuid) to authenticated;

-- These helpers are trigger/internal functions and must not be callable by clients.
revoke execute on function public.touch_updated_at() from public, anon, authenticated;
revoke execute on function public.handle_new_user() from public, anon, authenticated;
revoke execute on function public.review_word(bigint, boolean) from public, anon;
grant execute on function public.review_word(bigint, boolean) to authenticated;

-- Remove an unused duplicate admin helper if it exists.
revoke execute on function public.is_admin(uuid) from public, anon, authenticated;

-- Support FK lookups and review history joins.
create index if not exists idx_total_reviews_word on public.total_reviews(word_id);

-- Ensure all application tables retain RLS.
alter table public.profiles enable row level security;
alter table public.user_settings enable row level security;
alter table public.user_words enable row level security;
alter table public.user_stats enable row level security;
alter table public.total_reviews enable row level security;
alter table public.collections enable row level security;
alter table public.collection_words enable row level security;
alter table public.collection_stories enable row level security;