# User-config regression suite (R00)

Twenty realistic kinds of Quick Adsense site, frozen at **v2.9.4**. Each one is
run through the plugin's real code, one after another, and every visible or
stored result is compared with a golden-master snapshot. Any release that
changes what an existing user sees, or what is stored in their database, fails
this suite before it ships.

The behaviour being frozen is listed in
[`.ai/compat/legacy-data-contract.md`](../../.ai/compat/legacy-data-contract.md)
(clause ids `C-x.y`). The plan is
[`.ai/roadmap/releases/r00-regression-safety-net.md`](../../.ai/roadmap/releases/r00-regression-safety-net.md).
[`BASELINE.md`](BASELINE.md) records the environment the snapshots came from.

## Running it

```sh
composer test:configs            # or: bin/test-user-configs.sh
bin/test-user-configs.sh 05 15   # only configs whose slug starts with 05 or 15
composer test:all                # characterization, configs, PHPUnit, AJAX
```

Output is one line per config, then the run-wide checks:

```
[01/20] 01-fresh-defaults … PASS
...
[--/20] run-wide checks (hooks, guards, independence) … PASS
```

The script exits non-zero if anything fails and prints the PHPUnit diff of a
failing config.

| Variable | Meaning |
|---|---|
| `WP_TESTS_ABSPATH` | WordPress core to run against. If unset the script installs a **throwaway** core into `.cache/wp-core/<version>/` (git-ignored) with `bin/install-wp-core.sh`. CI sets it to its own temporary core. |
| `WP_CORE_VERSION` | Version for the throwaway core (default `latest`). |
| `WP_TESTS_DB_HOST`, `_NAME`, `_USER`, `_PASSWORD` | Test database (defaults in `tests/phpunit/wp-tests-config.php`: host `db`, database `quick_adsense_tests`, prefix `qatests_`). |
| `QA_UPDATE_SNAPSHOTS=1` | Write snapshots instead of comparing. Refused when `CI` or `GITHUB_ACTIONS` is set. |
| `QA_FULL_REPORT=1` | List every mismatch instead of the first eight. |

### The real site is never touched

* `tests/phpunit/bootstrap-user-configs.php` refuses to start unless
  `WP_TESTS_ABSPATH` is set. The default in `wp-tests-config.php` is the
  developer's real WordPress root, whose `wp-content` would also load.
* It adds a `filesystem_method` filter that returns a method that does not exist,
  so `QuickAdsense\FileHandler` (which only acts on `direct`) can neither read
  nor write an `ads.txt`. It stops the run if that filter is missing, and the
  test case checks again.
* A sentinel records whether `ABSPATH . 'ads.txt'` exists, with its size and
  mtime, before anything runs. Every test fails if it changed afterwards.
* No test fires `admin_notices`, because the ads.txt notice reads the file.
* The default bootstrap (`composer test`, `composer test:ajax`) skips these tests
  and the composer scripts exclude the `user-configs` group.

## What is in this directory

```
NN-slug.php                 the 20 base configs (and any extension configs, 21+)
loader.php                  QA_User_Config_Loader: list(), load(), apply(), write_option()
fixtures/ad-codes.php       named ad payloads (exact bytes, each with a QAFX marker comment)
fixtures/posts.php          named post bodies (final HTML, after wpautop)
snapshots/<slug>/<scenario>.txt      golden masters for the front-end scenarios
snapshots/<slug>/settings-*.json     stored settings after init and after each save path
snapshots/<slug>/widgets-registry.json
snapshots/<slug>/admin-steps.txt     init/admin notices, quicktags script, ads.txt publisher ids
snapshots/<slug>/<variant>/...       the same, for configs with variants
snapshots/_global/hooks.txt          every callback the plugin registers (hook, priority, args, order)
snapshots/_global/admin-form-controls.txt   the names of every control in the settings form
BASELINE.md                 where the snapshots came from
```

The code lives in `tests/phpunit/tests/UserConfigRegressionTest.php` and
`tests/phpunit/includes/`:

