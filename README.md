# truegritclean.ca

The website for TrueGrit Clean Co., a post-construction cleaning company in Vancouver. Built by ICONA on
SilverStripe 6 from ICONA's SS6 starter (`icona-ss6-starter` main `46f9df1`; see CHANGELOG.md for what that
release carries).

The project docs (build plan, page copy, the Figma export the theme is built from, task list) live in the
folder above this repo: `BUILD-HANDOFF.md`, `content/TrueGrit-Website-Content.md`, `design/figma-export/`,
`TRUEGRIT-NEXT-SESSION-TASKS.md`.

Stack: SilverStripe 6.2 (recipe-cms), PHP 8.4, MariaDB 10.11, elemental, userforms with reCAPTCHA v3, blog,
linkfield, googlesitemaps, SEO module, redirects (silverstripe/redirectedurls). Local: DDEV project
`truegritclean`. Nothing runs on the Mac itself: PHP, Composer and Node run inside DDEV.

## Site rules

- **No claim on the site until it is true.** Insurance, WorkSafeBC, HEPA, signed checklists and the like
  render only when their Settings checkbox is ticked.
- **Service-area business.** No street address anywhere, in the page or the schema.
- **Stock photos are context only.** Never captioned as TrueGrit jobs; no before/after section.
- **Copy lives in the CMS.** Site-wide sentences are Settings fields; templates carry only short UI labels.

## Theme

`themes/app-theme`, ported from the Figma Make export (`design/figma-export/styles.css`): its tokens are in
`css/src/_tokens.scss`, and its components keep the export's class names (`components/` and `pages/`
partials marked "(Figma)"). Fonts are self-hosted in `themes/app-theme/fonts/` (see `FONTS.md` there).
The logo is still the export's CSS-drawn placeholder (`templates/App/Includes/Logo.ss`) until the client's
SVG arrives.

## Run it locally

```bash
cd "/Users/dave/Documents/Projects/ICONA/Clients/True Grit Cleaning (Rebekah)/site.nosync"
ddev start
ddev composer install
ddev exec vendor/bin/sake db:build --flush
ddev exec vendor/bin/sake tasks:my-task
```

