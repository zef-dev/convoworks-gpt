# Convoworks GPT – Build Guide

This document describes how to build the Convoworks GPT plugin for distribution and how to run code quality tools.

---

## Overview

Convoworks GPT is a WordPress plugin that extends Convoworks WP with AI/GPT capabilities. Unlike the main Convoworks WP plugin, this extension has:

- **No frontend JavaScript bundles** (no Webpack, no AngularJS UI)
- **Simple build process** (just packaging with composer dependencies)
- **Minimal external dependencies** (mostly just autoloader)
- **GitHub Actions for releases** (automated release builds and distribution)

---

## Prerequisites

- **PHP**: 7.2+
- **Composer**: latest stable
- **Node.js & npm**: for build scripts
- **PHPStan**: for static analysis (installed via Composer)

---

## Build Process

### Development Build (No Build Needed)

For local development when you have a dedicated test WordPress site:

```bash
# Install dependencies
composer install

# Activate in WordPress
# Just symlink or copy the plugin directory to wp-content/plugins/convoworks-gpt/
```

In development mode, no build is needed. All code runs directly from the source directory.

### Release Build (via GitHub Actions)

Releases are automated through GitHub Actions. When you push a tag or create a release:

1. GitHub Actions runs the build pipeline
2. Version is synced across files
3. Dependencies are installed with `composer install --no-dev`
4. A distributable zip is created
5. The zip is attached to the GitHub release

**Manual release build** (if needed locally):

```bash
npm run build
```

This creates `build/convoworks-gpt-vX.Y.Z.zip` with:
- Source files from `src/`, `assets/`
- `composer install --no-dev` output
- README, CHANGELOG, and other metadata files

---

## Code Quality Tools

### PHPStan - Static Analysis

PHPStan is configured to analyze the codebase for type errors, bugs, and code quality issues.

#### Running PHPStan

```bash
# Run analysis on src/ directory
vendor/bin/phpstan analyse

# With custom memory limit
vendor/bin/phpstan analyse --memory-limit=1G

# Generate baseline (to ignore existing errors)
vendor/bin/phpstan analyse --generate-baseline
```

#### Configuration

PHPStan is configured via `phpstan.neon`:

```neon
parameters:
    level: 5
    paths:
        - src
    excludePaths:
        - tests
    ignoreErrors:
        # WordPress functions/classes/constants
        - '#Function (wp_|get_|set_|add_|remove_|apply_|do_|esc_|__)[a-zA-Z_]+ not found\.#'
        - '#Constant (WP_|ABSPATH)[A-Z_]+ not found\.#'
        - '#Class (WP_|wpdb)[a-zA-Z_]+ not found\.#'
```

**Key points**:
- **Level 5**: Good balance between strictness and practicality
- **Ignores WordPress symbols**: Since WordPress isn't installed during analysis
- **Analyzes src/ only**: Tests are excluded

#### Understanding PHPStan Levels

- **Level 0-3**: Basic checks (undefined variables, unknown methods on known types)
- **Level 4-5**: More strict (unknown methods, property access, type inference)
- **Level 6-9**: Very strict (full type coverage, generics, strict comparison)

You can adjust the level in `phpstan.neon` based on your needs.

#### Adding WordPress Stubs (Optional)

For better analysis of WordPress code, you can install WordPress stubs:

```bash
composer require --dev php-stubs/wordpress-stubs
```

Then uncomment the bootstrapFiles section in `phpstan.neon` and add a bootstrap file that includes the stubs.

### PHPUnit - Unit Testing

Run unit tests with:

```bash
vendor/bin/phpunit
```

Tests are located in `tests/`:
- `ProcessJsonWithConstantsTest.php` – JSON processing
- `SummarizeMessagesTest.php` – Message summarization
- `TruncateToSizeTest.php` – Token-based truncation

---

## Build Scripts

All build scripts are defined in `package.json`:

```json
{
  "scripts": {
    "build": "node sync-version.js && node build.js",
    "sync-version": "node sync-version.js",
    "clean": "node -e \"require('fs-extra').removeSync('dist'); require('fs-extra').removeSync('.workspace'); require('fs-extra').removeSync('build');\""
  }
}
```

### `npm run build` (Package Build)

1. Syncs version from `package.json` to `composer.json` and main plugin file
2. Runs `build.js`:
   - Creates temp directory in `dist/temp/convoworks-gpt-vX.Y.Z/`
   - Copies: `assets/`, `src/`, `convoworks-gpt.php`, `composer.json`, `composer.lock`, `README.md`, `CHANGELOG.md`
   - Runs `composer install --no-dev` in temp directory
   - Creates zip in `build/convoworks-gpt-vX.Y.Z.zip`
   - Cleans up temp directory

### `npm run sync-version` (Version Sync)

Syncs version from `package.json` to:
- `composer.json` (version field)
- `convoworks-gpt.php` (plugin header)

### `npm run clean` (Clean Build Artifacts)

Removes all build artifacts:
- `dist/` – Build output
- `.workspace/` – Temporary workspace (if any)
- `build/` – Final release zips

---

## Version Management

Version is managed in `package.json` as the single source of truth.

To bump version:

1. Edit `version` field in `package.json`
2. Run `npm run sync-version` to propagate to:
   - `composer.json` (version field)
   - `convoworks-gpt.php` (plugin header)
