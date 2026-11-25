# ICONA SilverStripe 6 starter

The starting point for every new ICONA SilverStripe 6 site. It carries the fleet pattern (page types,
blocks and variants, theme, forms, SEO layer, gates) and no client's identity. New sites start here, not
from the last client's repo, and every gotcha a build finds that the starter also has is fixed here first.

Current version: see [CHANGELOG.md](CHANGELOG.md). Stack: SilverStripe 6.2 (recipe-cms), PHP 8.4,
MariaDB 10.11, elemental, userforms with reCAPTCHA v3, blog, linkfield, googlesitemaps, SEO module,
redirects (silverstripe/redirectedurls).

## Start a new site from a release

Take a release tag as a snapshot, not a clone: the starter's history names the builds its fixes came from,
and a client repo should not carry that.

```bash
S="/Users/dave/Documents/Projects/Bitbucket-Migration/icona-ss6-starter"
cd "/Users/dave/Documents/Projects/Bitbucket-Migration/cloned"
T=$(git -C "$S" describe --tags --abbrev=0) && echo "starter $T" && mkdir new-site && git -C "$S" archive "$T" | tar -x -C new-site
```

It prints the tag it used, so a site is never built from a release you did not mean. `git -C "$S" tag -l`
lists them; CHANGELOG.md says what each one fixed.

Then, in the new repo, before its first commit on an `rc/*` branch:

1. **Identity.** Replace both logo placeholders (dashed LOGO, 240 x 44): `logo-white.svg` is the one the
   header loads over the dark hero, `logo.svg` the one the footer loads on white. Then `public/favicon.svg`
   and `public/favicon.ico`, and the names in `public/site.webmanifest`, `composer.json` (`name`,
   `description`) and `themes/app-theme/package.json`.
2. **SEO config.** `app/_config/seo.yml`: `area_served` and `short_brand`.
3. **robots.txt.** Add the site's own `Sitemap:` line (absolute URL, canonical host) at launch. The starter
   ships none on purpose.
4. **Design tokens.** `themes/app-theme/css/src/_tokens.scss` from the site's design. Figma reports
   letter-spacing as a percentage, so the em value is that number over 100 — converting it as px tracks
   text about five times too tight. Fonts are system stacks until the design names one; self-host it
   under `themes/app-theme/fonts/`. `editor.scss` styles the CMS editor and cannot read CSS variables,
   so its colours and fonts are literals: change them alongside the tokens or the editor keeps showing
   the starter's palette.
5. **Lineage denylist.** Remove only this site's own names from `scripts/lineage-denylist.txt`.
6. **README.md.** Replace this file with the site's own.

## First run on the Mac

```bash
cd "/Users/dave/Documents/Projects/Bitbucket-Migration/cloned/new-site"
cp .env.example .env
ddev start
ddev composer install
ddev exec vendor/bin/sake db:build --flush
ddev exec vendor/bin/sake tasks:my-task
```

Set `SS_BASE_URL` in `.env` to the URL `ddev start` prints. `tasks:my-task` registers the block variants
(BlockStyle rows) and creates the 404 and 500 error pages; it is safe to re-run.

Theme build (after any change in `css/src` or `js/src`; commit `css/dist` and `js/dist`):

```bash
cd "/Users/dave/Documents/Projects/Bitbucket-Migration/cloned/new-site/themes/app-theme"
npm ci
npm run build
```

## First deploy on a server

The repo is the code; three things exist only on the server, and a deploy that skips them looks fine and
serves a broken site.

```bash
cd "<project root on the server>"
mkdir -p silverstripe-cache && chown <domain-user>:psacln silverstripe-cache
sudo -u <domain-user> composer install --no-dev
sudo -u <domain-user> <php-binary> vendor/bin/sake db:build --flush
sudo -u <domain-user> <php-binary> vendor/bin/sake tasks:my-task
```

`composer install` also regenerates `public/_resources`, which is git-ignored on purpose. `db:build`
creates the schema; `tasks:my-task` writes the BlockStyle rows every block variant needs and the 404/500
pages — both live in the database, so every new environment needs them, not just the first.

Then open `/admin/?flush=1` in a browser as an admin: a CLI flush does not clear the PHP-FPM process's
config cache, so a config or class change is not live until a request rebuilds it. Only then probe the
site: a page, the sitemap (see below), and a real submission through the contact form.

## What the site owner fills in, in the CMS

- **Contact page > Recipients:** at least one address. A form with no recipient accepts submissions and
  delivers them nowhere; the CMS warns, but only if someone reads it. Send one real test before launch.
  Submissions are also stored in the CMS by default (Submissions tab). Email is the delivery: those rows
  are never migrated or reconciled when a site moves.
- **Settings > Contact Info:** address, email, phone. Shown in the contact banner, the contact page and the
  schema. Empty lines are hidden.
- **Settings > Contact Banner:** heading, text, button label and disclaimer for the banner above the footer.
  No wording ships in the templates; use the firm's approved disclaimer exactly.
- **Contact page > Form Fields:** the form itself, including a Spam Protection field (its title is for
  editors; visitors never see it).
- **Menus:** on a top-level page, Settings > Visibility. "Show in Main Menu" puts it in the header menu,
  "Show in Footer Menu" in the footer's Firm column, and the two are independent: a contact page reached
  by a header button can still be listed in the footer. "Show in menus?" must stay ticked for either, and
  pages below the top level appear in neither.
- **Any page > Hero > Hero image focus:** which side of the hero photo stays in view on a phone. Text must
  never cover a face: after changing a hero photo or this setting, look at the page on a phone.
