# Hashttick

Hashttick is a Persian-first English vocabulary learning application using PHP, Supabase Auth, PostgreSQL and Row Level Security.

## Features
- Email/password authentication
- Personal vocabulary: add/edit/delete
- 8-tick review flow; 6 successful ticks = learned
- Red review and re-review flow
- Review history, daily/weekly statistics and tick distribution
- Study-day / streak-break reset behavior
- Dictionary collections and units
- Story collections and chapters
- PDF/video collection metadata
- Add collection words to personal vocabulary
- Word lookup and FastDic fallback
- Browser pronunciation
- Handwriting canvas + optional Tesseract fallback
- AI story generation via Groq using a server-side environment secret
- Admin collection, dictionary, story and statistics management

## Supabase
Project ref: `htfrixfcirgrhlmwtkjs`.

RLS is enabled on every exposed application table. Personal records are scoped by `auth.uid()`. Admin collection writes are controlled by the database-backed `private.is_admin()` helper.

Migrations:
- `supabase/migrations/20261004000000_hashttick_initial.sql`
- `supabase/migrations/20261004010000_harden_rls.sql`

RLS contract:
- `supabase/tests/rls_contract.sql`

## Security
- No MySQL runtime dependency.
- No raw SQL execution from the web admin panel.
- No hard-coded AI credentials.
- SSL certificate and hostname verification remain enabled for outbound cURL.
- Ordinary users cannot update `profiles.is_admin`.
- Personal tables are isolated by authenticated user.
- Admin writes require `is_admin=true`.

## Environment
Set these on the PHP host:
- `SUPABASE_URL=https://htfrixfcirgrhlmwtkjs.supabase.co`
- `SUPABASE_PUBLISHABLE_KEY`
- `GROQ_API_KEY` (optional)
- `GROQ_MODEL` (optional)

Never commit Supabase secret/service keys or Groq credentials.

## Deployment
This is a PHP application and must be deployed to PHP-capable hosting. GitHub Pages cannot execute the PHP backend.

To create an administrator after the first registration:
```sql
update public.profiles
set is_admin = true
where email = 'YOUR_ADMIN_EMAIL';
```