| Class | Job |
|---|---|
| `QA_User_Config_Runner` | Runs one config: init, widgets, every scenario, the admin steps. |
| `QA_Plugin_Hook_Capture` | Runs only the callbacks the plugin registered on a hook, so output does not depend on the WordPress version. Also dumps the hook registry. |
| `QA_Browser_Form` | `submit()` builds the POST array a browser would send for the settings form (C-6.2). Shared by later releases. |
| `QA_Notice_Recorder` | Records notices, warnings and deprecations from plugin files in a version-independent form. |
| `QA_Snapshot_Store` | Compares, creates and diffs snapshot files. |
| `QA_File_Sentinel` | The ads.txt sentinel. |

## Config file shape

A config returns an array. Only `slug` and `options` are needed for a simple one.

| Key | Meaning |
|---|---|
| `slug` | Must equal the file name. |
| `description`, `shape` | `defaults`, `saved`, `legacy-v1`, `legacy-v2` or `corrupt`. |
| `options` | Written with `update_option()` exactly as given, **before** the plugin's `init` runs. `QA_AD('name')` and `QA_STATS($days, $factor)` build ad code and relative stats history. |
| `post_init` | Keys merged into `quick_adsense_settings` after init, without a save (config 01 only). |
| `widgets` | `[ 'sidebar-1' => [1, 2], 'sidebar-2' => [3] ]`: AdsWidget slots placed in the two test sidebars. |
| `variants` | Named overrides of `options`; each runs the whole matrix and is snapshotted under `snapshots/<slug>/<variant>/`. |
| `deleted_by_init` | Options that init is expected to delete (`AdsDisp`, `quick_adsense_2_options`). |
| `extra_scenarios` | Extra scenarios for this config: `id => [ 'target' => 'single:long', 'user' => 'admin', 'ua' => 'googlebot' ]`. |
| `ajax` | `true` also runs the click, chart and reset AJAX endpoints. |
| `geo_expect` | Countries the bundled GeoIP data must resolve the scenario addresses to. |
| `pipeline_exceptions` | `slot => reason` for ad code that core's `the_content` filters legitimately change. |
| `skip` | `scenario id => reason`. Avoid; prefer the full matrix. |

## Scenario matrix (every config)

`single-long`, `single-short`, `single-two-paras`, `single-upper-p`,
`single-empty-paras`, `single-images`, `single-more`, `single-blocks`,
`single-classic-markers`; `marker-ads1`, `-ads5`, `-ads10`, `-rndads`, `-noads`,
`-offads`, `-offdef`, `-offwidget`, `-offbegin`, `-offmiddle`, `-offend`,
`-offafmore`, `-offbflastpara`, `-mixed`; `page`, `home`, `category`, `tag`,
`date-archive`, `search`, `feed`; `logged-in-subscriber`, `logged-in-admin`;
`ua-iphone`, `ua-ipad`, `ua-googlebot`, `ua-curl`; `ref-google`, `ref-bing`,
`ref-other`; `geo-us`, `geo-in`, `geo-de`, `geo-unknown`; `head`, `footer`,
`widgets`, `assets-front`.

A snapshot has labelled sections: `== content ==` (or `head`, `footer`,
`widgets`, `assets`), `== notices ==` and `== state ==` (engine globals, whether
the settings changed, and the stats options with dates as `D-n`).

Outputs are made repeatable:

* `mt_srand( 20261112 )` immediately before every render (the plugin shuffles);
* the home URL is `http://example.org`, so the wrapper class `md5(url)` is stable;
* fixture posts have fixed IDs; nonces are verified and replaced by `{NONCE:<action>}`;
* stats are built relative to today and shown as `D-n`; if the UTC day changes
  during a config, it is re-run once from a clean state;
* notices are reduced to `<kind>:<key or stem>@<plugin file>:<line>`, so PHP 7.4
  "Undefined index" and PHP 8 "Undefined array key" read the same.
  Notices from outside the plugin and deprecations that only newer PHP raises
  are not recorded.

The admin steps (always after every front-end scenario) check, per config:
init and migration, an idempotent second init, the widget registry, the
browser-accurate no-op save (`settings-after-noop-save.json`), the same save with
Classic Editor active, saves by an administrator without `unfiltered_html` and by
an editor (all stored code must survive), the privileged partial save that erases
omitted code (C-2.3.1), the quicktags script, and the ads.txt publisher ids. One
full-pipeline `the_content` smoke check per config asserts that every ad payload
survives core's filters byte-identical.

## Snapshot policy

