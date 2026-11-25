# Changelog

All changes to the ICONA SilverStripe 6 starter. Each entry says what changed and why, so a site built
from an older version can tell which fixes it lacks. Internal provenance (which builds each fix came
from) is in `docs/starter/PROVENANCE.md`, which is not exported with a release.

## Unreleased

Patch releases that clear the composer advisories failing the PDSP audit on `main`.

### Security
- **Composer packages move to their newest patch release (`composer update --patch-only`).** Against the
  v0.3.0 lock, `composer audit` reported 27 advisories across 10 packages, all published after v0.3.0 was
  audited on 2026-10-15. The PDSP audit fails on the high one, CVE-2026-91115 (code execution through the
  `i18nTextCollectorTask` writer parameter). The rest include SS-2026-003 (code execution through a renamed
  upload), SS-2026-004 (account takeover through the password reset token), SS-2026-001 and SS-2026-006
  (template injection through the userforms email subject), XSS and access-control fixes in framework and
  userforms, and Symfony fixes in http-foundation, http-client, yaml, config, filesystem, validator and mime.
  The releases that close them: `silverstripe/framework` 6.2.7 -> 6.2.14, `silverstripe/assets` 3.2.2 ->
  3.2.4, `silverstripe/userforms` 7.1.3 -> 7.1.4, and the Symfony components to 7.4.22/7.4.23 and
  8.1.10/8.1.11. 33 packages move, each within the minor version already locked. Nothing is added or
  removed, `composer.json` is unchanged, and `composer audit` now reports no advisories. A site built from
  v0.3.0 can take the same update with `composer update --patch-only`, then `db:build --flush`.
- **`public/assets/.htaccess` as written by assets 3.2.4.** It now refuses any request for a PHP script under
  `assets/` (`.php`, `.phar`, `.phtml`, `.pht`, `.inc`, in any case), because the existing `AddHandler` line
  does nothing under PHP-FPM. Its error-page rule now matches only file names that start with `error`. This
  is the server-side half of SS-2026-003. A flush writes the same file on any site that takes the update; a
  site that commits the file should commit the new copy, so a deploy never puts the old rules back between
  the pull and the flush.

### Fixed
- **The `.section` padding utility could make the header about 200px taller on child pages.** SilverStripe
  writes `section` as a menu link's state class (`$LinkingMode`) when one of that page's child pages is open,
  and the bare `.section` rule gave such a link 100px of padding top and bottom. The starter's own header,
  mobile menu and rail nav set their own padding on those links, which hides it, but a build that restyles a
  menu link without its own `padding-block` inherits the fault. The rule now applies only to block wrappers
  (`section.section, div.section`). No starter template uses the class, so nothing here renders differently;
  a site built from an earlier release can take the `_layout.scss` change and rebuild `css/dist`.

## v0.3.0 (2026-10-15)

Redirects in the CMS become standard on every new SS6 build, and the theme's build tools clear the npm
advisory that failed the PDSP audit.

### Added
- **Redirects in the CMS (`silverstripe/redirectedurls` ^4.0, with its dependency `unclecheese/display-logic`).**
  A page that is removed, such as a profile for a lawyer who has left, used to return 404 with no way for
  staff to forward it. Its old address can now be pointed at another page from the Redirects menu, live as
  soon as it is saved. Redirects fire only for an address that would otherwise 404, so a published page is
  never overridden. `db:build` adds one table (`RedirectedURL`) and widens four class-name columns by one
  value each; nothing is removed. How to use it: README, Redirects.

### Security
- **Theme build tools: `source-map-js` 1.2.1 -> 1.2.2** (GHSA-68fv-2mgg-jv7q, high: a crafted source map
  with indexed section offsets could stall the process). It sits in the theme's Sass build chain and runs
  only on the developer's machine during `npm run build`; nothing from it ships to visitors. The PDSP audit
  failed on it for every branch. Only `themes/app-theme/package-lock.json` changes, and a clean rebuild
  produces byte-identical `css/dist` and `js/dist`, so a site built from an earlier release can take the same
  lock change without rebuilding its assets.

