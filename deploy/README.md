# Deploying Orbit

Orbit runs on a VPS (Ubuntu 24.04, e.g. Hostinger) behind nginx and PHP-FPM, with SQLite, at
`https://orbit.smszs.tech`. Every push to `main` whose tests pass is deployed by
[`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml): GitHub Actions builds the release (Composer
without dev packages, the Vite build), uploads it to a new directory under `/var/www/orbit/releases`, and
[`activate.sh`](activate.sh) migrates the database and switches the `current` symlink to it in one step.

## One-time setup

1. **DNS.** Add an `A` record `orbit` → the VPS's IP address (in the panel where `smszs.tech`'s DNS lives).
2. **Server.** Open a root shell on the VPS (SSH, or the browser terminal in Hostinger's panel) and run:

    ```bash
    curl -fsSL https://raw.githubusercontent.com/smssdev/orbit/main/deploy/provision.sh -o provision.sh
    less provision.sh   # read it before running it
    bash provision.sh orbit.smszs.tech you@example.com
    ```

    It installs PHP 8.4, nginx, SQLite and certbot, opens ports 22/80/443, creates a `deploy` user with its own SSH
    key, writes `/var/www/orbit/shared/.env` (with a fresh `APP_KEY`), configures nginx and gets the HTTPS
    certificate. If DNS is not live yet it says so: run it again once it is. Running it again is safe.

3. **GitHub secrets.** In _Settings → Secrets and variables → Actions_, add the three values the script prints at
   the end: `SSH_HOST` (the server's IP), `SSH_USER` (`deploy`) and `SSH_KEY` (the private key, BEGIN and END lines
   included). Add `SSH_PORT` only if SSH does not listen on 22.
4. **First deploy.** _Actions → deploy → Run workflow_. Later deploys happen on their own after each merge.

## On a CloudPanel server

Do **not** run `provision.sh` on a server managed by CloudPanel: it adds a second PHP repository and its own nginx
site, which clash with CloudPanel's packages and the sites already there. The production server for
`orbit.smszs.tech` is a CloudPanel server, set up like this instead:

1. `clpctl site:add:php --domainName=orbit.smszs.tech --phpVersion=8.4 --vhostTemplate='Laravel 12' --siteUser=orbit --siteUserPassword='…'`
2. In `/home/orbit/htdocs/orbit.smszs.tech`: `releases/`, `shared/` (`.env`, `storage/`, `database.sqlite`, made
   the same way as in `provision.sh`), and `public` as a symlink to `current/public`, so CloudPanel's vhost serves
   the live release without changes.
3. The `orbit` user has a `github_actions` key in its `authorized_keys`, may run
   `sudo systemctl reload php8.4-fpm` (`/etc/sudoers.d/orbit-deploy`), and runs the scheduler from its crontab.
4. GitHub: secrets `SSH_HOST`, `SSH_USER` (`orbit`), `SSH_KEY`, and the repository **variable**
   `APP_DIR` = `/home/orbit/htdocs/orbit.smszs.tech`.
5. Vhost (CloudPanel → Sites → orbit.smszs.tech → Vhost): the backend listens on `127.0.0.1:8091` and the HTTPS
   server proxies to it (`proxy_pass http://127.0.0.1:8091;`), with Varnish off. The template's default port 8080
   is taken by the `legends-app` Docker container on this server, so keep 8091 if you edit the vhost.
6. HTTPS: `clpctl lets-encrypt:install:certificate --domainName=orbit.smszs.tech` once DNS points at the server.

Paths below use `/var/www/orbit`; on CloudPanel read them as `/home/orbit/htdocs/orbit.smszs.tech`.

## Day to day

| Task                              | How                                                                                                                              |
| --------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| Change settings (mail, app name…) | Edit `/var/www/orbit/shared/.env`, then `cd /var/www/orbit/current && php artisan optimize`                                      |
| Logs                              | `/var/www/orbit/shared/storage/logs/`                                                                                            |
| Roll back                         | `ln -sfn /var/www/orbit/releases/<older> /var/www/orbit/current && sudo systemctl reload php8.4-fpm` (migrations are not undone) |
| Back up the data                  | Copy `/var/www/orbit/shared/database.sqlite`                                                                                     |

Until a real mailer is set in `.env`, invitation emails are written to the log instead of being sent.