* **A missing snapshot fails.** Create it with `QA_UPDATE_SNAPSHOTS=1`.
* **A mismatch fails** with a unified diff of the part that changed.
* `QA_UPDATE_SNAPSHOTS=1` is **forbidden in CI**; the workflow sets it to `0`.
* Two WordPress-core formatting details are normalised at comparison time (and
  update mode does not rewrite a snapshot that differs only by them): the
  attribute order of core's sidebar wrapper (`id`/`class`), and the blank line
  core used to put before inline script data (right after a `== name ==`
  header). Both changed in WP 7.2, are not visitor-visible and are not produced
  by the plugin, so snapshots stay valid on WP 6.7, 7.1 and 7.2+.
* Every release plan has a section "Intended snapshot changes". A regenerated
  snapshot that is not listed there is a release blocker, and the commit or PR
  description must quote that section.
* The 20 base configs are **frozen forever**: their files and snapshots change
  only for an intended change a release plan lists in advance.
* Releases that add stored data append **new numbered configs** (`21-...`,
  `22-...`) instead of editing the base 20. A new *scenario* may be added to the
  matrix for all configs; it only creates new snapshot files.
* Every extension config with saved shape must satisfy
  `settings-after-noop-save.json == input` (C-10.7). The base configs cannot:
  see "Known v2.9.4 behaviour" below.

## Independence

No two configs share the same (shape, enabled positions, targeting, sinks)
signature; `test_run_wide_contract` checks it. Every contract clause is covered by
at least one config:

| Clause | 01 | 02 | 03 | 04 | 05 | 06 | 07 | 08 | 09 | 10 | 11 | 12 | 13 | 14 | 15 | 16 | 17 | 18 | 19 | 20 |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| C-1 storage: legacy options kept, `_bak`, stats, notice flag, sidebars_widgets |  | x | x |  |  |  |  |  | x |  |  |  |  |  | x |  | x | x |  | x |
| C-2.1 shape 1 (never saved) | x |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
| C-2.1 shape 2 (saved through the form) |  |  |  | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x |  | x |
| C-2.1 shape 3 (migrated from v1/v2) |  | x | x |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
| C-2.2 sanitizer rules (no-op and unprivileged saves) | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x |
| C-2.3.1 partial save erases omitted code (4b) | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x |
| C-2.3.2 sanitizer is not shape-preserving | x | x | x |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
| C-2.3.3 sanitizer attached globally after admin_init | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x |
| C-3.1 activation uses `isset()` | x |  |  | x | x |  |  |  |  |  |  |  |  |  |  |  | x |  |  | x |
| C-3.2 evaluation order and page types | x |  |  | x | x |  |  |  |  |  |  |  |  |  |  |  | x |  |  | x |
| C-3.3 max ads / widget deduction never happens |  |  |  | x | x |  | x |  | x |  |  |  |  |  |  |  | x | x | x | x |
| C-3.4 Random branch never taken (`CusAds0`) | x | x | x |  |  | x |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
| C-3.4b the `100` sentinel (empty wrapper) | x |  |  | x |  | x | x | x | x | x | x | x | x |  |  |  |  |  | x |  |
| C-3.5 middle, more tag, beginning, end | x |  |  | x | x | x |  |  |  |  | x | x | x | x |  | x |  |  |  | x |
| C-3.5 after paragraph (incl. 0 and jump-to-end) |  |  |  |  | x | x | x |  |  |  |  | x | x |  |  | x |  |  |  | x |
| C-3.5 before last paragraph |  |  |  |  | x | x | x |  |  |  |  |  |  |  |  | x |  |  |  | x |
| C-3.5 after image (plain, linked, caption, uppercase) |  |  |  |  | x | x |  | x |  |  |  |  |  |  |  |  |  |  |  |  |
| C-3.6 archive pages and `enable_all_possible_ads` | x |  |  |  | x |  |  |  |  |  |  |  |  |  |  |  |  |  |  | x |
| C-3.7 wrapper markup, alignment styles, margins |  |  |  | x | x |  |  |  |  |  |  |  |  |  |  | x |  |  | x |  |
| C-3.8 empty paragraph placeholders |  |  |  |  |  |  | x |  |  |  |  |  |  |  |  |  |  |  |  |  |
| C-3.9 quicktag markers the cleaner does not know |  |  |  |  |  | x |  |  |  |  |  |  |  |  |  |  | x |  |  |  |
| C-3.10 device targeting |  |  |  |  |  |  |  |  |  |  | x |  |  |  |  |  |  |  |  | x |
| C-3.10 referer targeting |  |  |  |  |  |  |  |  |  |  |  | x |  |  |  |  |  |  |  | x |
| C-3.10 visitor type (logged-in, guest, bot, browser) |  |  |  |  |  |  |  |  |  |  |  |  | x |  |  |  |  |  |  | x |
| C-3.10 geo targeting |  |  |  |  |  |  |  |  |  |  |  |  |  | x |  |  |  |  |  | x |
| C-3.11 header and footer code |  |  |  |  |  |  |  |  |  | x |  |  |  |  |  |  |  | x |  | x |
| C-3.12 stats (impressions, trimming, click) |  |  |  |  |  |  |  |  |  |  |  |  |  |  | x |  |  |  |  | x |
| C-4.1 widget registration and ids |  | x | x |  |  |  |  |  | x |  |  |  |  |  |  |  | x | x |  | x |
| C-4.2 widget output, kses, homepage warning |  |  |  |  |  |  |  |  | x |  |  |  |  |  |  |  | x | x |  | x |
| C-5.1 post markers (every `m-*` post) | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x |
| C-5.2 quicktag buttons script | x |  | x |  |  | x |  |  |  |  |  |  |  |  |  |  | x |  |  | x |
| C-6.1 byte-for-byte ad code |  |  |  |  |  |  |  |  |  | x |  |  |  |  |  |  |  | x |  |  |
| C-6.2 browser round trip of ad code | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x |
| C-6.3 only `unfiltered_html` users change code | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x |
| C-7.1 existing array is left alone |  |  |  | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x |
| C-7.2 v2.0 migration |  |  | x |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
| C-7.3 v1.x migration |  | x |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
| C-7.4 new install and non-array value | x |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  | x |  |
| C-8 settings page render, publisher IDs | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x | x |
| C-8 AJAX: click, chart, reset, bad indexes |  |  |  |  |  |  |  |  |  |  |  |  |  |  | x |  |  |  |  | x |

