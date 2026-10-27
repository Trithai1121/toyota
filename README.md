# Toyota Kon Tum dealership website

New Laravel application for a Toyota dealership serving Kon Tum, Gia Lai, and
Quang Ngai. It does not reuse source code, themes, plugins, data, or images from
the previous CMS. Vehicle prices, specifications, promotions, dealer identity,
and media remain unpublished until supplied and approved by the dealership.

For step-by-step Windows/XAMPP setup and production deployment, see
[HUONG-DAN-XAMPP-VA-DEPLOY.md](./HUONG-DAN-XAMPP-VA-DEPLOY.md).

## Technology

- PHP 8.3+, Laravel 13, MySQL 8.0+ or a currently supported MariaDB release
- Blade, Tailwind CSS 4, Alpine.js, Vite
- Filament 5 for administration; Spatie Laravel Permission for roles
- Database-backed queue, cache, and sessions for ordinary shared hosting

Dependencies are managed by Composer and NPM. Check their licenses before
adding packages; use only licenses compatible with commercial use.

## Local setup

Required PHP extensions: BCMath, Ctype, cURL, DOM, Fileinfo, Filter, Hash,
Mbstring, OpenSSL, PCRE, PDO with `pdo_mysql`, Session, Tokenizer, XML, ZIP,
and GD or Imagick for image processing. Also install Composer 2, Node.js 20+
and NPM, and MySQL/MariaDB.

WebP uploads require PHP GD with `imagewebp()` enabled. The admin upload
workflow converts JPEG/PNG/WebP files to WebP and creates responsive widths;
do not disable GD WebP support on the production PHP runtime.

1. Create a MySQL database and a least-privilege database account.
2. Copy `.env.example` to `.env`; set the database, mail, dealership, and
   `APP_URL` values. Keep credentials and API tokens only in `.env` or the
   hosting provider's secret store.
3. Set `INITIAL_ADMIN_NAME`, `INITIAL_ADMIN_EMAIL`, and a unique
   `INITIAL_ADMIN_PASSWORD` of at least 12 characters before running the
   seeder. Do not commit `.env`.
4. Run:

   ```sh
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   npm ci
   npm run build
   php artisan migrate --seed --force
   php artisan storage:link
   ```

5. Point the web server document root to `public/`, enable HTTPS, and ensure
   `storage/` and `bootstrap/cache/` are writable by the PHP process.

To run locally, use `composer install` (including dev dependencies), `npm
install`, and `php artisan serve`. The admin panel is at `/admin`.

## Hosting operations

Use a cron entry to run Laravel's scheduler every minute:

```cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

Run a supervised queue worker for queued mail and other notifications:

```sh
php artisan queue:work --tries=3 --timeout=90
```

For cPanel, confirm PHP 8.3+, required extensions, Composer, cron, and queue
worker support with the hosting provider. A VPS with a supervised worker is
recommended for reliable notifications. Configure regular encrypted database
and uploaded-media backups, log rotation, and a tested restore procedure.

## Initial data and access

The seeder creates the `admin`, `marketing`, and `sale` roles and unpublished
draft vehicle lines for the three example names supplied in the project
request. It does not create vehicle records, prices, specifications, promotions,
dealer contact details, or fabricated testimonials. Set the initial admin
credentials through `.env` before seeding; no default account or password is
shipped.

Role-based panel access and record policies are enforced in the application.
The admin panel requires authenticator-app two-factor authentication for all
staff accounts. Recovery codes are stored encrypted/hashed by Filament.

## Admin workspace

- The dashboard summarizes lead counts by status. Administrators see all leads;
  sales staff see only leads assigned to their account; marketing staff do not
  see lead counts.
- **Kinh doanh** contains the lead list and care notes. Lead details include
  the requested vehicle/date, customer note, assignment, campaign, and available
  UTM source fields. Only administrators can assign staff or export leads.
- **Danh mục xe** manages vehicle lines, models, variants, specifications, and
  approved vehicle images. **Nội dung** manages promotions, posts, pages, and
  campaign landing pages.
- **Quản trị** contains staff accounts and business calculation settings.
  Only administrators can manage staff and settings. Marketing can manage
  catalog and publication content; sales staff can update the status and notes
  of leads assigned to them.

## Page builder and editable site content

Marketing and administrators can edit pages under **Admin → Nội dung → Trình
dựng trang**, and edit the global appearance, dealer contact information, and
interface strings under **Admin → Giao diện**. Sales users cannot edit this
content. Public preview requires an authenticated content editor; the preview
toolbar switches between desktop, tablet, and mobile widths. When an editor is
signed in, each rendered page block also links to that page's editor.

The page builder stores blocks as data and renders them with a dedicated Blade
component. Supported blocks include hero, vehicle grid, promotions, text/image,
specifications, gallery, lead form, calculators, FAQ, recent posts, showroom
map, CTA, and explicitly confirmed real customer reviews. Rich text is
sanitized against an HTML tag and link-scheme allowlist before it is saved.
Uploads require alt text where applicable and are converted into WebP plus
responsive variants. Do not upload Toyota marks or vehicle photos without
confirmed usage rights.

Page status can be draft, published, or scheduled. The scheduler runs
`pages:publish-scheduled`; configure Laravel's scheduler as described above.
While editing an existing page, the builder debounces live field updates and
stores them in a private `page_drafts` record. These autosaved values do not
change a published page until the editor explicitly saves. The last saved
revision is removed from the autosave slot after the save completes.
Each edit records a revision. **Admin → Nội dung → Lịch sử phiên bản** lets
content editors inspect a saved JSON snapshot, compare it with current content,
and restore it. Page and block presets are reusable from the builder.

The theme and dealer settings are JSON-backed records so the editor can manage
menu trees, theme colors, logo paths, footer columns, contact actions, and
dealer details without editing Blade. Interface labels and thank-you text are
managed as key/value records in **Giao diện → Chuỗi giao diện**. Keep
credentials and server-only integration secrets in the deployment secret
store; they are not page content and must not be copied into theme JSON.

### Content ERD

```text
pages 1 ─── * page_blocks
pages 1 ─── * page_revisions * ─── 0..1 users
pages 1 ─── 0..1 page_drafts * ─── 0..1 users
tracking_setting_audits * ─── 0..1 users
block_presets (standalone; stores one block or a page's block collection)
theme_settings (unique key → JSON value)
ui_strings (unique key → editable public-facing string)
page_blocks * ─── 0..1 vehicle_lines
page_blocks * ─── 0..1 vehicles
vehicles * ─── 0..1 pages (content_page_id)
campaign_landing_pages * ─── 0..1 pages (content_page_id)
```

The builder records page revisions and restores page and block state
transactionally. Before publishing or scheduling, the builder requires SEO
metadata, checks internal route/database destinations, image alt text, active
promotion conditions and end dates, scheduled publish time, and a configured
dealer hotline's Vietnamese phone format. External links are syntax-checked;
their remote availability is not probed. Vehicle details and campaign landing
pages can select a page from this builder as their content source; without one,
their current default layouts remain active.

## Configuring calculations and tracking

Do not enter rates, prices, fees, or tracking identifiers until the dealership
has supplied and approved them. In **Admin → Cấu hình nghiệp vụ**, create:

- `loan_rates`: key/value rows where each key is the term in months and each
  value is the confirmed annual percentage rate (for example, the structure is
  `term_months => annual_rate`; no defaults are seeded).
- `on_road_kon_tum`, `on_road_gia_lai`, and `on_road_quang_ngai`: each with
  `registration_rate_percent`, `registration_fixed`, `license_plate`,
  `road_use`, `compulsory_insurance`, and `other`. Set values only after
  verifying their applicability to the vehicle and registration location.

The public calculators remain unavailable for a region or loan term until
corresponding settings are configured. Their results are estimates, not a
price quote or a lender's offer.

Configure `META_PIXEL_ID`, `META_CAPI_ACCESS_TOKEN`, and
`META_GRAPH_API_VERSION` in the deployment secret store for Meta events.
Admins may edit the GA4, GTM, and Meta Pixel identifiers under **Giao diện →
Giao diện và menu → tracking**. The application generates the vendor tags from
these validated IDs and records each change in **Cài đặt hệ thống → Lịch sử mã
đo lường**. Arbitrary HTML and JavaScript snippets are intentionally not
accepted; server-side Meta CAPI credentials remain in the deployment secret
store. `GA4_MEASUREMENT_ID` and `GTM_CONTAINER_ID` are optional public
identifier fallbacks.
Tracking scripts load only after the corresponding visitor consent. Meta
Conversions API lead events are queued only when the visitor has separately
consented to marketing measurement and the server-side session has recorded
that choice; a form field alone cannot grant marketing consent. The normalized
phone number is hashed before transmission and browser/server events share an
event ID.

Use a database-backed queue and a supervised worker in production. Review the
privacy policy with the dealership and legal counsel before collecting real
customer data or activating tracking.

Image uploads in the page builder and vehicle gallery retain the 5 MB
validation limit and display an admin warning above 3 MB or when the aspect
ratio differs materially from the target block ratio. Page revision comparison
shows field-level changes; restoration still creates a new revision.

## Production release checklist

- Replace all dealer placeholders only with information supplied and approved
  by the dealership. Verify product, price, promotion, fee, rate, image-rights,
  and privacy-policy content before publishing it.
- Set `APP_ENV=production`, `APP_DEBUG=false`, the HTTPS `APP_URL`, a unique
  `APP_KEY`, `SESSION_SECURE_COOKIE=true`, and `SESSION_SAME_SITE=lax`. Keep
  `.env` outside the public document root and never commit it or print secret
  values in logs.
- Create a least-privilege database account; enable HTTPS and configure the
  web root to `public/`. Do not grant broad write access such as `777`.
- If traffic is behind a reverse proxy or CDN, configure Laravel to trust only
  that provider's proxy addresses so HTTPS detection and IP-based throttling
  are correct.
- Create the initial admin from deployment secrets, sign in, enroll and verify
  2FA, create named staff accounts with only required roles, then remove the
  initial-admin credentials from deployment configuration.
- Build assets and run migrations in maintenance-safe deployment order:

  ```sh
  composer install --no-dev --optimize-autoloader
  npm ci
  npm run build
  php artisan migrate --force
  php artisan storage:link
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```

- Configure a supervised database queue worker and scheduler. Confirm SMTP and
  Telegram delivery with a controlled test lead; review failed jobs without
  exposing customer data.
- Configure consent-aware tracking IDs only after the privacy notice is
  approved. Verify that rejecting optional cookies makes no analytics/marketing
  requests and that a lead without a server-recorded marketing choice never
  queues a Meta Conversions API event.
- Check mobile pages, form validation, rate limits, admin role boundaries,
  2FA, `robots.txt`, `sitemap.xml`, canonical URLs, HTTPS redirects, and
  production error pages. Use test contact details before accepting real leads.
- Back up the database and uploaded media on a schedule, encrypt backups,
  restrict access, rotate secrets, configure log rotation, and test restoring
  a backup before launch.
- Restrict access to logs and exports, avoid logging lead payloads, and ensure
  backups and generated exports are not placed in a public web directory.

The application was verified against SQLite in automated tests. Before launch,
run the migration and notification smoke tests against the actual supported
MySQL/MariaDB hosting environment.
