# Hashttick

Hashttick is a Persian-first English vocabulary learning application built with PHP, Supabase Auth, PostgreSQL and Row Level Security.

## Current application

The main branch contains:

- Email/password authentication with hardened PHP sessions
- Automatic access-token refresh for expired sessions
- CSRF protection for authenticated state-changing requests
- Responsive Persian-first dashboard
- Daily study session backed by PostgreSQL
- Atomic review engine with six-success learning threshold
- Red/re-review workflow
- Personal vocabulary CRUD with examples and edit support
- Case-insensitive duplicate protection
- Browser pronunciation with a user preference
- Pointer-based handwriting practice canvas
- Dictionary/story/library collections
- Collection vocabulary browsing and import into personal vocabulary
- PDF and video collection links
- Daily and historical statistics with recent-review snapshots
- User settings for daily study limit and pronunciation
- Admin console for users and learning content
- Admin create/edit/delete for collections, collection words and story chapters
- Server-side Supabase REST adapter
- Versioned Supabase migrations
- RLS and database-backed admin authorization
- GitHub Actions PHP/JavaScript syntax checks

## Architecture

`PHP pages → PHP API → Supabase Auth / PostgREST → PostgreSQL + RLS`

The browser never receives a Supabase service-role key. The server uses the publishable key together with the user's access token.

Learning state is server-authoritative: clients cannot directly write ticks, learned state, review history, review records or study statistics. The `review_word()` PostgreSQL RPC performs the state transition atomically.

## Supabase

The migration files are versioned under `supabase/migrations/`.

The intended production backend for this repository is the Hashttick Supabase project. Do not point the application at an unrelated project.

The migration sequence includes:

1. Initial schema and review engine
2. RLS hardening
3. Production permission/index hardening
4. Profile privilege hardening
5. Learning-state integrity hardening
6. Secure review engine
7. Controlled user-settings writes

All application tables currently have RLS enabled. The Supabase security advisor should remain clean before release.

## Environment

Required host environment:

- `SUPABASE_URL`
- `SUPABASE_PUBLISHABLE_KEY`

Optional:

- `GROQ_API_KEY`
- `GROQ_MODEL`

Never commit service-role keys, secret keys, passwords, database passwords, sessions or API tokens.

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
- Learning state and statistics are not client-writable.
- `review_word()` is authenticated-only and security-definer with a fixed search path.
- The settings RPC is authenticated-only and exposes only supported settings.
- Internal helper functions are not executable by anonymous users.
- PHP sessions use HttpOnly/SameSite cookies and session ID regeneration after authentication.
- Authenticated mutations require a CSRF token.
- TLS certificate and hostname verification remain enabled for outbound cURL.
- No raw SQL execution is exposed through the web admin.
- Collection media URLs are restricted to HTTP/HTTPS.
- Collection word totals are derived by a database trigger rather than manual client/API counting.
- Case-insensitive unique indexes prevent duplicate English entries that differ only by letter case.

## Admin console

The admin console supports:

- Create/edit/delete learning collections
- Configure collection type, cover image/color, PDF and video URLs
- Add/edit/delete collection vocabulary
- Set vocabulary unit numbers and examples
- Add/edit/delete story chapters
- View registered users and administrator status

## Verification

The repository includes GitHub Actions checks for PHP and JavaScript syntax.

Before public launch:

1. Configure the intended Supabase project environment variables on PHP hosting.
2. Apply all migrations in order, or confirm the existing production migration history already contains the equivalent changes.
3. Confirm the first administrator explicitly.
4. Confirm GitHub Actions completes successfully on the current main branch.
5. Verify registration and email-confirmation behavior.
6. Verify login, automatic session refresh and logout.
7. Verify adding/editing/deleting personal vocabulary.
8. Verify study/review, duplicate-review protection, six-success learning and red/re-review behavior.
9. Verify collection browsing, vocabulary import, story reading, PDF/video links.
10. Verify statistics, streak calculations and settings.
11. Verify admin collection/content CRUD and permission enforcement against a real authenticated admin session.

## Important

Do not store credentials or database passwords in this repository.
