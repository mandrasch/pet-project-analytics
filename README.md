# Pet Project Analytics (pp_analytics)

Pet Project Analytics allows you to track pageviews for multiple webpages within WordPress - in a private, cookie-less way. Inspiration were services like Plausible, Fathom Analytics, umami, matomo & co.

🚧 Status: Experimental / use with caution / not intended for live usage yet. 🚧

This is a fork of the [ibericode/koko-analytics/](https://github.com/ibericode/koko-analytics/), a WordPress plugin created by [Danny van Kooten](https://github.com/dannyvankooten).

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

## License

This is licensed as GNU GENERAL PUBLIC LICENSE Version 3. Fork of the [ibericode/koko-analytics/](https://github.com/ibericode/koko-analytics/)
