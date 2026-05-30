# CLAUDE.md — WP Cookie Shield Plugin

> **Purpose of this file:** This is the authoritative specification and coding guide for the **WP Cookie Shield** WordPress plugin. Claude Code should read this file in full before writing, editing, or refactoring any code in this project. All architectural decisions, naming conventions, legal requirements, and feature behaviours are defined here.

---

## 1. Project Overview

| Field | Value |
|---|---|
| **Plugin Name** | WP Cookie Shield |
| **Text Domain** | `wp-cookie-shield` |
| **Version** | 1.0.0 |
| **Minimum WordPress** | 6.3 |
| **Minimum PHP** | 8.1 |
| **License** | GPL-2.0-or-later |
| **WP Consent API** | Compatible (implements consumer + API hooks) |
| **Google Consent Mode** | v2 (gtag-based, supports all 8 consent types) |

### What it does
WP Cookie Shield is a GDPR/ePrivacy/CCPA/PIPEDA/Law-25-compliant cookie consent manager for WordPress. It:
- Shows a consent **banner** (top or bottom bar) and a **preferences modal** on first visit
- Lets visitors accept all, reject all, or granularly manage cookie categories
- **Scans** the active theme and installed plugins to auto-detect cookies and third-party services that need disclosure
- Fires **Google Consent Mode v2** signals before any analytics or advertising scripts load
- Blocks non-essential scripts until the visitor grants consent
- Logs consent records to the database for audit purposes
- Exposes the **WP Consent API** so other plugins can read the granted consent categories

---

## 2. Visual Design Specification

> All UI must match the dark-navy theme shown in the design screenshots. Refer to these specs precisely.

### 2.1 Colour Palette

```css
--wpcs-bg-primary:    #0a1628;   /* banner & modal background */
--wpcs-bg-secondary:  #0d1f3c;   /* modal section rows */
--wpcs-border:        #1e3254;   /* divider lines */
--wpcs-text-primary:  #ffffff;
--wpcs-text-muted:    #a0aec0;
--wpcs-btn-accept:    #e53e3e;   /* red Accept All */
--wpcs-btn-accept-hover: #c53030;
--wpcs-btn-outline:   transparent; /* Preferences / Reject / Close */
--wpcs-btn-outline-border: #4a5568;
--wpcs-toggle-on:     #6b7280;   /* essential — greyed, non-interactive */
--wpcs-toggle-off:    #1e3254;
--wpcs-toggle-active: #3182ce;   /* enabled optional category */
```

### 2.2 Banner

```
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  We use cookies to improve your experience on our site. By using our site, you       │
│  consent to cookies.                         [Preferences]  [Reject]  [Accept All]   │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

- **Position:** fixed top (default) or fixed bottom — admin-configurable
- **z-index:** 99999
- **Full viewport width**, single row on desktop, stacked on mobile
- `Preferences` and `Reject` buttons: white outline style
- `Accept All` button: red (`--wpcs-btn-accept`)
- Persist behind page scroll (sticky)
- Dismiss (hide) only after the visitor takes an explicit action
- Never auto-dismiss

### 2.3 Preferences Modal

```
┌──────────────────────────────────────────────────────┐  ✕
│  Cookie Preferences                                  │
│  Manage your cookie preferences below:               │
├──────────────────────────────────────────────────────┤
│  ▼ Essential                               [●●●] ──  │  (greyed toggle, always ON)
├──────────────────────────────────────────────────────┤
│  ▼ Statistics                              [○○○]     │  (toggleable)
├──────────────────────────────────────────────────────┤
│  ▼ Marketing                               [○○○]     │  (toggleable)
├──────────────────────────────────────────────────────┤
│  ▼ Preferences                             [○○○]     │  (toggleable)
├──────────────────────────────────────────────────────┤
│  ▼ Cookie Policy                                     │  (link row, no toggle)
├──────────────────────────────────────────────────────┤
│  [Accept All]  [Close]              [Save and Close] │
└──────────────────────────────────────────────────────┘
```

- Expandable accordion rows: clicking a category header reveals a description + list of cookies detected
- `Essential` toggle is **always on**, greyed out, non-interactive — add tooltip "Essential cookies cannot be disabled"
- `✕` close button top-right triggers same behaviour as `Close` button
- Modal max-width: 560px, centred, with dark overlay backdrop
- `Save and Close` triggers consent save + banner dismiss

---

## 3. File & Directory Structure

```
wp-cookie-shield/
├── wp-cookie-shield.php              # Main plugin bootstrap, metadata header
├── CLAUDE.md                         # This file
├── readme.txt                        # WordPress.org readme
├── uninstall.php                     # DB cleanup on plugin deletion
│
├── includes/
│   ├── class-plugin.php              # Core Plugin singleton, load order
│   ├── class-installer.php           # Activation / deactivation hooks, DB schema
│   ├── class-consent-manager.php     # Core consent logic, cookie read/write
│   ├── class-consent-logger.php      # Audit log writes to DB
│   ├── class-cookie-scanner.php      # Theme/plugin scanner (see §8)
│   ├── class-gcm-handler.php         # Google Consent Mode v2 (see §9)
│   ├── class-script-blocker.php      # Buffer-based script blocking (see §10)
│   ├── class-geolocation.php         # IP-based jurisdiction detection
│   ├── class-rest-api.php            # REST endpoints for consent save/read
│   ├── class-wp-consent-api.php      # WP Consent API bridge
│   └── class-i18n.php                # Load text domain
│
├── admin/
│   ├── class-admin.php               # Admin menu, settings page bootstrap
│   ├── class-settings.php            # Settings registration (register_setting)
│   ├── class-scanner-page.php        # Cookie scanner admin UI
│   ├── class-consent-log-page.php    # Audit log admin table (WP_List_Table)
│   ├── views/
│   │   ├── settings-general.php      # General settings tab view
│   │   ├── settings-categories.php   # Category editor tab view
│   │   ├── settings-scanner.php      # Scanner results tab view
│   │   ├── settings-gcm.php          # Google Consent Mode tab view
│   │   ├── settings-compliance.php   # Legal/compliance tab view
│   │   └── consent-log.php           # Log table view
│   └── assets/
│       ├── admin.css
│       └── admin.js
│
├── public/
│   ├── class-frontend.php            # Enqueue public assets, output HTML
│   ├── assets/
│   │   ├── css/
│   │   │   └── cookie-shield.css     # All banner + modal styles
│   │   └── js/
│   │       ├── cookie-shield.js      # Banner/modal logic, consent save
│   │       └── cookie-shield.min.js  # Minified build (generated)
│   └── templates/
│       ├── banner.php                # Banner HTML template
│       └── modal.php                 # Preferences modal HTML template
│
├── languages/
│   └── wp-cookie-shield.pot
│
└── tests/
    ├── bootstrap.php
    ├── test-consent-manager.php
    ├── test-cookie-scanner.php
    ├── test-gcm-handler.php
    └── test-rest-api.php