3. Update `CHANGELOG.md` manually
4. Commit changes
5. Create a Git tag: `git tag vX.Y.Z`
6. Push with tags: `git push && git push --tags`
7. GitHub Actions will automatically build and create a release

---

## Build Output Structure

```
build/
  convoworks-gpt-vX.Y.Z.zip
    convoworks-gpt/
      assets/
        mcp-logo-wide.png
      src/
        Convo/
          Gpt/
            Admin/
            Mcp/
            Pckg/
            Tools/
            ...
      vendor/
        autoload.php
        composer/
        ...
      convoworks-gpt.php
      composer.json
      README.md
      CHANGELOG.md
```

---

## Development Workflow

### Local Development

1. Clone/symlink plugin to `wp-content/plugins/convoworks-gpt/`
2. Run `composer install` (includes dev dependencies)
3. Activate plugin in WordPress admin (requires Convoworks WP)
4. Make changes to PHP files – no build step needed
5. Run `vendor/bin/phpstan analyse` to check for issues
6. Run `vendor/bin/phpunit` to run tests
7. Test in WordPress

### Pre-Commit Checklist

Before committing changes:

```bash
# Run PHPStan
vendor/bin/phpstan analyse

# Run tests
vendor/bin/phpunit

# Check that everything works in WordPress
```

### Creating a Release

1. Make and test your changes locally
2. Run PHPStan and PHPUnit
3. Update version in `package.json`
4. Run `npm run sync-version`
5. Update `CHANGELOG.md`
6. Commit changes
7. Create Git tag: `git tag v0.16.2`
8. Push: `git push && git push --tags`
9. GitHub Actions will build and create the release automatically
10. Verify the release on GitHub

---

## GitHub Actions

The release process is automated via GitHub Actions (`.github/workflows/release.yml`).

When you push a tag matching `v*` (e.g., `v0.16.2`):

1. **Checkout code** – Pulls the tagged commit
2. **Setup PHP and Composer** – Installs PHP 7.4 and Composer
3. **Setup Node.js** – Installs Node.js and npm
4. **Install dependencies** – Runs `npm install` and `composer install --no-dev`
5. **Build plugin** – Runs `npm run build`
6. **Create release** – Uploads the zip to GitHub Releases

This ensures consistent, reproducible builds without requiring manual intervention.

---

## Troubleshooting

### Composer dependency conflicts

If `composer install` fails:

1. Check `composer.json` for invalid constraints
2. Run `composer update --verbose` to see detailed error
3. Try `composer clear-cache` and retry

### PHPStan memory issues

If PHPStan runs out of memory:

```bash
# Increase memory limit
vendor/bin/phpstan analyse --memory-limit=2G

# Or analyze fewer files at once
vendor/bin/phpstan analyse src/Convo/Gpt/Pckg/
```

### PHPStan reports too many errors

If you're getting overwhelmed with errors:

1. **Lower the level** in `phpstan.neon` (e.g., from 5 to 3)
2. **Generate a baseline**:
   ```bash
   vendor/bin/phpstan analyse --generate-baseline
   ```
   This creates `phpstan-baseline.neon` that ignores existing errors
3. **Fix errors incrementally** over time

### Build fails on GitHub Actions

Check the Actions tab in GitHub to see the error:
- **Composer install failed**: Check `composer.json` syntax
- **npm run build failed**: Check `package.json` scripts
- **Version mismatch**: Ensure `npm run sync-version` was run

---

## Comparison with Convoworks WP Build

| Aspect | Convoworks WP | Convoworks GPT |
|--------|---------------|----------------|
| Frontend bundles | Yes (Webpack, AngularJS, React) | No |
| Build scripts | Complex (Webpack, Gulp, php-scoper) | Simple (npm + composer) |
| PHP Scoping | Full vendor scoping | Not needed (no conflicts) |
| Build time | ~2-3 minutes | ~10-20 seconds |
| Dependencies | Many (Webpack, Babel, Sass, etc.) | Few (archiver, fs-extra, yargs) |
| Static analysis | PHPStan (optional) | PHPStan (configured) |
| Release automation | Manual or GitHub Actions | GitHub Actions |

Convoworks GPT is intentionally kept simple since it has no UI components of its own – all admin UI is provided by Convoworks WP.

---

## Best Practices

### Code Quality

1. **Run PHPStan before committing** – Catch type errors early
2. **Write tests for utility functions** – Especially for message processing, truncation, etc.
3. **Use type hints** – PHPStan works better with explicit types
4. **Document public APIs** – Add PHPDoc blocks with `@param` and `@return` tags

### Version Management

1. **Use semantic versioning** – MAJOR.MINOR.PATCH (e.g., 0.16.1)
2. **Update CHANGELOG** – Document user-facing changes
3. **Test before tagging** – Always test the build locally first
4. **Tag format** – Use `v` prefix (e.g., `v0.16.2`)

### Dependencies

1. **Keep dependencies minimal** – Only add what you truly need
2. **Use version constraints** – `^0.22` for Convoworks, `^7.2` for PHP
3. **Document dependency changes** – Update AGENTS.md if adding major dependencies

---

For more details on the project structure and architecture, see [AGENTS.md](AGENTS.md).