`x` means the config exercises the clause. Rows marked "all" are exercised by the
admin steps and the scenario matrix of every config.

## Known v2.9.4 behaviour the snapshots record

These are real behaviours found while baselining. They are frozen on purpose and
are the first candidates for an opt-in fix (C-10.4):

* **Stats trimming destroys the date keys.** `array_shift()` renumbers numeric
  keys, so once an array holds 30 days, every date whose day is 10 or more
  becomes `0,1,2,...`. Today's key is then never found again: every page view
  appends a new `{i:1,c:0}` entry instead of adding to today's. Snapshots record
  such arrays as ordered entries only, because which keys survive depends on the
  calendar.
* **The classic form cannot round-trip some keys.** The "Hide on Mobile" box is
  never rendered checked (its value is read with the wrong key), so a classic save
  clears every `onpost_ad_N_hide_device_mobile`. The quicktag switches are only in
  the form when the Classic Editor plugin is active, so a classic save without it
  drops `enable_quicktag_buttons` and the three `disable_*_quicktag_button(s)`.
  The hidden `widget_global_alignment` and `widget_global_margin` appear on the
  first save. Compare `settings-after-noop-save.json` with
  `settings-after-noop-save-classic-editor.json` and `settings-after-init.json`.
* `<!--OffDef-->` raises "Undefined variable $cusrnd" warnings (the variable is
  only set when the default positions run).
* Real `the_content` runs `do_shortcode` after the engine, so a shortcode inside
  ad code (`[gallery]` in the `nasty` payload) is not byte-identical in a full
  render, although the plugin's own output is. Config 18 lists it under
  `pipeline_exceptions`.

## Adding or changing things

* **New extension config:** add `21-short-name.php`, run
  `QA_UPDATE_SNAPSHOTS=1 bin/test-user-configs.sh 21`, review the new files, then
  run everything without the flag. Nothing else needs registering.
* **New scenario:** add it to `QA_User_Config_Runner::scenarios()` and regenerate;
  only new snapshot files should appear.
* **Intended change to a frozen snapshot:** list it in the release plan first.
