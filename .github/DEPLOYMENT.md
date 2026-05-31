# Auto Deployment (GitHub Actions → Server)

Every push to **`main`** builds the app on GitHub and deploys it to the server `beta` folder via SSH.

- Workflow file: [.github/workflows/deploy.yml](workflows/deploy.yml)
- Deploy target: `/home/u196501019/domains/getasecurityquotenow.com/public_html/beta`

## What the workflow does
1. Checks out the code
2. Installs Composer deps (`--no-dev`, optimized autoloader) on the runner
3. Installs npm deps and runs `npm run production` to build assets
4. `rsync`s the built project to the server (custom SSH port, password auth)
5. Runs `migrate`, `config/route/view:cache`, and `storage:link` on the server

## Required GitHub Secrets
Add these under **Repo → Settings → Secrets and variables → Actions → New repository secret**:

| Secret name    | Value             |
| -------------- | ----------------- |
| `SSH_HOST`     | `195.35.39.158`   |
| `SSH_PORT`     | `65002`           |
| `SSH_USERNAME` | `u196501019`      |
| `SSH_PASSWORD` | *your SSH password* |

## Notes
- `.env` on the server is **never overwritten** (it's excluded from rsync). Set it up once on the server.
- `storage/`, `node_modules/`, `.git`, and `tests/` are excluded from the upload.
- `--delete` keeps the server in sync with the repo. Files not tracked in git (and not excluded) will be removed from the server `beta` folder, so make sure server-only files live under excluded paths (e.g. `storage/`).
- Trigger manually anytime: **Actions → Deploy to Server (beta) → Run workflow**.

## First-time server setup (run once over SSH)
```bash
ssh -p 65002 u196501019@195.35.39.158
cd /home/u196501019/domains/getasecurityquotenow.com/public_html/beta
# create/upload your production .env here, then:
php artisan key:generate   # only if APP_KEY not already set
php artisan storage:link
```

## Switching to SSH key auth (more secure, optional)
Replace the password secret with a private key:
1. Generate a key: `ssh-keygen -t ed25519 -f deploy_key`
2. Add `deploy_key.pub` to `~/.ssh/authorized_keys` on the server.
3. Store the private key as secret `SSH_PRIVATE_KEY` and swap the `sshpass`/`SSHPASS`
   steps for `webfactory/ssh-agent@v0.9.0` with `ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY }}`.