## v0.2.2 (2026-09-24)

Four items the ICONA build-job template's test builds turned up in v0.2.1.

### Fixed
- **The footer's "Firm" column repeated the header menu.** It listed the pages ticked "Show in Main Menu",
  so "Show in Footer Menu" did nothing and a page kept out of the main menu could never appear in the
  footer. The usual casualty was the contact page, which designs link from a header button rather than a
  menu item. The column now lists the top-level pages ticked "Show in Footer Menu", independently of the
  header, and like the header menu it respects "Show in menus?" and leaves out pages the visitor cannot
  view (`PageController::FooterMenu()`, `Footer.ss`). The two checkboxes in Settings > Visibility now say
  they apply to top-level pages only.
- **A refused contact-form submission could land on the home page.** When a form refuses a post (the spam
  check, or a required field the browser did not catch), SilverStripe sends the visitor back to the page in
  the browser's Referer header. Browsers send one by default, but privacy settings and extensions, a
  no-referrer policy and scripts do not, and then the visitor arrived at the home page with no form and no
  message while what they had typed waited in the session. A refused post now always returns to the form's
  own page, scrolled to the form, with the visitor's text in place (`App\Extensions\UserFormRefusalExtension`,
  registered in `app/_config/extensions.yml`). What gets refused has not changed.
- **The refusal message was written for a different captcha.** It said "Please answer the captcha, if you do
  not see the captcha please enable JavaScript", and a real person with a low reCAPTCHA v3 score was told
  "Your submission has been marked as spam". v3 is invisible: there is nothing to answer. All three of the
  module's messages now read "Your message has not been sent. Our spam check could not confirm it came from
  a person. Please try again, or phone or email us instead." (`app/lang/en.yml`; the French text is in
  `app/lang/fr.yml`). It shows in a box at the top of the form, the first thing the visitor sees, instead of
  below the last field; any other field's message stays on that field.
- **"Spam protection" showed as a label over nothing.** The spam field's title rendered as a visible label
  above the invisible check, pointing at no element. The theme hides it (`_forms.scss`); the title stays in
  the CMS so editors can find the field.
- **The hero scrim darkened heroes with no photo.** v0.2.1 put it on every default hero, so a design's plain
  dark background came out darker than specified. It now appears only over a photo, including a photo a
  page shows from its parent (`Hero.ss`).

## v0.2.1 (2026-09-24)

### Fixed
- **Breadcrumbs were never shown.** v0.1.0 moved the trail template to where SilverStripe looks for it, but
  no layout called `$Breadcrumbs`, and three layouts (practice area, person, blog post) hand-rolled a
  two-level trail that dropped any deeper ancestor and always said "Home". Every page except the home page
  now shows the real trail: the home page first, under its navigation label (a French site shows
  "Accueil"), then every ancestor, then the current page as text marked `aria-current` rather than a link
  to itself. The visible trail and the page's `BreadcrumbList` schema come from the same records, so they
  match.
  - Layouts with a place for it in the content column (practice area, person, blog post) render it there.
    The others show it as a strip under the hero (`App/Includes/BreadcrumbBar.ss`).
  - Keep `id="breadcrumbs"`: the ICONA build-job template's render check looks for it on every page but
    the home page.

### Added
- **Hero image focus (Hero tab).** Narrow screens crop the sides of the hero photo, and every hero cropped
  to the centre, both in the CSS and in the smaller images served below 992px. A face near one edge was
  cut away or slid under the headline, and each site solved it by hand. `HeroFocusX` (left, centre,
  right) now decides which side stays in view at every width; centre is the old behaviour, so nothing
  changes until an editor sets it. A page without its own hero image uses its parent's image and setting.
  The hero templates call `$HeroImageFill(w, h)` instead of `$HeroImageOrDefault.Fill(w, h)`, and
  `Image::AnchorFill()` does the crop.
- **A scrim on the default hero.** A near-black layer at 58% opacity between the photo and the text keeps
  the white headline at 4.5:1 or better over any photo. The grey intro and the gold label can still fall
  short over a very bright photo, so check one on staging. The home and person heroes keep their text off
  the photo and have no scrim.

