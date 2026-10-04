# Hashttick

Hashttick is a Persian-first English vocabulary learning application built with PHP, Supabase Auth, PostgreSQL and Row Level Security.

## Current application

The production branch contains:

- Email/password authentication with hardened PHP sessions
- Responsive Persian-first dashboard
- Daily study session and atomic PostgreSQL-backed review flow
- Personal vocabulary CRUD
- Six-success threshold for learned words
- Red/re-review workflow
- Browser pronunciation
- Handwriting practice canvas
- Dictionary/story/library collections
- Import collection words into personal vocabulary
- Daily and historical statistics
- User settings
- Admin console for users and learning content
- Admin CRUD for collections, collection words and story chapters
- Server-side Supabase REST adapter
- Versioned Supabase migrations
- RLS and database-backed admin authorization
- GitHub Actions PHP/JavaScript syntax checks

## Architecture

`PHP pages → PHP API → Supabase Auth / PostgREST → PostgreSQL + RLS`

The browser never receives a Supabase service-role key. The server uses the publishable key together with the user's access token.

## Supabase

The migration files are versioned under `supabase/migrations/`.

The intended production project for this repository is the **Hashttick Supabase project**. Do not point the application at an unrelated project.

Current migrations include:

1. Initial schema and review engine
2. RLS hardening
3. Production permission/index hardening
4. Profile privilege hardening

All application tables currently have RLS enabled, and the Supabase security advisor reports no security lints.

## Environment

Required host environment:

- `SUPABASE_URL`
- `SUPABASE_PUBLISHABLE_KEY`

Optional:

- `GROQ_API_KEY`
- `GROQ_MODEL`

Never commit service-role keys, secret keys, passwords or API tokens.

## Deployment

Hashttick requires PHP hosting. GitHub Pages cannot execute the PHP backend.

Recommended PHP requirements:

- PHP 8.1+
- cURL enabled
- JSON enabled
- HTTPS
- environment variables configured

After the first user registers, promote the administrator explicitly from a protected database session:

```sql
update public.profiles
set is_admin = true
where email = 'YOUR_ADMIN_EMAIL';
```

The application deliberately does not grant ordinary authenticated users permission to update `profiles.is_admin`.

## Security model

- RLS is enabled on every application table.
- Personal rows are scoped by `auth.uid()`.
- Admin collection/content writes require `private.is_admin()`.
- `profiles.is_admin` is not client-writable.
- Internal helper functions are not executable by anonymous users.
- PHP sessions use HttpOnly/SameSite cookies and session ID regeneration after authentication.
- TLS certificate and hostname verification remain enabled for outbound cURL.
- No raw SQL execution is exposed through the web admin.
- The review engine is authoritative in PostgreSQL through `review_word()`.

## Admin console

The admin console supports:

- Create/delete learning collections
- Configure collection type, cover image/color, PDF and video URLs
- Add/delete collection vocabulary
- Set vocabulary unit numbers and examples
- Add/delete story chapters
- View registered users and administrator status

## Verification

Before public launch:

1. Configure the intended Supabase project environment variables on PHP hosting.
2. Apply all migrations in order.
3. Confirm the first administrator explicitly.
4. Run the repository's GitHub Actions checks.
5. Verify registration, login, study/review, vocabulary CRUD, collection import, statistics, settings and admin CRUD against a real authenticated session.

## Important

Do not store credentials or database passwords in this repository.