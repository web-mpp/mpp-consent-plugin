# WP Cookie Shield — Update Release Process

Instructions for Claude Code to process a new plugin release end-to-end.

---

## Prerequisites (already set up — verify if something seems wrong)

| Item | Value |
|---|---|
| GitHub repo | `https://github.com/web-mpp/mpp-consent-plugin` |
| GitHub user | `web-mpp` |
| PAT location | `C:\Work\MPP\Apps\mpp-consent-plugin\.env` → `GIT_PAT_CONSENT_PLUGIN` |
| Test server | `test.mppdev.net` via SSH key `~/.ssh/mppdev_key` |
| Plugin path on server | `/var/www/vhosts/mppdev.net/test.mppdev.net/wp-content/plugins/wp-cookie-shield/` |
| WP-CLI on server | `/opt/plesk/php/8.2/bin/php /usr/local/bin/wp --path=/var/www/vhosts/mppdev.net/test.mppdev.net` |

---

## Step 1 — Make and test changes

1. Edit code in `C:\Work\MPP\Apps\mpp-consent-plugin\`
2. Deploy to test server to verify:
   ```bash
   bash bin/deploy-test.sh
   ```
   This pushes to GitHub and pulls on the server. No restart needed for PHP changes.
3. Check the plugin works at `https://test.mppdev.net`

---

## Step 2 — Bump the version

Edit **`wp-cookie-shield.php`** — update in **two places**:

```php
 * Version:           1.X.X        ← plugin header (line ~6)
```
```php
define( 'WPCS_VERSION', '1.X.X' ); ← constant (line ~22)
```

Use [semantic versioning](https://semver.org/):
- **Patch** `1.0.x` — bug fixes only
- **Minor** `1.x.0` — new features, backwards compatible
- **Major** `x.0.0` — breaking changes

---

## Step 3 — Build the distributable ZIP

Run this PowerShell block:

```powershell
$src = "C:\Work\MPP\Apps\mpp-consent-plugin"; $dest = "C:\Work\MPP\Apps\wp-cookie-shield.zip"
if (Test-Path $dest) { Remove-Item $dest -Force }
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($dest, 'Create')
$excl = @('.git','node_modules','tests','CLAUDE.md','.gitignore','.gitattributes','.env')
Get-ChildItem -Path $src -Recurse -File | ForEach-Object {
    $rel = $_.FullName.Substring($src.Length + 1); $parts = $rel -split '\\'
    foreach ($ex in $excl) { if ($parts -contains $ex -or $_.Name -eq $ex) { return } }
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
        $zip, $_.FullName, "wp-cookie-shield/$($rel -replace '\\','/')", 'Optimal') | Out-Null
}
$zip.Dispose()
$i = Get-Item $dest; "Built: $($i.Name) ($([math]::Round($i.Length/1KB,1)) KB)"
```

ZIP is written to `C:\Work\MPP\Apps\wp-cookie-shield.zip` — this is the file clients install.

---

## Step 4 — Commit and push

```bash
cd "C:/Work/MPP/Apps/mpp-consent-plugin"
git add -A
git commit -m "chore: bump version to 1.X.X

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>"
```

Then push using the PAT from `.env`:

```bash
PAT=$(grep GIT_PAT_CONSENT_PLUGIN .env | cut -d= -f2)
git -c "url.https://web-mpp:${PAT}@github.com/.insteadOf=https://github.com/" push origin master
```

---

## Step 5 — Tag the release

```bash
PAT=$(grep GIT_PAT_CONSENT_PLUGIN .env | cut -d= -f2)
git tag v1.X.X
git -c "url.https://web-mpp:${PAT}@github.com/.insteadOf=https://github.com/" push origin v1.X.X
```

---

## Step 6 — Create GitHub release and upload ZIP

Run this PowerShell block (replace version and release notes):

```powershell
$PAT     = (Get-Content "C:\Work\MPP\Apps\mpp-consent-plugin\.env") -replace "GIT_PAT_CONSENT_PLUGIN=", ""
$version = "v1.X.X"
$notes   = @"
- Change one
- Change two
- Bug fix: description
"@

# Create release
$headers = @{ Authorization = "token $PAT"; "Content-Type" = "application/json" }
$body    = @{ tag_name = $version; name = $version; body = $notes; draft = $false; prerelease = $false } | ConvertTo-Json
$release = Invoke-RestMethod -Uri "https://api.github.com/repos/web-mpp/mpp-consent-plugin/releases" -Method Post -Headers $headers -Body $body
Write-Host "Release: $($release.html_url)"

# Upload ZIP
$uploadUrl     = $release.upload_url -replace '\{.*\}', '?name=wp-cookie-shield.zip'
$uploadHeaders = @{ Authorization = "token $PAT"; "Content-Type" = "application/zip" }
$asset = Invoke-RestMethod -Uri $uploadUrl -Method Post -Headers $uploadHeaders -Body ([System.IO.File]::ReadAllBytes("C:\Work\MPP\Apps\wp-cookie-shield.zip"))
Write-Host "Asset: $($asset.browser_download_url)"
```

---

## Step 7 — Verify auto-update on test server

Force WordPress to check immediately (clears the 12-hour cache):

```powershell
ssh -i "$HOME\.ssh\mppdev_key" mppdev@35.183.253.194 `
  "/opt/plesk/php/8.2/bin/php /usr/local/bin/wp --path=/var/www/vhosts/mppdev.net/test.mppdev.net transient delete wpcs_github_release && /opt/plesk/php/8.2/bin/php /usr/local/bin/wp --path=/var/www/vhosts/mppdev.net/test.mppdev.net plugin list --fields=name,version,update,update_version --format=table 2>&1 | grep wp-cookie"
```

Expected output:
```
wp-cookie-shield    1.0.0    available    1.X.X
```

You can also verify in WP Admin → Plugins on `test.mppdev.net` — the "Update available" banner should appear under the plugin.

---

## Step 8 — Update the test server to the new version

After confirming the update notification works, apply it on the test server:

```powershell
ssh -i "$HOME\.ssh\mppdev_key" mppdev@35.183.253.194 `
  "/opt/plesk/php/8.2/bin/php /usr/local/bin/wp --path=/var/www/vhosts/mppdev.net/test.mppdev.net plugin update wp-cookie-shield"
```

Or just run `bash bin/deploy-test.sh` which will `git pull` the version-bumped code directly.

---

## Quick reference — version history

| Version | Date       | Summary |
|---------|------------|---------|
| 1.0.0   | 2026-05-28 | Initial release — full GDPR/CCPA consent manager scaffold |
| 1.0.1   | 2026-05-29 | Appearance tab, multilingual support, scanner page-scan, auto-updater |
| 1.0.2   | 2026-05-29 | Fix: checkbox settings reset when saving from a different tab |
| 1.0.3   | 2026-05-29 | Fix: update notification not appearing on WP Engine / cached hosts |
| 1.0.4   | 2026-05-29 | Banner text centered; cookie policy link in preferences modal |

*Add a row here each release.*

---

## Notes

- The auto-updater polls GitHub at most every **12 hours** per site. Clear the `wpcs_github_release` transient (Step 7) to test immediately.
- The ZIP must be named **`wp-cookie-shield.zip`** and must unzip to a folder named **`wp-cookie-shield/`** — the updater's `fix_folder_name()` handles this automatically.
- The `.env` file contains the PAT and is excluded from git and from the ZIP.
- Client sites update via **WP Admin → Plugins → Update** — no manual file upload needed.
