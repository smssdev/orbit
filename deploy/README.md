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

## Day to day

| Task                              | How                                                                                                                              |
| --------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| Change settings (mail, app name…) | Edit `/var/www/orbit/shared/.env`, then `cd /var/www/orbit/current && php artisan optimize`                                      |
| Logs                              | `/var/www/orbit/shared/storage/logs/`                                                                                            |
| Roll back                         | `ln -sfn /var/www/orbit/releases/<older> /var/www/orbit/current && sudo systemctl reload php8.4-fpm` (migrations are not undone) |
| Back up the data                  | Copy `/var/www/orbit/shared/database.sqlite`                                                                                     |

Until a real mailer is set in `.env`, invitation emails are written to the log instead of being sent.
