# Launch notes: staging and production

Written Nov 26, 2026, in Phase 7 (QA). **Nothing here has been run.** Hosting on ICONA's server (Bertha) is a
separate job that Dave starts; these notes are what that job needs to know. The general first-deploy steps are in
README.md ("First deploy on a server"); this file adds what is particular to this site.

## Environment types

| | Local (DDEV) | Staging | Production |
|---|---|---|---|
| `SS_ENVIRONMENT_TYPE` | `dev` | `test` | `live` |

- **`dev`** shows PHP errors to visitors, adds an "Error detail" line to the 404 page and lets anyone run
  `dev/build` from the address bar. Never on a public host.
- **`test`** hides errors, as live does. Staging stays behind basic auth (Plesk password protection) the whole
  time: the pages say `index, follow`, so the password is the only thing keeping staging out of search results.
  Staging's `robots.txt` never gets a Sitemap line.
- **`live`** hides errors. A server error shows the static `public/assets/error-500.html` (see "Error pages"
  below). `?flush=1` in a URL works only for a logged-in admin, which is why the runbook flushes from
  `/admin/?flush=1`. No `SS_DEFAULT_ADMIN_*` lines in `.env`.

## `.env` on each server

Values live on the server only, never in the repo or in chat.

| Variable | Staging | Production |
|---|---|---|
| `SS_ENVIRONMENT_TYPE` | `test` | `live` |
| `SS_BASE_URL` | the staging host, `https://` | `https://truegritclean.ca` (the apex is canonical, brief §9; www redirects to it) |
| `SS_ALLOWED_HOSTS` | the staging host | `truegritclean.ca,www.truegritclean.ca` |
| `SS_DATABASE_*` | that subscription's database | that subscription's database |
| `SS_ERROR_LOG` | `silverstripe.log` | `silverstripe.log` |
| `MAILER_DSN` | the Mailgun DSN for `mg.truegritclean.ca` | the same |
| `SS_SEND_ALL_EMAILS_FROM` | `website@mg.truegritclean.ca` | the same |
| `SS_SEND_ALL_EMAILS_TO` | an inbox Dave reads, unless the phone test deliberately mails hello@ | not set |
| `SS_NOCAPTCHA_SITE_KEY` / `_SECRET_KEY` | the pair in the local `.env` (registration "truegritclean.ca"; it already covers any host under truegritclean.ca) | the same pair |
| `SS_DEFAULT_ADMIN_*` | not set | not set |

The local site logs "Allowed hosts has not been set" on every request; on a server, a missing `SS_ALLOWED_HOSTS`
leaves Host-header abuse open.

## Server settings (Plesk)

- **PHP 8.4** for the subscription (the handler, not just the CLI binary): `composer.lock` needs PHP 8.4.1 or later
  (four Symfony 8.1 packages). Bertha has `/opt/plesk/php/8.4/bin/php`.
- **Uploads:** `upload_max_filesize` 10M or more, `post_max_size` 60M or more (5 photos of 10 MB; staging's 110M is
  fine too), `max_file_uploads` 20, and nginx's request size to match. Plesk's defaults (2M / 8M) refuse almost every
  phone photo. The photos are emailed, never stored. Resizing raises the time limit for that request (180 seconds),
  so don't lock `max_execution_time` with a `php_admin_value`. It does not raise `memory_limit`, and need not: with
  Imagick the memory used to open a photo is outside PHP's limit (up to about 850 MB for the largest photo the form
  opens, one request at a time for the whole site). Details: README, "Quote form".
- **Visitor address:** the quote form's limit (3 requests in 10 minutes per address; 20 an hour for the whole site
  whatever the address) needs PHP to see the visitor's
  address. With Cloudflare on DNS only (as now) Plesk passes it on. If the orange cloud is ever turned on, set
  `SS_TRUSTED_PROXY_IPS` to Cloudflare's ranges first, or every visitor shares one count.
- **Paths:** the Git deployment path is the project root and the document root is `<project root>/public`.
- **TLS:** Let's Encrypt on the server.
- **Mail:** the hello@truegritclean.ca inbox exists, and `mg.truegritclean.ca` is added and verified in Mailgun
  (its DNS records go in at the registrar).

## Building a new environment

`public/assets` is not in git. The photos reach a server through the seed (web copies in `app/seed/images/`).

