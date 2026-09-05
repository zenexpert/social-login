# Social Login for Zen Cart

**Platform:** Zen Cart 2.x  

The Social Login plugin provides a seamless, multi-provider OAuth 2.0 authentication system for Zen Cart. Built on the industry-standard PHP League OAuth client, this encapsulated extension allows customers to register and log in using their existing Google, Facebook, or Apple identities without leaving your storefront.

## Core Architecture & Features

* **Unified Callback Routing:** Employs a single dynamic endpoint for all OAuth providers, drastically simplifying integration and allowing for rapid expansion to future providers.
* **WAF & ModSecurity Bypass:** Utilizes `form_post` response modes combined with local session recovery bounces to prevent firewall 406 errors (Remote File Inclusion false-positives) without requiring server-level rule whitelisting.
* **Cart & Session Preservation:** Actively intercepts cross-origin cookie drops triggered by modern `SameSite=Lax` browser policies. Carts built prior to logging in are safely retained and merged into the authenticated session.
* **Automated Account Provisioning:** Safely parses missing provider data (e.g., Apple's proxy emails or Facebook's phone-only accounts) and seamlessly links external social identities to existing Zen Cart customer database records.

## Installation

This plugin is built as a strict **Encapsulated Plugin** for Zen Cart 2.x. It utilizes reference-based observers and a self-contained directory structure, guaranteeing absolutely zero core file modifications.

1. Backup your database.
2. Upload the `SocialLogin` folder to your store's `zc_plugins` directory.
3. Navigate to your Zen Cart Admin and go to **Plugins > Plugin Manager**.
4. Locate **Social Login** and click **Install**. The necessary database tables and configuration keys will automatically generate.
5. Configure your desired social providers in the Admin configuration menu. 

> **Provider Setup Instructions:** Step-by-step configuration guides for Google, Facebook, and Apple can be found in the [Wiki](../../wiki).

## Sources and Credits

This plugin bundles the following open-source libraries to securely handle the OAuth 2.0 specifications:

* [PHP League OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client) (Base Library)
* [PHP League Google Provider](https://github.com/thephpleague/oauth2-google)
* [PHP League Facebook Provider](https://github.com/thephpleague/oauth2-facebook)
* [Patrick Bussmann's Apple Provider](https://github.com/patrickbussmann/oauth2-apple) (PHP League Compatible)

## Contributing

Found a bug? Feel free to submit an issue or pull request.

## 📄 License

GNU Public License V2.0
