# Joomla-NXD-E-Mail-Authentication-Plugin

This plugin allows your users to authenticate themselves on your Joomla! website with their email address (used during registration). Instead of the username, visitors simply enter the mail address of the account.

The core Joomla authentication plugin keeps handling usernames, so both continue to work side by side. Logging into the administrator with an e-mail address is off by default and can be enabled in the plugin options.

Extension on JED: https://extensions.joomla.org/extension/authentication-e-mail-by-nxd/

## Requirements

| | |
|---|---|
| Joomla! | 5.0 or later |
| PHP | 8.1 or later |

Version 1.x supports Joomla! 4.4; use it if you are still on Joomla 4.

### Please Note:
The plugin does not adapt the language files. This means that if you want the login window to say "user name or e-mail address" instead of "user name", you have to do this via Joomla's integrated language overrides.

### Security considerations

E-mail addresses are usually easier to discover than usernames, so allowing them as login identifiers slightly widens the surface for credential stuffing and brute force attempts. Joomla does not rate limit logins out of the box — enabling multi-factor authentication is recommended, especially if you turn on the backend login option.

If several accounts share the same e-mail address (which imports, LDAP synchronisation or third party registration extensions can produce), the plugin refuses the login instead of guessing which account was meant, and writes a warning to the `plg_authentication_nxdemailauth` log category.

## Changelog

### 2.0
- Rebuilt plugin from scratch for the Joomla 4+ extension layout (`plugin/services/provider.php`, namespaced `src`)
- Joomla 5 / 6 compatible; minimum requirement raised to Joomla 5.0 and PHP 8.1
- Refuses to authenticate when more than one account shares an e-mail address
- E-mail addresses are matched case insensitively on every supported database, not just MySQL
- Input that is not an e-mail address is left to the core authentication plugin
- Added German translation

### 1.0
- Initial Release