```

---

## 4. Database Schema

Run on `register_activation_hook`. Use `dbDelta()`. Prefix all tables with `$wpdb->prefix`.

### 4.1 `{prefix}wpcs_consent_log`

Stores one row per consent event (page load where visitor makes a choice or consent is auto-renewed).

```sql
CREATE TABLE {prefix}wpcs_consent_log (
  id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  consent_uuid    VARCHAR(36)         NOT NULL,          -- UUID v4, stored in cookie
  user_id         BIGINT(20) UNSIGNED NULL DEFAULT NULL, -- WP user ID if logged in
  ip_hash         VARCHAR(64)         NOT NULL,          -- SHA-256 of IP (privacy-safe)
  user_agent_hash VARCHAR(64)         NOT NULL,
  consent_json    LONGTEXT            NOT NULL,          -- JSON of category => bool
  method          VARCHAR(20)         NOT NULL,          -- 'accept_all','reject_all','custom'
  version         VARCHAR(10)         NOT NULL,          -- policy version at time of consent
  jurisdiction    VARCHAR(10)         NOT NULL DEFAULT 'UNKNOWN',
  created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at      DATETIME            NOT NULL,
  PRIMARY KEY (id),
  KEY consent_uuid (consent_uuid),
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.2 `{prefix}wpcs_cookies`

Cookie declarations — populated by scanner and/or manual admin entry.

```sql
CREATE TABLE {prefix}wpcs_cookies (
  id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  cookie_name  VARCHAR(255)        NOT NULL,
  provider     VARCHAR(255)        NOT NULL DEFAULT '',
  purpose      TEXT                NOT NULL DEFAULT '',
  category     VARCHAR(50)         NOT NULL DEFAULT 'statistics',
  duration     VARCHAR(100)        NOT NULL DEFAULT '',
  cookie_type  VARCHAR(20)         NOT NULL DEFAULT 'http',   -- 'http','js','pixel'
  domain       VARCHAR(255)        NOT NULL DEFAULT '',
  source       VARCHAR(20)         NOT NULL DEFAULT 'manual', -- 'manual','scan','import'
  is_active    TINYINT(1)          NOT NULL DEFAULT 1,
  created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY category (category),
  UNIQUE KEY cookie_name_domain (cookie_name, domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.3 Options (wp_options)

All settings stored as a single serialised array under `wpcs_settings`.  
Schema defined in `class-settings.php::get_defaults()`.

---

## 5. Settings Schema (`wpcs_settings`)

```php
[
  // --- General ---
  'banner_position'        => 'top',            // 'top' | 'bottom'
  'banner_text'            => 'We use cookies to improve your experience on our site. By using our site, you consent to cookies.',
  'show_reject_button'     => true,
  'show_preferences_button'=> true,
  'policy_version'         => '1.0',            // bump to re-ask consent on policy changes
  'consent_expiry_days'    => 365,
  'prior_consent_required' => true,             // true = block scripts until consent given

  // --- Categories ---
  'categories'             => [
    'essential'   => ['label' => 'Essential',   'description' => '...', 'enabled' => true,  'locked' => true ],
    'statistics'  => ['label' => 'Statistics',  'description' => '...', 'enabled' => false, 'locked' => false],
    'marketing'   => ['label' => 'Marketing',   'description' => '...', 'enabled' => false, 'locked' => false],
    'preferences' => ['label' => 'Preferences', 'description' => '...', 'enabled' => false, 'locked' => false],
  ],

  // --- Google Consent Mode ---
  'gcm_enabled'            => false,
  'gcm_default_analytics'  => 'denied',         // 'denied' | 'granted'
  'gcm_default_ads'        => 'denied',
  'gcm_region'             => [],               // empty = global; ['GB','CA'] = geo-limited
  'gcm_wait_for_update_ms' => 500,

  // --- Script Blocking ---
  'script_blocking_enabled'=> true,
  'blocked_patterns'       => [],               // additional URL patterns to block

  // --- Geolocation ---
  'geo_enabled'            => false,            // show banner only in regulated jurisdictions
  'geo_jurisdictions'      => ['EU','CA','US-CA'], // EEA, Canada, California

  // --- Compliance ---
  'dnt_respect'            => true,             // honour Do Not Track header
  'cookie_policy_page_id'  => 0,
  'privacy_policy_page_id' => 0,

  // --- Scanner ---
  'last_scan_time'         => 0,
  'scan_frequency_days'    => 30,
]
```

---

## 6. Cookie Categories

### 6.1 Definitions

| Category | Consent Required | Examples |
|---|---|---|
| **Essential** | No (always active) | Session cookies, login, CSRF tokens, load balancer |
| **Statistics** | Yes | Google Analytics (anonymised), Matomo, Plausible |
| **Marketing** | Yes | Google Ads, Meta Pixel, LinkedIn Insight, DoubleClick |
| **Preferences** | Yes | Language selector, theme, font-size, remembered forms |

### 6.2 Category→GCM Mapping

```php
const GCM_CATEGORY_MAP = [
  'statistics'  => ['analytics_storage'],
  'marketing'   => ['ad_storage', 'ad_user_data', 'ad_personalization'],
  'preferences' => ['functionality_storage', 'personalization_storage'],
  'essential'   => ['security_storage'],          // always 'granted'
];
```

---

## 7. Consent Flow (Frontend)

```
Page Load
    │
    ├─ Read wpcs_consent cookie
    │      │
    │      ├─ Cookie exists & valid & version matches?
    │      │       └─ YES → fire GCM with stored prefs → allow scripts → END
    │      │
    │      └─ NO → fire GCM defaults (all denied) → show banner
    │
    ▼
Banner displayed
    │
    ├─ [Accept All]   → set all categories true  → save → fire GCM granted → hide banner
    ├─ [Reject]       → set non-essential false   → save → fire GCM denied  → hide banner
    └─ [Preferences]  → open modal
                            │
                            ├─ Toggle categories
                            ├─ [Accept All]  → same as banner Accept All
                            ├─ [Close / ✕]  → close modal, banner remains
                            └─ [Save and Close] → save selection → fire GCM → hide banner
```

### 7.1 Consent Cookie Format

Cookie name: `wpcs_consent`  
Value: base64-encoded JSON  
SameSite: `Lax`  
Secure: set only if `is_ssl()`

```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "version": "1.0",
  "ts": 1716912000,
  "expires": 1748448000,
  "categories": {
    "essential": true,
    "statistics": false,
    "marketing": false,
    "preferences": false
  },
  "method": "custom"
}
```

---

## 8. Cookie Scanner (`class-cookie-scanner.php`)

The scanner runs server-side and produces a list of cookies and third-party services discovered in the WordPress installation.

### 8.1 Scan Sources

1. **Known plugin signatures** — a bundled JSON map `data/known-plugins.json` that maps plugin slugs to their known cookies:
   ```json
   {
     "google-site-kit": {
       "provider": "Google",
       "category": "statistics",
       "cookies": [
         {"name": "_ga",     "duration": "2 years",  "purpose": "Distinguishes unique users"},
         {"name": "_ga_*",   "duration": "2 years",  "purpose": "Persists session state"},
         {"name": "_gid",    "duration": "24 hours", "purpose": "Distinguishes users"},
         {"name": "_gat",    "duration": "1 minute", "purpose": "Throttle request rate"}
       ]
     },
     "woocommerce": {
       "provider": "WooCommerce",
       "category": "essential",
       "cookies": [
         {"name": "woocommerce_cart_hash",     "duration": "session"},
         {"name": "woocommerce_items_in_cart", "duration": "session"},
         {"name": "wc_session_cookie_*",       "duration": "2 days"}
       ]
     }
     // ... 200+ common plugins
   }
   ```

2. **Active plugin slug scan** — loop `get_plugins()` + `is_plugin_active()`, cross-reference against `known-plugins.json`.

3. **Theme source scan** — regex scan theme's `functions.php`, `header.php`, template files for:
   - `wp_enqueue_script()` calls referencing known CDN domains (googletagmanager.com, connect.facebook.net, etc.)
   - Hardcoded `<script>` tags in templates
   - `document.cookie` write patterns in inline JS

4. **WordPress core cookies** — always included:
   - `wordpress_*`, `wp-settings-*`, `wordpress_logged_in_*` → Essential
   - `wordpress_test_cookie` → Essential

5. **WooCommerce auto-detection** — if WooCommerce active, always add WC cookie set.

6. **Page-crawl scan (optional, async)** — uses `wp_remote_get()` on the homepage URL with a headless-friendly user agent, parses `Set-Cookie` headers and `document.cookie` patterns from the returned HTML/JS.

### 8.2 Scan Result Handling

```php
interface ScanResult {
    string  $cookie_name;
    string  $provider;
    string  $category;       // 'essential'|'statistics'|'marketing'|'preferences'
    string  $purpose;
    string  $duration;
    string  $source;         // 'known_plugin'|'theme_scan'|'page_crawl'|'wp_core'
    string  $plugin_slug;    // if source = known_plugin
}
```

- Scanner merges results with existing DB rows (no duplicate on `cookie_name + domain`)
- New findings flagged as `needs_review` until admin confirms
- Scanner result displayed in admin → Settings → Scanner tab with inline edit capability

### 8.3 Scheduled Scan

Register a `wp_cron` event `wpcs_scheduled_scan` that fires every `scan_frequency_days` days.  
Store last scan timestamp in `wpcs_settings['last_scan_time']`.  
On completion send admin email notification (optional, configurable).

---

## 9. Google Consent Mode v2 (`class-gcm-handler.php`)

### 9.1 All 8 GCM Parameters

| Parameter | Default | Category |
|---|---|---|
| `analytics_storage` | denied | statistics |
| `ad_storage` | denied | marketing |
| `ad_user_data` | denied | marketing |
| `ad_personalization` | denied | marketing |
| `functionality_storage` | denied | preferences |
| `personalization_storage` | denied | preferences |
| `security_storage` | **granted** | essential (always) |
| `wait_for_update` | 500ms | — |

### 9.2 Default Consent Snippet (output in `<head>`, before any gtag/GTM)

```html
<!-- WP Cookie Shield — Google Consent Mode v2 Defaults -->
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('consent', 'default', {
    'analytics_storage':       'denied',
    'ad_storage':              'denied',
    'ad_user_data':            'denied',
    'ad_personalization':      'denied',
    'functionality_storage':   'denied',
    'personalization_storage': 'denied',
    'security_storage':        'granted',
    'wait_for_update':         500
  });
  gtag('set', 'ads_data_redaction', true);
  gtag('set', 'url_passthrough', false);