1. `composer install --no-dev`
2. `vendor/bin/sake db:build --flush`
3. `vendor/bin/sake tasks:my-task` (block styles, the 404 and 500 pages)
4. `vendor/bin/sake tasks:seed --publish` (9 pages, blocks, Settings text)
5. `vendor/bin/sake tasks:seed-images --publish` (photos, alt text, credits line)
6. `vendor/bin/sake tasks:seed-form --publish` (the quote form)
7. **Error pages:** in the CMS, open "Page not found" and press Publish, then the same for "Server error".
8. Open `/admin/?flush=1` as an admin, then probe (README: sitemap probe; the quote form).

Settings > Claims stays unticked: tick a box only when Rebekah has confirmed that claim is true.

If the local database is copied to the server instead of seeding, the content and Settings come with it, and the
photos must be copied as well (`public/assets`, minus `.protected/`). Step 7 still applies.

## Error pages

`tasks:my-task` writes the static copies of the 404 and 500 pages (`public/assets/error-404.html`,
`error-500.html`) when it creates those pages, which is before the seed has given the site its name. Found in
Phase 7: the local copies read "Your Site Name" and "your tagline here", with none of the footer. The 404 page
visitors see is rendered fresh, but a server error in `live` serves the static 500 copy as it is. Publishing
the two pages rewrites both copies (step 7). Do it again after any change to the templates or Settings that
should show on them.

## Before launch

- **Rebekah confirms** the claims entered without the bracket note (list in the docs repo's task list, item 11),
  above all **HEPA**: it is in the Home and Renovation meta descriptions, `/llms.txt`, the Services page and the
  Post-Construction and Renovation lists, none of which Settings > Claims controls. Also the Privacy page's hosting
  and retention lines.
- **No missing content left** (README, "Missing content"). The About story, both team bios and the Privacy effective
  date are highlighted stand-ins until Rebekah's text arrives; an editor replaces each whole `[missing]...[/missing]`.
  Gate, against production once it answers (staging needs `-u dave` on both curls): every sitemap page must print
  0, and the team photos are either uploaded or Rebekah has agreed to launch without them (on live, an empty photo
  slot shows the card's text only).
  `for u in $(curl -s https://truegritclean.ca/sitemap.xml/sitemap/SilverStripe-CMS-Model-SiteTree/1 | grep -o '<loc>[^<]*' | sed 's/<loc>//'); do printf '%s %s\n' "$(curl -s "$u" | grep -c 'class="content-missing"')" "$u"; done`
- **Quote form, a site seeded before Nov 27, 2026** (staging was): submissions must not be saved. Run
  `vendor/bin/sake tasks:seed-form --publish` (it prints `[set] DisableSaveSubmissions: 1`, or `[ok]` once done), or
  in the CMS tick the quote page's Configuration tab > "Disable Saving Submissions to Server" and Publish. Then check
  that Files has no `Form-submissions` folder with photos in it; delete any left from earlier tests.
- **A site seeded before Nov 27, 2026** (staging was) still holds the old bracket placeholders. After the code
  deploy, `vendor/bin/sake tasks:seed-placeholders --publish` swaps in the highlighted stand-ins; it changes only a
  field that still holds exactly what the seed wrote, publishes only that record, and is safe to run again.
- **Logo:** a PNG of the monogram, at least 112 x 112 px, in Settings > Main > Logo (the schema's `logo` and
  `image`). The header logo is still the CSS placeholder until the SVG arrives.
- **On staging:** reCAPTCHA refuses a post with no token (a server-side check, no password needed in an outside tool).
- **Lighthouse** on staging over https, where the server's compression and caching apply: Home, a service page and
  Quote on Mobile, Home on Desktop, aiming for 90+ in all four categories. Dave moved this from the local site to
  staging (Nov 26).
- **Rich Results Test** on staging: Google can't get past the staging password, so use the test's Code tab and paste
  the page source (Home and one service page). Local checks found nothing missing apart from the logo. Once
  production is live, run it again by URL (below).

## After production is live

Outside tools run here, not behind the staging password (Dave, Nov 27); the order is in the docs repo's
`staging-2026-11/RUNBOOK-staging.md`, "After launch: testing".

- **Phone test first:** 5 photos from the iPhone at the camera's largest setting (the fullest email the form can
  send). One email arrives with 5 JPEG photos attached, upright, not in spam;
  the quote page's Submissions tab stays empty and Files gains nothing. It also proves a real visitor passes reCAPTCHA.
- `public/robots.txt`: add `Sitemap: https://truegritclean.ca/sitemap.xml` (a PR, like any code change).
- Run the README's sitemap probe against `https://truegritclean.ca`.
- Search Console: add the property, submit the sitemap, and watch **Last read** until it shows a date.
- Google's Rich Results Test by URL on the home page and one service page.
- Google Business Profile as a service-area business with the address hidden (brief §9).
