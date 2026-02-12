## About this repo

This is a repo containing a stock Bedrock installation with an also pretty stock roots/sage theme and the following plugins
- poet (for easy cpt and taxonomy registration)
- ACF composer (to create blocks and fields easily)
- WP Graphql and required plugins to query ACF and block data

As you may guess from the above, the purpose of this system is to serve as a headless backend for a separate frontend.

If you're not me or someone I work with and are looking at this, this repo might be useful if what you want to do is to have a working SvelteKit frontend serving data from a Wordpress backend queried using GraphQL. If that's you, I imagine you must feel pretty lonely. <a href="mailto:erik@nhtbl.studio">Reach out!</a>

[![Build Status](https://img.shields.io/static/v1.svg?label=CSL&message=software%20against%20climate%20change&color=green?style=flat&logo=github)](https://img.shields.io/static/v1.svg?label=CSL&message=software%20against%20climate%20change&color=green?style=flat&logo=github)

## Stack

- [Bedrock](https://roots.io/bedrock/) - WordPress boilerplate with Composer and improved folder structure
- [Sage](https://roots.io/sage/) (headless theme) - with [Acorn](https://roots.io/acorn/) for Laravel integration
- [WPGraphQL](https://www.wpgraphql.com/) - GraphQL API for WordPress
- [ACF Pro](https://www.advancedcustomfields.com/) - Advanced Custom Fields for flexible content modeling
- [Yoast SEO](https://yoast.com/) - SEO management, exposed via GraphQL

## Included plugins

All managed via Composer:

| Plugin | Purpose |
|---|---|
| wp-graphql | GraphQL API endpoint |
| wpgraphql-acf | Expose ACF fields in GraphQL |
| wp-graphql-content-blocks | Expose Gutenberg block data via GraphQL |
| wp-graphql-yoast-seo | Expose Yoast SEO data via GraphQL |
| advanced-custom-fields-pro | Field and block management |
| poet | Easy CPT and taxonomy registration |
| simple-custom-post-order | Drag-and-drop post ordering |
| webp-uploads | Modern image format support |
| wordpress-seo (Yoast) | SEO management |

## Custom theme features

The Sage headless theme (`web/app/themes/sage-headless/`) includes:

- **ACF/GraphQL align fix** (`app/setup.php`) - Patches block `align` attributes to be queryable via GraphQL
- **Content publish webhook** (`app/filters.php`) - Sends a webhook (e.g. to Vercel) when content is published, for redeployment/ISR. Configurable via `VERCEL_WEBHOOK` env var. Only fires in staging/production.
- **Preview integration** (`app/preview-integration.php`) - Token-based preview authentication allowing the SvelteKit frontend to access draft/pending content. Includes CORS configuration, GraphQL auth hooks, and admin preview link rewriting.

## Requirements

- PHP >= 8.0
- Composer
- A local development environment (e.g. [Laravel Valet](https://laravel.com/docs/valet), [DDEV](https://ddev.com/), or [Local](https://localwp.com/))
- An ACF Pro license key (for `composer install` to pull ACF Pro)

## Installation

1. Clone the repo:
   ```
   git clone <repo-url> my-site-backend
   cd my-site-backend
   ```

2. Copy and configure environment:
   ```
   cp .env.example .env
   ```
   Edit `.env` with your local domain and database credentials. At minimum set:
   - `DB_NAME`, `DB_USER`, `DB_PASSWORD`
   - `WP_HOME` (e.g. `http://my-site.test`)
   - `FRONTEND_HOST` (e.g. `http://localhost:5173`)
   - Generate salts at https://roots.io/salts.html

3. Install dependencies:
   ```
   composer install
   ```

4. Set up WordPress:
   - Point your local dev environment at `web/` as the document root
   - Run the WordPress install at `http://your-site.test/wp/wp-admin`
   - Activate the **sage-headless** theme
   - Activate all plugins
   - Create a Primary Navigation menu under Appearance > Menus

5. Verify GraphQL is working:
   - Visit `http://your-site.test/wp/graphql` - you should get a GraphQL response

## Environment variables

| Variable | Required | Description |
|---|---|---|
| `DB_NAME` | Yes | Database name |
| `DB_USER` | Yes | Database user |
| `DB_PASSWORD` | Yes | Database password |
| `WP_HOME` | Yes | WordPress home URL (e.g. `http://my-site.test`) |
| `WP_ENV` | Yes | Environment: `development`, `staging`, or `production` |
| `FRONTEND_HOST` | Yes | SvelteKit frontend URL, used for preview links |
| `VERCEL_WEBHOOK` | No | Webhook URL to trigger frontend redeployment on publish |

## Project structure

```
web/
  app/
    mu-plugins/      # Must-use plugins (auto-loaded)
    plugins/         # Composer-managed plugins
    themes/
      sage-headless/ # The headless theme
        app/
          setup.php              # Theme setup, block align fix
          filters.php            # Publish webhook, app passwords
          preview-integration.php # Preview token auth system
        resources/
          views/    # Blade templates (minimal for headless)
          styles/   # Editor styles
  wp/                # WordPress core (Composer-managed, gitignored)
config/              # Bedrock environment configs
```

## Companion frontend

This backend is designed to work with [sveltekit-wp-bedrock-graphql](https://github.com/notheretobeliked/sveltekit-wp-bedrock-graphql), a SvelteKit frontend that consumes this GraphQL API and renders Gutenberg blocks as Svelte components.

## About Bedrock

Bedrock is a WordPress boilerplate for developers that want to manage their projects with Git and Composer. Much of the philosophy behind Bedrock is inspired by the [Twelve-Factor App](http://12factor.net/) methodology, including the [WordPress specific version](https://roots.io/twelve-factor-wordpress/).

- Better folder structure
- Dependency management with [Composer](https://getcomposer.org)
- Easy WordPress configuration with environment specific files
- Environment variables with [Dotenv](https://github.com/vlucas/phpdotenv)
- Autoloader for mu-plugins (use regular plugins as mu-plugins)
- Enhanced security (separated web root and secure passwords with [wp-password-bcrypt](https://github.com/roots/wp-password-bcrypt))