</script>
```

Output via `wp_head` priority **1** (before any other scripts).  
Only output when `gcm_enabled = true`.

### 9.3 Consent Update Call (fired by JS after visitor chooses)

```javascript
// cookie-shield.js — called after consent saved
function fireGCMUpdate(categories) {
  if (typeof gtag !== 'function') return;
  gtag('consent', 'update', {
    analytics_storage:       categories.statistics  ? 'granted' : 'denied',
    ad_storage:              categories.marketing   ? 'granted' : 'denied',
    ad_user_data:            categories.marketing   ? 'granted' : 'denied',
    ad_personalization:      categories.marketing   ? 'granted' : 'denied',
    functionality_storage:   categories.preferences ? 'granted' : 'denied',
    personalization_storage: categories.preferences ? 'granted' : 'denied',
    security_storage:        'granted'
  });
}
```

### 9.4 GCM Region Support

When `gcm_region` is non-empty, output regional defaults:

```javascript
gtag('consent', 'default', {
  'analytics_storage': 'granted',  // countries NOT in the region list get granted
  'ad_storage': 'granted'
});
gtag('consent', 'default', {
  'region': ['CA', 'GB', 'DE'], // ... configured regions
  'analytics_storage': 'denied',
  'ad_storage': 'denied'
});
```

---

## 10. Script Blocking (`class-script-blocker.php`)

When `script_blocking_enabled = true` AND the visitor has not yet given consent (no valid `wpcs_consent` cookie), non-essential scripts must be blocked.

### 10.1 Strategy

Use WordPress output buffering via `ob_start` on `template_redirect` (priority 1) and `ob_end_flush` on `shutdown`.

The buffer callback (`class-script-blocker::process_buffer()`) performs regex replacement on the HTML output:

```php
// Patterns that identify non-essential scripts
$patterns = [
  // Google Analytics / GTM
  '/googletagmanager\.com/',
  '/google-analytics\.com/',
  '/googlesyndication\.com/',
  // Meta / Facebook
  '/connect\.facebook\.net/',
  '/facebook\.com\/tr/',
  // LinkedIn
  '/snap\.licdn\.com/',
  '/linkedin\.com\/insight/',
  // HotJar
  '/static\.hotjar\.com/',
  // Intercom, Drift, etc.
  '/widget\.intercom\.io/',
  '/js\.driftt\.com/',
  // ... admin-extensible via wpcs_blocked_script_patterns filter
];
```

Replace `<script src="MATCHED_URL">` with:
```html
<script type="text/plain" data-wpcs-src="ORIGINAL_URL" data-wpcs-category="statistics">
```

And inline `<script>` blocks detected as non-essential are wrapped:
```html
<script type="text/plain" data-wpcs-inline="true" data-wpcs-category="marketing">
  // original script content
