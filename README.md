# Pet Project Analytics (WIP)

Pet Project Analytics allows you to track pageviews for multiple webpages within WordPress - in a privacy-friendly way. Inspiration were services like Plausible, Fathom Analytics, umami, matomo, Koko Analytics, Statify & co.

This is a fork of [ibericode/koko-analytics](https://github.com/ibericode/koko-analytics/), an awesome WordPress plugin created by [Danny van Kooten](https://github.com/dannyvankooten).

Status: 🚧 Work in progress / not intended for live usage yet. 🚧

## Local Development

```bash
ddev start

# Setup and install WordPress, choose your admin password (like password123!)
ddev wp core download
ddev wp core install --url='$DDEV_PRIMARY_URL' --title='NewWordPress' --admin_user=admin --admin_email=admin@example.com --prompt=admin_password

ddev launch /wp-admin

# Install composer for plugin (see composer_root in .ddev/config.yaml)
ddev composer install

# Install npm deps, build assets
cd wp-content/plugins/pet-project-analytics
ddev npm install
ddev npm run build

# Needed one time, activate plugin:
ddev wp plugin activate pet-project-analytics
```

If you change CSS/JS, you need to run `ddev npm run build` within `wp-content/plugins/pet-project-analytics` again. Beware: If linting fails, file will be empty. Run `ddev npm run lint` before to check.

Demo site for tracking: [https://pet-project-analytics.ddev.site/demo/](https://pet-project-analytics.ddev.site/demo/)

To check the WP crons, you can use https://de.wordpress.org/plugins/wp-crontrol/. There is `pp_analytics_aggregate_stats` which will read the buffer file and insert visits in the database. By default the buffer file is located at `/wp-content/uploads/pageviews.php`. See `wp-content/debug.log` for debugging / enable WP_DEBUG logging.

Example of buffer file:

```bash
<?php exit; ?>
p,1,https://pet-project-analytics.ddev.site/demo-site.html,1,,
```

### How to update plugin version (locally)

- change version in pet-project-analytics.php in php code comments as well in `\define('PP_ANALYTICS_VERSION', '1.3.10');` (current version is stored in wp_options)

This will run SQL migrations automatically (see `maybe_run_migration`)

## TODOs

- [x] remove automatic tracking for wordpress site (PHP)
- [ ] remove adding tracking JS to WP Site
- [ ] reorganize admin menu structure
- [ ] Re-route settings, screen --> move to new parent menu
- [ ] Add cookie detection of unique visitors, allow opt-out as well? Or use plausibles way (cookieless)? https://plausible.io/data-policy#how-we-count-unique-users-without-cookies - but this would mean this needs to be stored in db
    - [ ] https://www.kokoanalytics.com/kb/does-koko-analytics-use-cookies/ - just disable the defaults? problem for GDPR is that "list of viewed pages" could be considered personal data (and therefore acceptance would be needed). because on a shared computer, you could check what the other person visited on a webpage ...
- [ ] remove dashboard widgets (for now)
- [ ] remove optimized endpoint, we need to use POST/xhr from outside (maybe re-add it later if needed)
- [ ] add Sites screen to add sites (title, domain) - WIP
- [ ] Add sites: proper validation for domains/subdomains
- [ ] View site: update title, domain
- [ ] add siteId to all screens showing stats
- [ ] provide JS tracking script for external sites -> with siteId (or domain detection?)
- [ ] change page tracking (wordpress post/pages) to URL path tracking
- [ ] adapt optimized endpoint with buffer file
- [ ] block request from other domains
- [ ] give proper credit in PHP code comments (how to do it for GNU?)
- [ ] check compatibility with koko-analytics installed next to it
- [ ] fix cronjob
- [ ] check wp-options --> still references to kokoanalytics
- [ ] fix/adapt uninstall
- [ ] rework admin-site-management to use `manage_pp_analytics` cap
- [ ] generally rename to PP Analytics to make it shorter?
- [ ] re-test fresh install, remove wp db ... 
- [ ] test uninstall, is everything removed?
- [ ] release 1.0.0, just for fun

## License

This is licensed as GNU GENERAL PUBLIC LICENSE Version 3. Fork of [ibericode/koko-analytics/](https://github.com/ibericode/koko-analytics/) by [Danny van Kooten](https://github.com/dannyvankooten), v1.3.10.
