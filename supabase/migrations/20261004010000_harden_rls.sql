-- Hashttick RLS hardening: prevent client privilege escalation and split broad policies.
drop policy if exists profiles_update_self on public.profiles;
revoke update on public.profiles from authenticated;

drop policy if exists settings_self on public.user_settings;
create policy settings_select_self on public.user_settings for select to authenticated using ((select auth.uid())=user_id);
create policy settings_insert_self on public.user_settings for insert to authenticated with check ((select auth.uid())=user_id);
create policy settings_update_self on public.user_settings for update to authenticated using ((select auth.uid())=user_id) with check ((select auth.uid())=user_id);
create policy settings_delete_self on public.user_settings for delete to authenticated using ((select auth.uid())=user_id);

drop policy if exists words_self on public.user_words;
create policy words_select_self on public.user_words for select to authenticated using ((select auth.uid())=user_id);
create policy words_insert_self on public.user_words for insert to authenticated with check ((select auth.uid())=user_id);
create policy words_update_self on public.user_words for update to authenticated using ((select auth.uid())=user_id) with check ((select auth.uid())=user_id);
create policy words_delete_self on public.user_words for delete to authenticated using ((select auth.uid())=user_id);

drop policy if exists stats_self on public.user_stats;
create policy stats_select_self on public.user_stats for select to authenticated using ((select auth.uid())=user_id);
create policy stats_insert_self on public.user_stats for insert to authenticated with check ((select auth.uid())=user_id);
create policy stats_update_self on public.user_stats for update to authenticated using ((select auth.uid())=user_id) with check ((select auth.uid())=user_id);
create policy stats_delete_self on public.user_stats for delete to authenticated using ((select auth.uid())=user_id);

drop policy if exists reviews_self on public.total_reviews;
create policy reviews_select_self on public.total_reviews for select to authenticated using ((select auth.uid())=user_id);
create policy reviews_insert_self on public.total_reviews for insert to authenticated with check ((select auth.uid())=user_id);
create policy reviews_update_self on public.total_reviews for update to authenticated using ((select auth.uid())=user_id) with check ((select auth.uid())=user_id);
create policy reviews_delete_self on public.total_reviews for delete to authenticated using ((select auth.uid())=user_id);

drop policy if exists collections_admin_write on public.collections;
create policy collections_admin_insert on public.collections for insert to authenticated with check (private.is_admin((select auth.uid())));
create policy collections_admin_update on public.collections for update to authenticated using (private.is_admin((select auth.uid()))) with check (private.is_admin((select auth.uid())));
create policy collections_admin_delete on public.collections for delete to authenticated using (private.is_admin((select auth.uid())));

drop policy if exists collection_words_admin_write on public.collection_words;
create policy collection_words_admin_insert on public.collection_words for insert to authenticated with check (private.is_admin((select auth.uid())));
create policy collection_words_admin_update on public.collection_words for update to authenticated using (private.is_admin((select auth.uid()))) with check (private.is_admin((select auth.uid())));
create policy collection_words_admin_delete on public.collection_words for delete to authenticated using (private.is_admin((select auth.uid())));

drop policy if exists collection_stories_admin_write on public.collection_stories;
create policy collection_stories_admin_insert on public.collection_stories for insert to authenticated with check (private.is_admin((select auth.uid())));
create policy collection_stories_admin_update on public.collection_stories for update to authenticated using (private.is_admin((select auth.uid()))) with check (private.is_admin((select auth.uid())));
create policy collection_stories_admin_delete on public.collection_stories for delete to authenticated using (private.is_admin((select auth.uid())));

revoke update on public.profiles from authenticated;