</script>
```

### 10.2 Script Release (JS side)

After consent is granted, `cookie-shield.js` iterates `[data-wpcs-src]` and `[data-wpcs-inline]` elements, checks if their declared category is now consented, and re-injects them as real `<script>` nodes.

```javascript
function releaseScripts(grantedCategories) {
  document.querySelectorAll('script[type="text/plain"][data-wpcs-src]').forEach(el => {
    const cat = el.dataset.wpcsCategory;
    if (grantedCategories[cat]) {
      const s = document.createElement('script');
      s.src = el.dataset.wpcsSrc;
      el.getAttributeNames()
        .filter(a => !['type','data-wpcs-src','data-wpcs-category'].includes(a))
        .forEach(a => s.setAttribute(a, el.getAttribute(a)));
      el.parentNode.replaceChild(s, el);
    }
  });
}
```

---

## 11. REST API (`class-rest-api.php`)

Namespace: `wp-cookie-shield/v1`

### Endpoints

| Method | Route | Auth | Description |
|---|---|---|---|
| `POST` | `/consent` | None (nonce) | Save consent choice, returns UUID |
| `GET` | `/consent/{uuid}` | None | Retrieve stored consent for a UUID |
| `GET` | `/categories` | None | Return active categories + cookie list |
| `POST` | `/scan` | Editor+ | Trigger a fresh cookie scan, returns job ID |
| `GET` | `/scan/{job_id}` | Editor+ | Poll scan status + results |

### POST `/consent` Request Body

```json
{
  "nonce": "wp_rest_nonce",
  "uuid": "...",
  "categories": { "essential": true, "statistics": true, "marketing": false, "preferences": false },
  "method": "custom",
  "version": "1.0"
}
```

### POST `/consent` Response

```json
{
  "success": true,
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "expires_at": "2027-05-28T00:00:00Z"
}
```

All REST responses include `Cache-Control: no-store, no-cache` headers.

---

## 12. WP Consent API Integration (`class-wp-consent-api.php`)

The plugin registers as a **consumer** of the WP Consent API and sets the `$wpsc_cookie` global so other plugins read consent from this plugin.

```php
// Tell WP Consent API that we manage consent
add_filter('wp_consent_api_registered_wp-cookie-shield', '__return_true');

