# Hashttick — InfinityFree deployment

Hashttick is a PHP application and must run on PHP hosting. GitHub Pages cannot execute the PHP backend.

## 1. Create the hosting account

Create a free InfinityFree account and create a website/subdomain.

InfinityFree currently provides PHP 8.4, HTTPS/SSL, FTP and an online file manager.

## 2. Upload the repository

Upload the contents of this repository to the site's `htdocs` directory.

Do not upload `config.local.php` from Git history because it is intentionally ignored. Instead create it directly on the server.

For larger projects, FTP is more reliable than the browser file manager.

## 3. Configure Supabase

Copy `config.local.php.example` to:

```
/htdocs/config.local.php
```

Then replace:

```
PASTE_YOUR_SUPABASE_PUBLISHABLE_KEY_HERE
```

with the **publishable** key from the Hashttick Supabase project's Connect/API settings.

Do not use a Supabase secret/service-role key.

The current Hashttick project URL is already included in the example:

```
https://htfrixfcirgrhlmwtkjs.supabase.co
```

## 4. Required PHP capabilities

The host needs:

- PHP 8.1+
- cURL
- JSON
- HTTPS

InfinityFree currently advertises PHP 8.4 and cURL/HTTPS-compatible PHP hosting.

## 5. First launch checklist

Open the site's URL and verify:

1. `register.php` loads.
2. Registration succeeds.
3. Email confirmation works according to the Supabase Auth configuration.
4. `login.php` accepts the account.
5. Dashboard loads.
6. Personal vocabulary CRUD works.
7. Study/review works.
8. Collections and imports work.
9. Statistics load.
10. Admin access works only for the explicitly promoted administrator.

## Important

Do not put `config.local.php`, a Supabase secret key, a database password, or any session/token in GitHub.

If InfinityFree's control panel is temporarily unavailable, wait for the hosting provider's service to recover before creating the account.
