# Contao OAuth server bundle

[![](https://img.shields.io/packagist/v/contao/oauth-server-bundle.svg?style=flat-square)](https://packagist.org/packages/contao/oauth-server-bundle)
[![](https://img.shields.io/packagist/dt/contao/oauth-server-bundle.svg?style=flat-square)](https://packagist.org/packages/contao/oauth-server-bundle)

The OAuth server bundle turns Contao into an [OAuth 2.1][1] authorization server based on
[league/oauth2-server][2]. Back end users can grant third-party clients (e.g. MCP clients) access to protected
resources, which then authenticate with bearer tokens.

**Supports the authorization code grant with PKCE and refresh tokens, client registration via
[Client ID Metadata Documents][3] and the authorization server and protected resource metadata endpoints
([RFC 8414][4], [RFC 9728][5]).**

**This bundle is experimental**. Experimental features are not covered by Contao's backwards compatibility promise
and may change or be removed in any release.

Contao is an Open Source PHP Content Management System for people who want a professional website that is easy to
maintain. Visit the [project website][6] for more information.

## Installation

```bash
composer require contao/oauth-server-bundle
```

**This repository is a READ-ONLY sub-tree split**. See https://github.com/contao/contao to create issues or submit
pull requests.

## License

Contao is licensed under the terms of the LGPLv3.

## Getting support

Visit the [support page][7] to learn about the available support options.

[1]: https://oauth.net/2.1/
[2]: https://github.com/thephpleague/oauth2-server
[3]: https://datatracker.ietf.org/doc/draft-ietf-oauth-client-id-metadata-document/
[4]: https://datatracker.ietf.org/doc/html/rfc8414
[5]: https://datatracker.ietf.org/doc/html/rfc9728
[6]: https://contao.org
[7]: https://to.contao.org/support