// Map our categories to WP Consent API types
// WP Consent API types: 'statistics', 'statistics-anonymous', 'marketing', 'preferences', 'functional'
add_filter('wp_consent_category_map', function($map) {
  return array_merge($map, [
    'statistics'  => ['statistics', 'statistics-anonymous'],
    'marketing'   => ['marketing'],
    'preferences' => ['preferences', 'functional'],
    'essential'   => ['functional'],
  ]);
});

// Return current consent status when queried
add_filter('wp_has_consent', function($has_consent, $category) {
  return WPCS_ConsentManager::get_instance()->is_category_granted($category);
}, 10, 2);
```

---

## 13. Geolocation (`class-geolocation.php`)

When `geo_enabled = true`, detect the visitor's jurisdiction to decide whether to show the banner.

### Detection Method (in order of availability)

1. **Cloudflare header** — `HTTP_CF_IPCOUNTRY`
2. **Fastly header** — `HTTP_X_COUNTRY_CODE`
3. **CloudFront header** — `HTTP_CLOUDFRONT_VIEWER_COUNTRY`
4. **MaxMind GeoIP2** — if MaxMind PHP library installed and `.mmdb` path configured
5. **Fallback** — treat as regulated jurisdiction (show banner)

### Jurisdiction Groups

```php
const JURISDICTIONS = [
  'EU'   => ['AT','BE','BG','CY','CZ','DE','DK','EE','ES','FI','FR','GR',
             'HR','HU','IE','IT','LT','LU','LV','MT','NL','PL','PT','RO',
             'SE','SI','SK'], // EEA also includes IS, LI, NO, GB
  'EEA'  => ['IS','LI','NO','GB'],
  'CA'   => ['CA'],           // Canada — PIPEDA + Law 25 (Quebec)
  'US-CA'=> ['US'],           // California CCPA — detected by state from MaxMind
];
```

If visitor is not in any regulated jurisdiction and `geo_enabled = true`, skip banner and treat all categories as granted (no consent wall).

---

## 14. Legal Compliance Matrix

The plugin must satisfy **all** of the following simultaneously.

### 14.1 GDPR / UK GDPR (EU & UK)

| Requirement | Implementation |
|---|---|
| Freely given consent | Reject button always visible; no pre-ticked optional categories |
| Specific consent | Separate toggle per category |
| Informed consent | Category descriptions + cookie list in modal |
| Unambiguous | Positive opt-in action required; no implied consent |
| As easy to withdraw | `wpcs_consent` cookie clearable; re-open modal via `[wpcs_preferences]` shortcode |
| No cookie walls | Banner must not block page content; website remains accessible on Reject |
| Record of consent | `wpcs_consent_log` table; UUID stored in cookie |
| Data minimisation | IP stored as SHA-256 hash, not plaintext |
| Right of access | Admin → Consent Log; exportable as CSV |
| Policy versioning | Re-show banner when `policy_version` bumps |

### 14.2 ePrivacy Directive (Cookie Law)

| Requirement | Implementation |
|---|---|
| Prior consent for non-essential cookies | `script_blocking_enabled` blocks before consent |
| Technical/necessary exempt | Essential category always active, never blocked |
| Clear information | Cookie list with name, purpose, duration, provider |

### 14.3 CCPA / CPRA (California)

| Requirement | Implementation |
|---|---|
| Right to opt out of sale/sharing | Marketing toggle maps to "Do Not Sell or Share My Personal Information" |
| Notice at collection | Banner text customisable; link to Privacy Policy |
| No discrimination | Website fully functional on Reject |
| GPC (Global Privacy Control) | Check `Sec-GPC: 1` header; auto-set marketing to denied if present |

### 14.4 PIPEDA + Law 25 / Bill 64 (Canada / Quebec)

| Requirement | Implementation |
|---|---|
| Express consent for sensitive data | All non-essential categories require opt-in |
| Purpose limitation | Category descriptions state the purpose clearly |
| Consent record keeping | Consent log with timestamps and version |
| Right to withdraw consent | Modal re-accessible; consent cookie clearable |
| Privacy officer contact | Admin can add contact info to modal footer |

### 14.5 Do Not Track

When `dnt_respect = true`:
- Check `$_SERVER['HTTP_DNT'] === '1'`
- Auto-set Statistics and Marketing to denied
- Still show banner to inform; visitor can opt in if desired

### 14.6 Global Privacy Control (GPC)

Check `$_SERVER['HTTP_SEC_GPC'] === '1'` (JS: `navigator.globalPrivacyControl === true`)  
Auto-deny Marketing on first load. Do not override explicit user opt-in.

---

## 15. Admin UI Specification

### 15.1 Menu Structure

```
Settings
  └─ Cookie Shield             (main page, slug: wpcs-settings)
       ├─ General              (tab)
       ├─ Categories           (tab)
       ├─ Scanner              (tab)
       ├─ Google Consent Mode  (tab)
       └─ Compliance           (tab)

