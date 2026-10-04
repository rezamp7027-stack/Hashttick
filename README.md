# Hashttick

Hashttick is an English vocabulary learning application migrated from the legacy PHP/MySQL design to Supabase Postgres + Supabase Auth.

## Backend status
- Supabase project: htfrixfcirgrhlmwtkjs
- Auth: Supabase Auth
- Database: PostgreSQL
- Personal data: RLS protected by auth.uid()
- Review engine: atomic review_word RPC
- Admin access: profiles.is_admin + private.is_admin()
- Collections: dictionary / story / PDF / video
- Review history and daily statistics
- SQL execution from the web admin panel is disabled for security
- AI credentials are no longer hard-coded

## Deployment
The supplied PHP application has been migrated in the working build to the Supabase REST/Auth API. PHP hosting is required for that build; GitHub Pages cannot execute PHP.

Environment variables:
- SUPABASE_URL
- SUPABASE_PUBLISHABLE_KEY
- GROQ_API_KEY (optional, for AI story generation)
- GROQ_MODEL (optional)

Do not commit secret keys.

## Administrator
After creating the first account, promote it explicitly:

```sql
update public.profiles
set is_admin = true
where email = 'YOUR_ADMIN_EMAIL';
```

The database schema and RLS policies are versioned under `supabase/migrations/`.