- **Breadcrumbs** use each page's navigation label (Main tab). Change the label and the trail follows.
- **Redirects (left menu):** when a page is removed, for example a lawyer who has left, add a redirect from
  its old address to the page visitors should land on. See Redirects below.

## Seeding content

A site's `SeedTask` builds its pages and blocks. Three rules, each learned the hard way:

- **Run in the Draft stage.** Call `Versioned::set_stage(Versioned::DRAFT)` first: elemental creates a
  page's block area only in Draft, so a seed running in Live writes every block to area 0.
- **Find by `SeedKey`, never by Title.** Set `SeedKey` when creating a block or bucket and look it up with
  `App\Models\SeedKey::findBlock($area, 'home-intro')` / `findBucket($bucketsBlock, 'card-one')`. Keys are
  lower-case letters, digits and hyphens, unique within their parent; a duplicated record, or a block an
  editor moves to another page, drops its key.
- **userforms owns Sort 1.** A form's first field is an `EditableFormStep`. Add fields from Sort 2, then call
  `$contactPage->createInitialFormStep(true)` (a CMS save does this; a write with no changes does not).

## reCAPTCHA

Keys live in `.env` (`SS_NOCAPTCHA_SITE_KEY`, `SS_NOCAPTCHA_SECRET_KEY`) and reach the field through
Injector properties in `app/_config/mysite.yml`. They must be a matched pair from one registration whose
domain list covers the apex, www and staging hosts. `SpamProtectionFieldValidator` enforces the captcha
server-side and fails closed: wrong keys refuse every submission. On staging, prove a solved captcha
passes and an unsolved one is refused before production, and after any key change open the contact page
and confirm `data-sitekey` shows the `.env` value. The validator logs one `spam-protection:` line per
submission to the PHP error log.

A refused submission comes back to the form with what the visitor typed and one message at the top: "Your
message has not been sent. Our spam check could not confirm it came from a person. Please try again, or
phone or email us instead." The wording is in `app/lang/en.yml` (French in `app/lang/fr.yml`), under the
module's three message keys; a site may reword it, but keep "not sent" and a way to reach the firm.

## Sitemaps

Every site must answer `/sitemap.xml` with **no `x-robots-tag` header** and no redirect on its child
sitemaps. Probe after any deploy (`D` is the site's host; staging is behind basic auth, so a 401 makes the
result void, not a failure):

```bash
D=icona-ss6-starter.ddev.site:33000; for u in /sitemap.xml "/sitemap.xml/sitemap/SilverStripe-CMS-Model-SiteTree/1"; do printf '%-58s ' "$u"; curl -s -o /dev/null -D - "http://$D$u" | grep -iE '^HTTP/|^x-robots' | tr -d '\r' | tr '\n' ' '; echo; done
```

Good looks like `HTTP/1.1 200 OK` on both lines and no `x-robots-tag`. In Search Console, the gate is
**Last read**: a date means Google fetched it, blank means it has not, whatever Status says.

## Redirects

`silverstripe/redirectedurls` adds **Redirects** to the CMS menu. Staff use it to forward an address the
site no longer has, most often a departed lawyer's profile, without a developer or a deploy.

- **It only fires on a 404.** A published page at the old address always wins, so unpublish or archive the
  page first, then add the redirect.
- **From base** is the old path (`/team/jane-smith`). Case and a trailing slash do not matter.
- **Redirect to: "A page on your website"**, then pick the page from the tree. That stores the page itself,
  so the redirect keeps working if the page is renamed later. A typed path does not: it goes stale when the
  target moves, and the visitor ends up on a chain of hops or a 404.
- The default code is **301** (permanent). Use 302 only for a short-term move.
- Renamed pages need no entry: SilverStripe already forwards a page's old URL segment to its new one.
- **Import CSV** loads a list of old-site paths at a cutover.

Delete the row to undo: the old address returns 404 again straight away.

## Gates

Before every PR and before the first staging deploy:

```bash
cd "/Users/dave/Documents/Projects/Bitbucket-Migration/cloned/new-site"
bash scripts/lineage-check.sh
bash scripts/predeploy-audit.sh
```

Both must PASS. `lineage-check.sh` skips `.svg` files, so check new SVGs for other clients' names by
hand, and keep every denylist token free of regex metacharacters: the gate builds one expression from the
file and escapes only the dot, so a stray bracket makes it report PASS having scanned nothing.
`predeploy-audit.sh` is the canonical PDSP audit; never edit the copy here. Its dependency gate audits
every `composer.json` and `package.json` in the tree, but the secret, malicious-pattern and diff-scope
gates only ever read the diff against the base branch. So give the audit one diff that contains
everything: make an empty first commit on `main` (`git commit --allow-empty -m "Initialise"`), commit the
imported snapshot on `rc/import`, and open the first PR from that. Without a base commit the audit exits 2,
which is not a pass.

## vendor/

Not tracked. Every environment runs `composer install` against `composer.lock` (PHP 8.4; the lock is
resolved for platform PHP 8.4.1). A site that must deploy by Plesk Git pull with no Composer step on the
server can decide, at deploy set-up and in its own repo, to commit `vendor/`.

## Changing the starter

Work on an `rc/*` branch, one commit per fix, and add the change to the Unreleased section of
CHANGELOG.md. Both gates must PASS, then Dave reviews and merges. Releases are annotated tags on `main`,
made after the merge. Nothing in this README names a version, so a release cannot leave it stale.