Tools
  └─ Consent Log              (slug: wpcs-consent-log)
```

### 15.2 Scanner Tab

- "Run Scan Now" button — triggers `POST /wp-json/wp-cookie-shield/v1/scan`
- Results table: Cookie Name | Provider | Category | Duration | Source | Actions
- Inline edit: change category assignment, edit purpose text
- "Accept All Findings" bulk action → inserts all into `wpcs_cookies`
- "Last scanned: X days ago" status line
- Warning badge if scan is overdue

### 15.3 Google Consent Mode Tab

- Enable/Disable GCM toggle
- Per-parameter default override dropdowns
- Region selector (multi-select with country flags)
- `wait_for_update` millisecond input
- Live preview of the output `<script>` snippet (read-only)
- "Test GCM" button → opens GTM Preview / Tag Assistant in new tab

### 15.4 Consent Log Tab (WP_List_Table)

Columns: Date | UUID (truncated) | Method | Jurisdiction | Categories (icon grid) | Version  
Filter by: date range, method, jurisdiction  
Export: CSV download button  
Bulk delete: allowed (admin only), with confirmation dialog  
Auto-purge: cron job removes logs older than `max(consent_expiry_days, 3 years)`

---

## 16. Shortcodes & Blocks

| Shortcode | Description |
|---|---|
| `[wpcs_preferences]` | Renders an "Manage Cookie Preferences" link that re-opens the modal |
| `[wpcs_cookie_table category="statistics"]` | Renders a formatted table of cookies in that category |
| `[wpcs_consent_status]` | Renders the visitor's current consent state (for privacy policy pages) |

Each shortcode also has a corresponding **Gutenberg block** (registered via `@wordpress/scripts` build, output in `blocks/` directory — create this directory if building blocks).

---

## 17. JavaScript Architecture (`cookie-shield.js`)

Written in **vanilla ES2020** (no jQuery dependency). Transpiled + minified via `wp_scripts` or a simple Rollup config.

### Module structure

```javascript
// cookie-shield.js — entry point
import { ConsentStore }   from './modules/consent-store.js';
import { Banner }         from './modules/banner.js';
import { Modal }          from './modules/modal.js';
import { GCMHandler }     from './modules/gcm-handler.js';
import { ScriptBlocker }  from './modules/script-blocker.js';
import { ConsentLogger }  from './modules/consent-logger.js';  // REST POST

