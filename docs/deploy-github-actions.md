# GitHub Actions Production Deploy

This project includes [deploy workflow](../.github/workflows/deploy.yml) for SSH-based production deploys.

## Required GitHub Secrets

Create these repository secrets in GitHub:

- `SSH_HOST`: server hostname or IP
- `SSH_PORT`: SSH port (example: `21098`)
- `SSH_USER`: SSH username
- `SSH_PRIVATE_KEY`: private key content (PEM format)
- `APP_PATH`: full app directory on server (example: `/home/sumbkqqz/skyare-laravel`)

## One-time Server Preparation

1. Add the public key pair for `SSH_PRIVATE_KEY` to `~/.ssh/authorized_keys` for your deploy user.
2. Ensure `composer`, `php`, and required PHP extensions are installed.
3. Ensure a valid `.env` exists at `APP_PATH/.env`.
4. Ensure web server points to Laravel `public` directory.

## How Deploy Runs

- Trigger automatically on push to `main`.
- You can also run manually from GitHub Actions with `workflow_dispatch`.
- Workflow syncs files via `rsync` and runs remote Laravel optimize/migrate commands.

## Recommended Safety

- Enable branch protection on `main`.
- Require CI checks to pass before merge.
- Keep all credentials in GitHub Secrets only.
