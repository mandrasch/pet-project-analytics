# Pet Project Analytics (WIP)

Pet Project Analytics allows you to track pageviews for multiple webpages within WordPress - in a private, cookie-less way. Inspiration were services like Plausible, Fathom Analytics, umami, matomo & co.

This is a fork of the [ibericode/koko-analytics/](https://github.com/ibericode/koko-analytics/), an awesome WordPress plugin created by [Danny van Kooten](https://github.com/dannyvankooten).

🚧 Status: Work in progress / not intended for live usage yet. 🚧

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

# Needed one time, install and activate plugin:
ddev wp plugin activate pet-project-analytics
```

If you change CSS/JS, you need to run `ddev npm run build` within `wp-content/plugins/pet-project-analytics` again.

### How to update plugin version (locally)

- change version in pet-project-analytics.php in php code comments as well in `\define('PP_ANALYTICS_VERSION', '1.3.10');` (current version is stored in wp_options)

This will run SQL migrations automatically (see `maybe_run_migration`)

## TODOs

- [ ] remove automatic tracking for wordpress site
- [ ] add Sites screen to add sites (title, domain)
- [ ] add siteId to all screens showing stats
- [ ] provide JS tracking script for external sites -> with siteId (or domain detection?)
- [ ] remove cookie option
- [ ] adapt optimized endpoint with buffer file
- [ ] block request from other domains
- [ ] give proper credit in PHP code comments (how to do it for GNU?)
- [ ] check compatibility with koko-analytics installed next to it

## License

This is licensed as GNU GENERAL PUBLIC LICENSE Version 3. Fork of the [ibericode/koko-analytics/](https://github.com/ibericode/koko-analytics/)
