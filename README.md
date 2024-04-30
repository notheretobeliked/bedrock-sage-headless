## About this repo

This is a repo containing a stock Bedrock installation with an also pretty stock roots/sage theme and the following plugins
- poet (for easy cpt and taxonomy regostration)
- ACF composer (to create blocks and fields easily)
- WP Graphql and required plugins to query ACF and block data

As you may guess from the above, the purpose of this system is to serve as a headless backend for a separate frontend.

If you're not me or someone I work with and are looking at this, this repo might be useful if what you want to do is to have a working SvelteKit frontend serving data from a Wordpress backend queried using GraphQL. If that's you, I imagine you must feel pretty lonely. <a href="mailto:erik@nhtbl.studio">Reach out!</a>

[![Build Status](https://img.shields.io/static/v1.svg?label=CSL&message=software%20against%20climate%20change&color=green?style=flat&logo=github)](https://img.shields.io/static/v1.svg?label=CSL&message=software%20against%20climate%20change&color=green?style=flat&logo=github)


## About Bedrock

Bedrock is a WordPress boilerplate for developers that want to manage their projects with Git and Composer. Much of the philosophy behind Bedrock is inspired by the [Twelve-Factor App](http://12factor.net/) methodology, including the [WordPress specific version](https://roots.io/twelve-factor-wordpress/).

- Better folder structure
- Dependency management with [Composer](https://getcomposer.org)
- Easy WordPress configuration with environment specific files
- Environment variables with [Dotenv](https://github.com/vlucas/phpdotenv)
- Autoloader for mu-plugins (use regular plugins as mu-plugins)
- Enhanced security (separated web root and secure passwords with [wp-password-bcrypt](https://github.com/roots/wp-password-bcrypt))