const store    = new ConsentStore();    // reads/writes wpcs_consent cookie
const gcm      = new GCMHandler(store);
const blocker  = new ScriptBlocker(store);
const banner   = new Banner(store, gcm, blocker);
const modal    = new Modal(store, gcm, blocker);
const logger   = new ConsentLogger();   // async POST to REST API

document.addEventListener('DOMContentLoaded', () => {
  if (!store.isValid()) {
    gcm.fireDefaults();
    banner.show();
  } else {
    gcm.fireUpdate(store.getCategories());
    blocker.release(store.getCategories());
  }
});

// Public API on window for theme/plugin developers
window.WPCookieShield = { store, banner, modal, gcm };
```

### Events dispatched on `document`

```javascript
wpcs:consent_saved   // detail: { categories, method }
wpcs:banner_shown
wpcs:banner_hidden
wpcs:modal_opened
wpcs:modal_closed
wpcs:gcm_updated     // detail: { gcm_payload }
```

---

## 18. PHP Architecture Notes

### 18.1 Singleton Pattern

`class-plugin.php` uses a standard singleton:

```php
final class WPCS_Plugin {
    private static ?self $instance = null;
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function __construct() { $this->init(); }
}
```

### 18.2 Autoloading

Register a PSR-4-style autoloader in the main plugin file:

```php
spl_autoload_register(function (string $class) {
    $prefix = 'WPCS_';
    if (!str_starts_with($class, $prefix)) return;
    $map = [
        'WPCS_Plugin'         => 'includes/class-plugin.php',
        'WPCS_ConsentManager' => 'includes/class-consent-manager.php',
        // ... full map
    ];
    if (isset($map[$class])) {
        require_once plugin_dir_path(__FILE__) . $map[$class];
    }
});
```

### 18.3 Nonces

Every AJAX/REST write action must verify a nonce created via `wp_create_nonce('wpcs_consent')`.  
Every admin action must verify `check_admin_referer('wpcs_admin_action')`.

### 18.4 Sanitisation Rules

| Data | Sanitise with |
|---|---|
| Category name | `sanitize_key()` |
| Free text (banner text, descriptions) | `sanitize_textarea_field()` |
| URLs | `esc_url_raw()` |
| Integers (expiry days) | `absint()` |
| Boolean settings | `(bool) $val` |
| JSON (consent payload) | `wp_json_encode()` / `json_decode(..., true)` with strict schema validation |

---

## 19. Hooks Reference

### Filters

```php
// Modify the list of cookie categories before output
apply_filters('wpcs_cookie_categories', array $categories)

// Add extra script patterns to block
apply_filters('wpcs_blocked_script_patterns', array $patterns)

// Modify consent cookie expiry (seconds)
apply_filters('wpcs_consent_expiry', int $seconds)

// Modify banner HTML before output
apply_filters('wpcs_banner_html', string $html)

// Modify modal HTML before output
apply_filters('wpcs_modal_html', string $html)

// Modify GCM default payload
apply_filters('wpcs_gcm_defaults', array $defaults)

// Control whether banner shows for this request
apply_filters('wpcs_show_banner', bool $show)

// Modify scanner results before DB insert
apply_filters('wpcs_scan_results', array $results)
```

### Actions

```php
// Fires after a consent record is saved to DB
do_action('wpcs_consent_saved', string $uuid, array $categories, string $method)

// Fires when banner is first displayed (determined server-side on initial render)
do_action('wpcs_banner_displayed')

// Fires after cookie scan completes
do_action('wpcs_scan_complete', array $results, int $timestamp)
```

---

## 20. Testing Checklist

Before any release, all the following must pass:

### Unit Tests (PHPUnit)
- [ ] `ConsentManager::is_valid_consent()` returns false for expired/wrong-version cookies
- [ ] `ConsentManager::get_categories()` returns correct defaults
- [ ] `ConsentLogger::log()` inserts correct row to DB
- [ ] `GCMHandler::build_defaults()` returns all 8 params
- [ ] `CookieScanner::scan_plugins()` detects known plugin cookies
- [ ] REST `/consent` endpoint rejects invalid nonce
- [ ] REST `/consent` endpoint stores correct data

### Integration Tests
- [ ] Banner does not appear when valid consent cookie is present
- [ ] Banner appears on first visit (no cookie)
- [ ] "Accept All" sets all categories to true in cookie
- [ ] "Reject" sets non-essential to false in cookie
- [ ] "Save and Close" saves only toggled categories
- [ ] GCM `gtag('consent','update')` fires after choice (check dataLayer)
- [ ] Scripts with `data-wpcs-src` are NOT executed before consent
- [ ] Scripts are released after consent granted

### Legal/Compliance Checks
- [ ] No non-essential scripts fire on page load before consent
- [ ] Essential scripts fire without consent
- [ ] Reject button is always visible (not hidden behind Preferences)
- [ ] No pre-ticked optional categories
- [ ] Consent log records every accept/reject/custom event
- [ ] Re-consent triggered when policy_version changes
- [ ] DNT header respected when option enabled
- [ ] GPC header respected when marketing opt-in hasn't been explicitly given

### Accessibility (WCAG 2.1 AA)
- [ ] Banner and modal are keyboard-navigable (Tab order correct)
- [ ] Modal has correct `role="dialog"` and `aria-labelledby`
- [ ] Focus trapped inside modal when open
- [ ] Focus returns to triggering element on modal close
- [ ] All buttons have descriptive `aria-label` where needed
- [ ] Colour contrast ratio ≥ 4.5:1 for all text

---

## 21. Build & Deployment

> **Releasing a new version to clients?** Follow `AppUpdate.md` in the project root — it is the step-by-step release runbook (version bump → ZIP → commit → tag → GitHub release → verify). The commands below cover individual build tasks only.

```bash
# Install JS dependencies
npm install