The site is at http://truegritclean.ddev.site, the CMS at `/admin`, and Mailpit (every email the site sends
locally) at http://truegritclean.ddev.site:8025. `tasks:my-task` registers the block variants (BlockStyle
rows) and creates the 404 and 500 error pages; it is safe to re-run. DDEV is registered at this real
`site.nosync` path, never through the `site` link beside it (DDEV's file sync cannot follow a link).

Theme build (after any change in `css/src` or `js/src`; commit `css/dist` and `js/dist`):

```bash
cd "/Users/dave/Documents/Projects/ICONA/Clients/True Grit Cleaning (Rebekah)/site.nosync"
ddev exec --dir /var/www/html/themes/app-theme npm ci
ddev exec --dir /var/www/html/themes/app-theme npm run build
```

## Gates

Before every PR: `bash scripts/lineage-check.sh` and `bash scripts/predeploy-audit.sh` must PASS, on an
`rc/*` branch. The Mac has no Composer, Node or gitleaks, so the audit's dependency gate needs `composer`
and `npm` shims on PATH that forward into DDEV (capture the subfolder before any `cd`, close stdin).
`lineage-check.sh` skips `.svg` files: check new SVGs for other clients' names by hand, and keep every
denylist token free of regex metacharacters (the gate escapes only the dot, so a stray bracket makes it
report PASS having scanned nothing).
`predeploy-audit.sh` is the canonical PDSP audit; never edit the copy here. Gate 1 fails only on high or
critical advisories, so read `ddev composer audit --locked` by hand as well.

## First deploy on a server

What is particular to this site (environment types, `.env` per server, PHP 8.4, upload limits, the seed order and
the error pages) is in [LAUNCH.md](LAUNCH.md). The repo is the code; three things exist only on the server, and a
deploy that skips them looks fine and serves a broken site.

```bash
cd "<project root on the server: settled when hosting is set up>"
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
- **Settings > Contact Info:** email, phone and service area (footer, CTA band, schema). Leave the street
  address and postal code empty: this is a service-area business. Empty lines are hidden. City and Province
  hold "Vancouver" and "BC" for the schema's address only (Google wants one on a local business); no template
  prints them.
- **Settings > Main > Tagline:** shown in the footer under the logo, and the `slogan` in the schema.
- **Settings > Main > Logo:** the logo search engines show for the business (the schema's `logo`); visitors see
  the theme's logo, not this field. Upload a PNG of the monogram, at least 112 x 112 px, once the client's logo
  is final. Empty leaves `logo` out of the schema.
- **Settings > Footer:** the exclusions line and the photo credits (keep them in step with the photos used: when a
  stock photo is replaced or removed, take its photographer off the line).
- **Settings > Claims:** six boxes, all unticked until the business confirms each one is true: insured,
  WorkSafeBC, HEPA equipment, signed checklist, job photos, minimum price. An unticked box keeps everything that
  depends on it off the site and out of the schema: checklist and FAQ items that tick it under "Show only when
  confirmed", sentences wrapped in `[claim key=minimum]...[/claim]` (`key=checklist+photos` waits for both),
  and the trust badges.
- **Missing content:** text the business hasn't sent yet is wrapped in `[missing note="what's needed"]stand-in
  text[/missing]` inside a paragraph. It shows on every environment as a black "Content needed" label with the note,
  then the stand-in on a yellow highlight, so a stand-in never passes as real copy. To finish one, replace the whole
  `[missing]...[/missing]` (tag included) with the real text and publish. A team card with no photo shows a
  "Photo needed" box on the local site and staging; on live it shows the text only. Before launch, LAUNCH.md counts
  what is left.
- **Settings > Contact Banner:** the yellow CTA band above the footer: heading and button label on every page;
  the text line and the words before the phone number on the home page only. The button links to the quote
  page (the site's ContactPage).
- **Contact page > Form Fields:** the form itself, including a Spam Protection field (its title is for
  editors; visitors never see it).
- **Menus:** on a top-level page, Settings > Visibility. "Show in Main Menu" puts it in the header menu,
  "Show in Footer Menu" in the footer's Company column, and the two are independent: a contact page reached
  by a header button can still be listed in the footer. "Show in menus?" must stay ticked for either, and
  pages below the top level appear in neither.
- **Any page > Hero > Hero image focus:** which side of the hero photo stays in view on a phone. Text must
  never cover a face: after changing a hero photo or this setting, look at the page on a phone.
- **Hero photos:** the home and About heroes show the page's own Hero image. A service page shows a photo only when
  one is set on that page itself (never its parent's): the hoarding page carries no photo by design, and a page
  without one gets the plain charcoal hero. Feature photos and the "Who we work with" band use the block's
  Background Image; the three phase cards use each item's Image. The image's Title in Files is its alt text.
- **Breadcrumbs** use each page's navigation label (Main tab). Change the label and the trail follows.
- **Redirects (left menu):** when a page is removed, for example a service that is dropped, add a redirect from
  its old address to the page visitors should land on. See Redirects below.

## Seeding content

`App\Tasks\SeedTask` builds the 9 launch pages, their blocks and the Settings text from
`app/seed/content.yml` (the copy of `content/TrueGrit-Website-Content.md`, verbatim minus the notes for the client):

```bash
cd "/Users/dave/Documents/Projects/ICONA/Clients/True Grit Cleaning (Rebekah)/site.nosync"
ddev exec vendor/bin/sake tasks:my-task
ddev exec vendor/bin/sake tasks:seed --publish
ddev exec vendor/bin/sake tasks:seed-images --publish
ddev exec vendor/bin/sake tasks:seed-form --publish
```

It checks the whole file before writing anything, skips every page that already exists and every Settings field
that already holds text, and never touches Settings > Claims, so a re-run cannot overwrite the client's edits.

`App\Tasks\SeedImagesTask` (`tasks:seed-images`) then attaches the launch photos listed in `app/seed/images.yml`
(web copies in `app/seed/images/`, Unsplash construction scenes, never TrueGrit jobs) to the page heroes, blocks
and items, with each photo's alt text stored as the image's Title (SilverStripe prints it as `alt`), and writes the
footer's photo credits line. It fills only empty image fields and leaves an edited credits line alone; `--force`
replaces both. A photo already in Files is reused, so a re-run uploads nothing twice.
`--force` overwrites existing pages and Settings text: never on a site whose content the client has started
editing. Without `--publish` it writes drafts.

`App\Tasks\SeedFormTask` (`tasks:seed-form`) builds the quote form on `/quote/` from `app/seed/form.yml`: its four
numbered sections and their fields (userforms Field Groups), the photo field, the spam check, the email recipient, the
button text and the text under the button. It adds fields only to a form that has none, a recipient only when there is
none, and a page setting only while it is empty; once the form exists it belongs to the editor. It has no `--force`
and deletes nothing.

A site's `SeedTask` builds its pages and blocks. Three rules, each learned the hard way:

- **Run in the Draft stage.** Call `Versioned::set_stage(Versioned::DRAFT)` first: elemental creates a
  page's block area only in Draft, so a seed running in Live writes every block to area 0.
- **Find by `SeedKey`, never by Title.** Set `SeedKey` when creating a block or bucket and look it up with
  `App\Models\SeedKey::findBlock($area, 'home-intro')` / `findBucket($bucketsBlock, 'card-one')`. Keys are
  lower-case letters, digits and hyphens, unique within their parent; a duplicated record, or a block an
  editor moves to another page, drops its key.
- **userforms owns Sort 1.** A form's first field is an `EditableFormStep`. Add fields from Sort 2, then call
  `$contactPage->createInitialFormStep(true)` (a CMS save does this; a write with no changes does not).

## Quote form

`/quote/` is the starter's `ContactPage` (userforms), in the markup of the Figma export's `quote.html`: field holders,
numbered sections, pill radios and the submit area are theme templates under
`themes/app-theme/templates/SilverStripe/UserForms/`, with `App\Extensions\UserFormThemeExtension` for what a
template cannot set (input types, the submit button). userforms' own jQuery and stylesheet are switched off on the
page (`app/_config/userforms.yml`): the browser's own checks run first, as in the export, and the server checks
everything again. A block in the Reassurance panel style sits beside the form; every other block goes under it.

**Photos.** userforms' file field takes one file, so the form uses the site's own field type, "Photo Upload Field
(several photos)" (`App\Models\EditablePhotosField`, front end `App\Forms\PhotosUploadField`, script
`js/src/photos.js`). Up to 5 photos of 10 MB each (both set on the field; 5 is also the most the code allows,
`EditablePhotosField::MAX_FILES_LIMIT`), JPG or PNG, checked in the browser and on the server (count, size, extension,
content matches extension). Someone who chooses more is told they can send the rest by email once the firm replies.
HEIC is not accepted: the image libraries cannot read it, and an iPhone's Safari converts its photos to JPEG when the
page does not ask for HEIC.

**Nothing is stored (since Nov 27, 2026).** The email is the delivery. The quote page has "Disable Saving Submissions
to Server" ticked (Configuration tab; `fixed_settings` in `app/seed/form.yml`, which `tasks:seed-form` sets on every
run), so no submission row is written. The photos never reach Files or `assets`: `App\Forms\EmailPhotos` resizes each
one in memory to a JPEG (longest side 2,000 px, quality 80, EXIF and GPS data dropped, turned upright), and
`App\Extensions\QuotePhotosEmailExtension` attaches them to each recipient's email. All attachments together stay
under 15 MB, so the message stays under Mailgun's 25 MB limit: if they would not fit, every photo is shrunk again
(1,600 px at 70, then 1,280 px at 60), and any photo that still does not fit is named in the email with the reason,
never dropped silently. PHP deletes the uploads when the request ends. Measured locally: 5 phone-size photos (12 MP)
gave a 2.1 MB email.

**What an upload can cost the server** (audit, Nov 27, 2026; test files and scripts in
`.seed-originals/photo-test/audit/`, not in git). Disguised files are refused and a real photo carrying hidden code
comes out clean, because only the re-made JPEG is ever sent. The cost that matters is memory: opening a photo takes
memory by its pixel count, not its file size, and with Imagick that memory is outside PHP's `memory_limit` (a 250 KB
JPEG declaring 8,000 x 8,000 pixels took about 1 GB). So a photo over 51 megapixels is never opened (config
`EmailPhotos.max_pixels`; the email names it as not attached), and only one request resizes photos at a time for the
whole site (a file lock in the temp folder). A request that waits more than 20 seconds for its turn sends its email
without photos and says so, and logs an error.

**Limits and failed sends** (`App\Pages\ContactPageController`). One visitor address may send 3 requests in 10 minutes
(counted once a request passes the form's checks and the reCAPTCHA check; config `throttle_max`, `throttle_window`).
The 4th is sent back to the form with a message to wait or phone. The whole site accepts 20 requests an hour whatever
the address (`site_max`, `site_window`), so the inbox cannot be filled with photo emails; the 21st gets the same kind
of message and an error is logged, because that many is either abuse or a limit to raise. If the email cannot be sent, or no recipient gets
one, the visitor sees the form again with their text kept and a message to try again or call the Settings phone number,
instead of the thank-you; the failure is logged to `silverstripe.log` without the visitor's details. The wording is in
`app/lang/en.yml`. The limit reads the visitor's address from PHP: behind a proxy or CDN (Cloudflare's orange cloud)
that address must be passed on (`SS_TRUSTED_PROXY_IPS`), or every visitor shares one count.

**Server limits.** PHP must accept the photos: `upload_max_filesize` at least 10M, `post_max_size` above the 5
photos together (60M or more), `max_file_uploads` 20 (PHP's default), and a web server request limit to match.
Plesk's defaults (2M / 8M) refuse almost any phone photo. The CMS field shows the server's limits, and the browser
refuses a selection larger than `post_max_size` before sending it (a post over that limit arrives empty). Resizing
raises the time limit to 180 seconds for that request, so `max_execution_time` may not be locked with a Plesk
`php_admin_value`. It does not raise `memory_limit` (see the cost paragraph above).

**Mail.** The recipient's From is `website@mg.truegritclean.ca` (userforms requires one; ICONA's Mailgun pattern), and
the server's `SS_SEND_ALL_EMAILS_FROM` replaces it. Reply-To is the visitor's email. userforms logs every mail error to
`silverstripe.log` (`error-log.ERROR`), and the site adds a line starting "Quote form: a quote request was NOT
delivered" when a visitor was sent back: read it when a quote request seems missing.

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
module's three message keys; a site may reword it, but keep "not sent" and a way to reach the company.

## Schema and llms.txt

`App\Extensions\SEOMetaExtension` builds each page's JSON-LD from the CMS (config in `app/_config/seo.yml`):

- **Every page:** a `ProfessionalService` node (name, URL, phone in `+1-` form, email, slogan, logo, `areaServed`,
  and an `OfferCatalog` naming every published service page), `WebSite`, `WebPage` and `BreadcrumbList`.
- **No street address, ever.** `service_area_business: true` keeps the street address and postal code out of the
  schema even if someone types them into Settings > Contact Info; no template prints them either. The address
  carries only Settings > City and Province (Vancouver, BC) and the country: Google requires an `address` on every
  LocalBusiness type, `ProfessionalService` included.
- **Service pages:** a `Service` node with the same `@id` as its entry in the catalogue.
- **Any page with an FAQ block:** `FAQPage`, from the FAQ items actually shown. An FAQ held back by
  Settings > Claims, or a `[claim]` sentence inside an answer, stays out of the schema too.

`/llms.txt` (`App\Control\LlmsTxtController`, route in `app/_config/routes.yml`) is a plain-text summary for AI
assistants: the home page's meta description, tagline, service area, phone, email, every service page and
other top-level page with its meta description, and the footer's exclusions line. It is built from the CMS on
every request and carries no copy of its own, so editing a meta description or a Settings field updates it.

## Sitemaps

Every site must answer `/sitemap.xml` with **no `x-robots-tag` header** and no redirect on its child
sitemaps. Probe after any deploy (`D` is the site's host; staging is behind basic auth, so a 401 makes the
result void, not a failure):

```bash
D=truegritclean.ddev.site; for u in /sitemap.xml "/sitemap.xml/sitemap/SilverStripe-CMS-Model-SiteTree/1"; do printf '%-58s ' "$u"; curl -s -o /dev/null -D - "http://$D$u" | grep -iE '^HTTP/|^x-robots' | tr -d '\r' | tr '\n' ' '; echo; done
```

Good looks like `HTTP/1.1 200 OK` on both lines and no `x-robots-tag`. In Search Console, the gate is
**Last read**: a date means Google fetched it, blank means it has not, whatever Status says.

## Redirects

`silverstripe/redirectedurls` adds **Redirects** to the CMS menu. Staff use it to forward an address the
site no longer has, most often a removed service page, without a developer or a deploy.

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

## vendor/

Not tracked. Every environment runs `composer install` against `composer.lock` (PHP 8.4; the lock is
resolved for platform PHP 8.4.1). A site that must deploy by Plesk Git pull with no Composer step on the
server can decide, at deploy set-up and in its own repo, to commit `vendor/`.