## v0.2.0 (2026-09-22)

Everything here came from an eight-dimension audit of v0.1.0 against the ICONA standards, with each
finding checked by a second pass told to refute it. The audit and the full backlog are in
`docs/starter/AUDIT-2026-09-22.md`.

### Fixed
- **Three other clients' names shipped in the starter, and the lineage gate could not see any of them.**
  The denylist holds each client's compressed spelling, while the source carried the human one: a firm
  name with a space in a stylesheet header, an abbreviation with a slash in a comment, and a partner's
  personal name in a code comment. All three strings are neutral now, the spaced and punctuated forms
  are on the denylist, and so are seven identifiers it lacked. One token was narrowed to a full domain,
  so it no longer flags a firm writing about the law in its own city. The denylist also warns that a
  token must carry no regex metacharacter: the gate escapes only the dot, so a stray bracket makes it
  report PASS having scanned nothing.

  (This entry deliberately names none of them: the CHANGELOG is a shippable file, and the gate flagged
  an earlier draft of this very paragraph.)
- **Body text on white was the on-dark colour**, about 2:1 where WCAG asks 4.5:1 — including the contact
  form's own inputs, so a visitor could barely read what they were typing. Four rules corrected; the
  token comments now say which background each colour belongs on.
- **A block left on "Default Style" rendered an empty shell.** All four base block templates printed
  field names that do not exist on those classes (`$Content`, `$Summary`, `$TeaserContent`, `$Name`).
  They now render the block's real fields, so nothing an editor wrote disappears.
- **The person grid hard-coded a link to `/team/` and the words "Meet Our Team"** on the home page of
  every site, to a page the starter does not create. It renders the block's own CTA link instead.
- **The Dark CTA variant loaded an image that is not in the repo** (a 404 on every page using it).
- **Two debug strings shipped:** "Default ElementContent.ss" rendered on the page, and a template
  filename appeared as a heading inside the CMS.
- **Blog posts printed their title twice**, and the prominent one was a paragraph, not a heading. The
  external-link field rendered `href=""` because the template used a different module's API.
- **The contact banner reached every layout except the base page type**, via a commented-out include.
- **`tasks:my-task` reported missing error pages and still exited 0.** It now exits non-zero, and it is
  CLI-only (`can_run_in_browser = false`), so no CMS user can run a writing task from a live site.
- **`--ls-h4` was used twice and never declared.** Declared, and the letter-spacing comments now state
  the fleet rule: Figma percentages convert as value/100 em, never as px.

### Changed
- **reCAPTCHA `minimum_score` 0.7 to 0.4.** The module compares with `<=`, so the configured number is the
  highest score it REFUSES: 0.7 refused everything up to and including 0.7, and 0.4 refuses 0.4 and below,
  which accepts Google's own starting point of 0.5. The captcha fix is fail-closed, so too strict turns
  real enquiries away with nothing in any log; the validator now records Google's score on every
  submission, so the threshold can be raised on evidence.
- **SVG upload is no longer re-enabled in the CMS.** SilverStripe blocks it by default because an SVG is
  a script-capable document served inline from the site's own origin; theme SVGs are committed, not
  uploaded.
- **A form with no recipient now warns in the CMS** (`recipients_warning_enabled`), and `.env.example`
  documents `SS_SEND_ALL_EMAILS_TO` / `_FROM` so a staging copy cannot email the client, with the
  SPF-aligned sender note. Local mail goes to DDEV's Mailpit instead of being discarded.
- **`symbiote/silverstripe-gridfieldextensions` is declared.** It was used directly in `app/src` while
  arriving only as someone else's dependency. No version changed.
- README gained a first-deploy section for servers (the cache directory, `_resources`, `db:build`,
  `tasks:my-task`, the browser flush, then probes), the recipient step, the sitemap probe, the
  letter-spacing and editor-palette rules, and the reason to import on an `rc/import` branch.

### Fixed after an independent review of this branch
An adversarial review of the branch found defects worth fixing, two of them in the branch's own work.

