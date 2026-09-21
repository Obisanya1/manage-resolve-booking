# Updating this plugin via GitHub

This plugin bundles the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)
library (`vendor/plugin-update-checker/`). Once it's pointed at a GitHub repo, WordPress
shows a normal "Update available" notice + Update button on the Plugins page — same as any
WordPress.org plugin — sourced from that repo's GitHub Releases instead.

## One-time setup

1. **Create a GitHub repo** called `manage-resolve-booking` (public is simplest and free;
   private works too, see below). Push this whole plugin folder to it as the `main` branch —
   the repo root should be this folder itself (i.e. `manage-resolve-booking.php` sits at the
   repo root, not inside a subfolder).

2. **Edit one line** in `manage-resolve-booking.php` — replace the placeholder in:

   ```php
   define( 'MRB_UPDATE_REPO', 'https://github.com/YOUR-GITHUB-USERNAME/manage-resolve-booking/' );
   ```

   with your actual GitHub username (or org).

3. **Re-upload that one file** to the live site (see the "replace files without reinstalling"
   steps from before). That's it — updates are now wired up.

4. **If the repo is private**, you additionally need a GitHub personal access token so the
   live site can read it:
   - GitHub → your avatar → Settings → Developer settings → Personal access tokens →
     Tokens (classic) → Generate new token → scope: just `repo`.
   - Paste it into the commented-out line in `init_update_checker()`:
     ```php
     $update_checker->setAuthentication( 'ghp_xxxxxxxxxxxxxxxxxxxx' );
     ```
   - Treat that token like a password — anyone with it can read the repo.

## Every time you want to ship an update

1. Make your code changes locally.
2. Bump the version number in **two spots** in `manage-resolve-booking.php` (keep them
   matching):
   ```php
   * Version:     1.1.1        // the header comment near the top
   define( 'MRB_VERSION', '1.1.1' );   // a few lines below it
   ```
3. Commit and push to the `main` branch on GitHub.
4. On GitHub, go to **Releases → Draft a new release**, tag it (e.g. `v1.1.1` — matching the
   version above makes it easiest to track, though the tag name itself doesn't need to be
   exact), and publish it. Whatever you write in the release description shows up when
   someone clicks "View version details" on the Plugins page in wp-admin.
5. Within a few hours (PUC checks every 12 hours by default) — or immediately, by going to
   **Plugins** in wp-admin and clicking **"Check for updates"** next to this plugin's "Visit
   plugin site" link — the site will show the update and let you click **Update Now**, exactly
   like a normal plugin update. No manual file replacement needed from that point on.

## Notes

- Steps 2–5 above are the *only* things that changed by adding this library — everything
  from the previous "replace files manually" approach still works too, if you're ever in a
  hurry and don't want to wait for a formal release.
- The auto-generated GitHub source zip is used by default — you don't need to build or
  upload a separate zip file per release.
- `readme.txt` in this plugin (WordPress.org format) is what PUC parses for the changelog
  shown in that "View version details" popup — keep its `Stable tag` roughly in sync with
  the plugin header version if you want that detail to be accurate, though it isn't required
  for updates to work.
