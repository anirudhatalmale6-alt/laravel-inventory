# Deploying

Written for a fresh Ubuntu 24.04 server. Any provider works; the app is small
enough that a 1 to 2 GB box is plenty.

## Picking a server

| Option | Rough cost | Notes |
| --- | --- | --- |
| Hetzner Cloud CX22 | ~4 to 5 EUR / month | 2 vCPU, 4 GB. Best value. EU and US regions. |
| DigitalOcean / Vultr basic | ~6 USD / month | 1 GB. Pricier per GB, friendlier control panel. |
| Contabo | cheaper again | Fine hardware, slow support. |
| Shared hosting with SSH | ~3 USD / month | Runs Laravel fine, but deploys are manual. |

On a 1 GB box `composer install` can be killed by the out-of-memory reaper.
`provision.sh` adds a 2 GB swap file for exactly this reason.

## Steps

    # as root on the new server
    git clone https://github.com/anirudhatalmale6-alt/laravel-inventory.git /tmp/app
    bash /tmp/app/deploy/provision.sh yourdomain.com

    # create the database (the script prints these too)
    mysql -e "CREATE DATABASE inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -e "CREATE USER 'inventory'@'localhost' IDENTIFIED BY 'CHANGE_ME';"
    mysql -e "GRANT ALL ON inventory.* TO 'inventory'@'localhost';"

    # first deploy: clones and stops so you can fill in .env
    su - deploy -c "bash /var/www/inventory/deploy/deploy.sh"

    # edit /var/www/inventory/.env, then run it again to migrate and go live
    su - deploy -c "bash /var/www/inventory/deploy/deploy.sh"

    # HTTPS, once DNS resolves
    certbot --nginx -d yourdomain.com -d www.yourdomain.com

After that every update is one command:

    su - deploy -c "bash /var/www/inventory/deploy/deploy.sh"

which pulls, installs, migrates, rebuilds the caches and checks that every stock
level still matches its ledger.

## Things that bite

- `APP_DEBUG=false` in production. Left true, any error page prints a stack trace
  containing the database credentials.
- Certbot is asked for both `yourdomain.com` and `www.yourdomain.com`. If one of
  them is missing from the nginx `server_name`, the whole certificate request
  fails rather than just that name.
- `BUILD_ASSETS=0 bash deploy/deploy.sh` skips npm if you would rather not install
  Node on the server. In that case commit `public/build` or upload it.
- The first `deploy.sh` run intentionally stops after cloning. That is not an
  error, it is waiting for `.env`.