- **SVG upload was still enabled.** Removing the block from `mysite.yml` changed nothing: a second,
  unguarded document in `app/_config/mimevalidator.yml` merged the same key back, and this changelog
  asserted the opposite. That document is gone, the four CMS upload fields that still offered `svg` now
  offer `jpg, jpeg, png, webp`, and `public/assets/.htaccess` regenerated without `svg` in its servable
  whitelist. Proven in the running app: `svg` is absent from `File::getAllowedExtensions()` and a file
  named `logo.svg` fails validation with "Extension 'svg' is not allowed", while `photo.jpg` still passes.
- **The README told every new site to snapshot `v0.1.0`**, so a site built by following it would have had
  none of the v0.1.1 or v0.2.0 fixes. The command now resolves the newest tag and prints it, and no
  version number appears anywhere in the README, so a release cannot leave it stale again.
- **The no-recipient CMS warning was configured on the wrong class** and never rendered: the flag is
  declared by the `SilverStripe\UserForms\UserForm` trait that `UserDefinedForm` uses, not by the
  front-end `Form` class it was set on. Confirmed in force in the running app.
- **The skip link skipped nothing** — it targeted the wrapper that contains the header. `#main-content`
  now sits on each layout's `<main>`.
- **`MyTask` declared a global-namespace class under the `App\` PSR-4 path**, so any code referring to
  `App\Tasks\MyTask` fatally redeclared it. Namespaced; `tasks:my-task` is unchanged.
- **The blog post's link field was named after the column, not the relation** (`ExternalLinkID`), so
  linkfield could not attach the link it created.
- **A person block with only a CTA dropped its button**, because the new CTA sat inside the intro guard.
- **The footer loaded the white logo onto its white background.** It loads the dark one now, and the README
  says which file appears on which surface.
- **Dark CTA styles still reserved the right half of the band** for the mark whose markup was removed;
  `.dark-cta__mark` and `.consultation-banner__logo` were dead rules.
- **The base block template printed `$SubTitle`**, a field the CMS strips and never re-adds.
- **Two templates used `btn--red`**, a modifier no stylesheet defines.
- **One denylist token had been narrowed to a full domain on a false premise.** The gate matches literal
  text, so the broader city-and-practice token never flagged that phrase written as ordinary prose;
  narrowing it only weakened the gate.
  Restored. The denylist now records what the gate cannot do: it matches only the spellings listed, so a
  multi-word firm needs its spaced form and its partners' names, and ICONA's own names are deliberately
  absent so nobody learns to ignore a flag.
- **Documentation corrections:** the audit's dependency gate reads the whole tree while its content gates
  read only the diff; an imported tree needs a base commit or the audit exits 2, which is not a pass; the
  page-type list in CLAUDE.md was missing `CustomBlog` and `CustomBlogPost`; a stylesheet comment still
  named a deleted page type.

### Removed
- **`CaseStudiesPage`, `FeesPage`, the `Review` model and the Resources CMS section**, with their
  templates, styles and the `SpecialListBlock` Experience variant: all one-offs from the build this was
  stripped from, none in the fleet pattern, and the Review model was a CMS section nothing rendered.
- **Four `PersonPage` fields no template read** (`PersonType`, `PersonLocation`, `CredentialsContent`,
  `AssociationsContent`) and the Reviews relation.
- **Twenty theme SVGs nothing referenced**, and `phpcs.xml.dist`, a config file for a tool the starter
  does not install.

### Added
- `public/favicon.ico`, so the browser's automatic request stops rendering a full 404 page.
- A skip link, using the `.sr-only` utility that was already in the stylesheet and unused.

## v0.1.1 (2026-09-22)

### Fixed
- **Sitemaps told search engines not to index them.** `wilr/silverstripe-googlesitemaps` hardcodes
  `X-Robots-Tag: noindex` on the sitemap index, every child sitemap and the gzipped index, in every
  version including the 4.1.0 this starter locks, with no config switch (a fleet sitemap sat unread in
  Search Console for 20 days because of it). `App\Middleware\SitemapRobotsHeaderMiddleware` removes the
  header from any `sitemap.xml*` response; nginx cannot do it on Plesk and a vendor patch dies at the next
  composer update.
- **Every child sitemap answered a 301.** The sitemap index lists children as
  `sitemap.xml/sitemap/<class>/<page>`, which the site's trailing-slash rule redirected. The sitemap
  routes are now exempt from that rule (the framework already exempted `/sitemap.xml` itself, because it
  has a file extension), so crawlers reach the XML in one hop. Pages still redirect to their trailing
  slash as before.

## v0.1.0 (2026-09-22)

First release. Seeded from the newest fleet reference build (commit 3868e8db), stripped of its identity
for the laptop-worker rehearsal (Step 3), then corrected for everything that rehearsal and its checks
found.

### Fixed
- **Footer 500 on every page.** The copyright line read `$SiteConfig.Title.`; the full stop made the parser
  render the SiteConfig object. Now `{$SiteConfig.Title}.`.
- **Contact form never rendered.** `ContactPage` extends `UserDefinedForm`, but its layout had the form
  section commented out. The form, the page content and the contact details render again.
- **Mobile menu included twice.** Two off-canvas menus with one id on every page. Now included once, by
  the header.
- **Breadcrumb template never used.** Moved from `templates/App/` to `templates/BreadcrumbsTemplate.ss`,
  where `SiteTree::Breadcrumbs()` looks.
- **Contact banner copy hard-coded.** Heading, text, button label and disclaimer are now SiteConfig fields
  (Settings > Contact Banner); an empty field hides its line. Phone and address lines are hidden when empty.
- **Remote placeholder image.** A Checkerboard bucket without an image loaded one from placehold.co. It
  now renders without an image column.
- **Text Cards had no heading.** The block title renders as its h2, like the other bucket variants.
- **No 404 or 500 page.** Turning off `create_default_pages` also stopped the error pages being created, so
  a missing URL got a bare one-line response. `tasks:my-task` now creates both.
- **reCAPTCHA keys could not load from .env.** They were set in plain config, which does not expand
  backticks. They now go through Injector properties.
- **Source maps.** `npm run css` no longer writes `.map` files and the compiled CSS no longer points at
  one; `*.map` is ignored.
- **Fonts named but never loaded.** The tokens and editor styles named two web fonts the site does not load.
  Both now use the system stacks visitors already saw.

### Added
- **`SeedKey`** on every block and bucket (`SeedKeyExtension`, `Varchar(120)`, indexed, never shown in the CMS;
  the fleet convention the build-job template expects), with `App\Models\SeedKey::findBlock()` / `findBucket()`. Seeds
  find their records by key, never by Title; keys are validated and unique within their parent, and a
  duplicated record, or a block moved to another page, drops its key.
- **Server-side captcha enforcement** (`SpamProtectionFieldValidator` + `UserForm_Extension`), fail-closed,
  verifying each token at most once.
- **PHP 8.4 by decision.** `require.php` is `^8.4` and `config.platform.php` is `8.4.1`; the lock was
  re-resolved on 2026-09-22 under that platform (37 patch or minor updates, no package added or removed,
  no advisories).
- **Gates.** Canonical PDSP audit v1.2 and the managed CLAUDE.md policy block; the lineage gate with a
  denylist naming every ICONA client and the rehearsal firm.
- **Local environment.** `.ddev/config.yaml` (PHP 8.4, MariaDB 10.11) and an `.env.example` that works with
  it as is.
- **Docs.** README (starting a site, first run, seeding rules, reCAPTCHA, gates, vendor policy),
  CLAUDE.md, this changelog.

### Changed
- Identity: neutral placeholders for logos, favicon, web manifest, SEO config and package names. robots.txt
  ships no Sitemap line until the site adds its own.
- `vendor/` is not tracked (see README).
- `wernerkrauss/silverstripe-rector` moved to `require-dev`, so it, rector and phpstan no longer install on
  production (`composer install --no-dev`). No version changed.

### Known, not yet addressed
- The header has no Contact pill (the fleet pattern puts Contact in a pill, not the text menu).
- Sass reports `@import` deprecations on every build; Dart Sass 3 will remove `@import`.