# Build JS (development)
npm run build:dev

# Build JS (production — minified)
npm run build:prod

# Run PHP unit tests
composer install
./vendor/bin/phpunit

# Generate POT file
wp i18n make-pot . languages/wp-cookie-shield.pot

# Build distributable ZIP (excludes tests, node_modules, CLAUDE.md)
npm run build:zip
```

### `package.json` scripts

```json
{
  "scripts": {
    "build:dev":  "rollup -c rollup.config.js --environment BUILD:development",
    "build:prod": "rollup -c rollup.config.js --environment BUILD:production",
    "build:zip":  "bash bin/build-zip.sh",
    "lint:js":    "eslint public/assets/js/",
    "lint:php":   "./vendor/bin/phpcs --standard=WordPress includes/ admin/ public/"
  }
}
```

### Release checklist (summary — full steps in `AppUpdate.md`)

1. Make and test changes on test server
2. Bump version in `wp-cookie-shield.php` (header + `WPCS_VERSION` constant)
3. Build ZIP using the PowerShell block in `AppUpdate.md` Step 3
4. Commit and push to `master` using PAT from `.env`
5. Tag the release (`git tag vX.X.X`) and push the tag
6. Create GitHub release + upload ZIP via the PowerShell block in `AppUpdate.md` Step 6
7. Verify update appears on test server; confirm `update: none` after applying
8. Add a row to the version history table in `AppUpdate.md`

---

## 22. Known Third-Party Cookie Database (Seed Data)

The plugin ships with `data/known-plugins.json` which must include at minimum:

- `wordpress/core` — WordPress session, logged-in, test cookies
- `woocommerce/woocommerce` — cart, session, payment nonces
- `google-site-kit` — _ga, _ga_*, _gid, _gat, NID
- `monsterinsights` — GA passthrough
- `jetpack` — _jetpack_*, __jid
- `contact-form-7` — no cookies (document explicitly)
- `facebook-for-woocommerce` — _fbp, _fbc, fr
- `pixel-cat` — _fbp, pixel events
- `hotjar-*` — _hjid, _hjFirstSeen, _hjSession
- `wp-rocket` — no tracking cookies (document)
- `elementor` — elementor_viewed_27_days, _elementor_*
- `mailchimp-for-wp` — no tracking cookies (document)
- `linkedin-insight-tag` — li_at, AnalyticsSyncHistory, bcookie, bscookie
- `crisp-chat` — crisp-client
- `intercom` — intercom-id-*, intercom-session-*
- `cloudflare` — __cflb, __cf_bm, cf_clearance (essential)
- `recaptcha` — _GRECAPTCHA (essential/functional)

---

## 23. Coding Standards

- Follow **WordPress Coding Standards** (WPCS via PHP_CodeSniffer)
- All PHP files start with `<?php` and have `declare(strict_types=1);`
- No short tags, no closing PHP tag in class files
- JS follows **WordPress JavaScript Coding Standards** (ESLint config `@wordpress/eslint-plugin`)
- All public-facing strings wrapped in `__()`, `_e()`, `esc_html__()` etc. with text domain `wp-cookie-shield`
- All output escaped at the point of output (`esc_html()`, `esc_url()`, `wp_kses_post()`)
- No direct DB queries — use `$wpdb->prepare()` for all queries with user input
- All AJAX handlers check `wp_verify_nonce()` AND `current_user_can()` (for admin actions)

---

## 24. Security Hardening

- All plugin files include `if (!defined('ABSPATH')) exit;` at the top
- No `eval()`, no `base64_decode()` on user input
- `uninstall.php` runs `DROP TABLE` only with `WP_UNINSTALL_PLUGIN` constant check
- REST routes use `permission_callback` — public routes return `true`, admin routes require `manage_options`
- `wpcs_consent_log` personal data (IP hash, UA hash) is irreversible — cannot be reversed to identify the individual
- Content Security Policy headers not modified by this plugin (leave to security plugins)
- Consent cookie set with `SameSite=Lax` and `Secure` flag (when HTTPS)
- Scanner page shows "Run Scan" only to users with `manage_options` capability

---

*End of CLAUDE.md — Last updated: 2026-05-28*
