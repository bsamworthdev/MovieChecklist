# Automated checks and Forge deployment

GitHub Actions runs `.github/workflows/tests.yml` for pull requests targeting `master` and for every push to `master`, including a merged PR. It installs dependencies, builds the production frontend, runs the entire PHPUnit suite with an isolated SQLite test database, and runs every JavaScript test under `tests/js`.

Local equivalents:

```bash
composer test
npm run build
npm test
```

## One-time Forge setup

Open Forge → your server → **Sites → moviechecklist.co.uk → Deployments**.

1. Set the deployment branch to `master`.
2. Disable **Push to deploy** (formerly Quick Deploy), so Forge does not deploy before GitHub's tests finish.
3. Copy the site's **Deploy hook** URL. In GitHub → MovieChecklist → Settings → Secrets and variables → Actions, create a repository secret named `FORGE_DEPLOY_HOOK_URL` containing that URL. Keep this token-bearing URL out of source control.
4. Open **Deploy script** on the same Forge Deployments page. Replace its contents with the complete contents of [`.forge/deploy.sh`](../.forge/deploy.sh). This preserves your app directory, Composer install and locked PHP-FPM reload, with migrations/cache clearing moved before the reload.

The workflow's Forge job runs only after both test suites pass on a push to `master`. Pull request runs only test and never deploy. The hook queues a Forge deployment; GitHub's trigger job confirms acceptance, not completion. Check Forge's deployment log for migration/cache results. The hook also supplies `tested_commit`. Forge exposes it as `FORGE_VAR_TESTED_COMMIT`; the deploy script explicitly checks out that tested commit. Older CI runs are skipped if master has moved on, preventing out-of-order deploys. Forge's built-in commit parameter is only a history label. A manual Forge deployment without the custom parameter continues to deploy the configured branch.

The replacement script calls `bash .forge/post-deploy.sh` after Composer installs dependencies and before PHP-FPM reloads. Both files must be merged into `master` before using this deployment configuration. The repository's compiled frontend assets continue to be deployed from Git, matching the previous setup.

The helper uses Forge's configured PHP binary. It runs pending live migrations with `--force`, clears Laravel's application/config/route/view caches with `optimize:clear`, and ensures the public storage link exists. It exits immediately if migrations fail, so later helper commands do not run after a migration failure. Running it during a manual deployment has the same effect. It never runs the tests against the production database.

Commit and merge these files into `master` to activate the GitHub workflow. The Forge script change and GitHub secret must be saved in their respective dashboards; committing files cannot change those external settings by itself.

## Require tests before merging

In GitHub's rules for `master`, require the **PHP and JavaScript tests** status check and require the branch to be up to date before merging. Wait for the first workflow run if the check is not listed yet. The post-merge test run also gates the Forge deployment hook. Keep Push to deploy disabled; otherwise Forge can deploy before this gate finishes.

References: [Forge deployments](https://laravel.com/forge/docs/sites/deployments), [GitHub protected branches](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/about-protected-branches).
