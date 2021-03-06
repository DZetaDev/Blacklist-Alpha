[![Under Development](https://img.shields.io/badge/under-development-orange.svg)](https://github.com/DZetaDev/Blacklist-Alpha) [![license](https://img.shields.io/github/license/mashape/apistatus.svg?maxAge=2592000)]()

This is a community-contributed list of [referrer spammers](http://en.wikipedia.org/wiki/Referer_spam), Bad IPs and Bad e-mails maintained by [DZeta](https://dzeta.biz/) - WordPress Web Developer and Designer.

- [Usage](#usage)
    - [PHP](#php)
    - [Apache](#apache)
    - [Nginx](#nginx)
    - [Varnish](#varnish)
- [Contributing](#contributing)
    - [Subdomains](#subdomains)
    - [Sorting](#sorting)
- [Disclaimer](#disclaimer)
- [License](#license)

## Usage

The list is stored in this repository in:
* Spammers Domains: `spammers_domains.txt`
* Blacklist IPs: `blacklist_ips.txt`
* Blacklist e-mails `blacklist_emails.txt`

This texts files contains one host per line.

You can download the [whole folder as zip](https://github.com/DZetaDev/Blacklist-Alpha/archive/master.zip) or clone the repository using git:

```
git clone https://github.com/DZetaDev/Blacklist-Alpha.git blacklist-alpha
```

### PHP

If you are using PHP, you can also install the list through Composer:

```
composer require dzeta/blacklist-alpha "dev-master"
```

Parsing the file should be pretty easy using your favorite language. Beware that the file can contain empty lines.

Here is an example using PHP:

```php
$list_txt = file('spammers_domains.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Another alternative.
$list_json = 'spammers_domains.json';
$file = file_get_contents($list_json);
$spammers_domains = json_decode($file);

// OR
$domainsFile = 'spammers_domains.txt';
$fileList = fopen($domainsFile, 'r');
```

### Apache
 
 .htaccess is a configuration file for use on web servers running Apache. This file is usually found in the root "public_html" folder of your website.
  
  The .htaccess file uses two modules to prevent referral spam, mod_rewrite and mod_setenvif. Decide which method is most suitable with your Apache server configuration. This file is **Apache 2.4** ready, where mod_authz_host got deprecated.
 
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{HTTP_REFERER} ^http(s)?://(www.)?.*0akley\.cc.*$ [NC,OR]
    RewriteCond %{HTTP_REFERER} ^http(s)?://(www.)?.*1pamm\.ru.*$ [NC] ## [NC] On last domain.
    RewriteRule ^(.*)$ – [F,L]
</IfModule>

<IfModule mod_setenvif.c>
    SetEnvIfNoCase Referer 0akley\.cc spambot=yes
    SetEnvIfNoCase Referer 1pamm\.ru spambot=yes
</IfModule>

# Apache 2.2
<IfModule !mod_authz_core.c>
    <IfModule mod_authz_host.c>
        Order allow,deny
        Allow from all
        Deny from env=spambot
    </IfModule>
</IfModule>
# Apache 2.4
<IfModule mod_authz_core.c>
    <RequireAll>
        Require all granted
        Require not env spambot
    </RequireAll>
</IfModule>
```

### Nginx

Nginx's `server` block can be configured to check the referer and return an error:

```nginx
if ($http_referer ~ '0akley.cc') {return 403;}
if ($http_referer ~ '1pamm.ru') {return 403;}
```

When combined, list exceeds the max length for a single regex expression, so hosts must be broken up as shown above.

Here is a bash script to create an nginx conf file:
```bash
sort spammers_domains.txt | uniq | sed 's/\./\\\\./g' | while read host;
do
    echo "if (\$http_referer ~ '$host') {return 403;}" >> /etc/nginx/referer_spam.conf
done;
```

you would then `include /etc/nginx/referer_spam.conf;` inside your `server` block

Now as a daily cron job so the list stays up to date:

```bash
0 0 * * * cd /etc/nginx/blacklist-alpha/ && git pull > /dev/null && echo "" > /etc/nginx/referer_spam.conf && sort spammers_domains.txt | uniq | sed 's/\./\\\\\\\\./g' | while read host; do echo "if (\$http_referer ~ '$host') {return 403;}" >> /etc/nginx/referer_spam.conf; done; service nginx reload > /dev/null
```

Otherwise, you can use `referral_spam.conf` in `/etc/nginx`, include it globally from within `/etc/nginx/nginx.conf`:

```conf
http {
    include referral-spam.conf;
}
```

Add the following to each `/etc/nginx/site-available/your-site.conf` that needs protection:

```conf
server {
    if ($bad_referer) {
        return 403;
    }
}
```

### Varnish

Add `referral_spam.vcl` to **Varnish 4** default file: `default.vcl` by adding the following code right underneath your default backend definitions

```conf
include "referral_spam.vcl";
sub vcl_recv { call block_referral_spam; }
```

## Contributing

To add a new referrer spammer to the list, [click here to edit the spammers_domains_ext.txt file](https://github.com/DZetaDev/Blacklist-Alpha/edit/master/spammers_domains.txt) and create a pull request. Alternatively you can create a [new issue](https://github.com/DZetaDev/Blacklist-Alpha/issues/new). In your issue or pull request please **explain where the referrer domain appeared and why you think it is a spammer**. You are highly encouraged to open **one pull request per new domain**.

To add a new referrer bad IP (Malware/Botnet/Spammer) to the list, [click here to edit the blacklist_ips.txt file](https://github.com/DZetaDev/Blacklist-Alpha/edit/master/blacklist_ips.txt) and create a pull request. Alternatively you can create a [new issue](https://github.com/DZetaDev/Blacklist-Alpha/issues/new). In your issue or pull request please **explain where the referrer IP appeared and why you think it is a bad IP**. You are highly encouraged to open **one pull request per new IP**.

To add a new referrer e-mail to the list, [click here to edit blacklist_emails.txt file](https://github.com/DZetaDev/Blacklist-Alpha/edit/master/blacklist_emails.txt) and create a pull request. Alternatively you can create a [new issue](https://github.com/DZetaDev/Blacklist-Alpha/issues/new). In your issue or pull request please **explain where the referrer IP appeared and why you think it is a bad e-mail**. You are highly encouraged to open **one pull request per new e-mail**.

If you open a pull request, it is appreciated if you keep one hostname per line, keep the list ordered alphabetically, and use [Linux line endings](http://en.wikipedia.org/wiki/Newline).

Please [search](https://github.com/DZetaDev/Blacklist-Alpha/issues) if somebody already reported the host before opening a new one.

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for more details.

### Subdomains

DZeta does sub-string matching on domain names from this list, so adding `semalt.com` is enough to block all subdomain referrers too, such as `semalt.semalt.com`.

However, there are cases where you'd only want to add a subdomain but not the root domain. For example, add `referrerspammer.tumblr.com` but not `tumblr.com`, otherwise all `*.tumblr.com` sites would be affected.

### Sorting

To keep the list sorted the same way across forks it is recommended to let the computer do the sorting. The list follows the merge sort algorithm as implemented in [sort](https://en.wikipedia.org/wiki/Sort_(Unix)). You can use sort to both sort the list and filter out doubles:

```
sort -uf -o spammers_domains.txt spammers_domains.txt
```

## Disclaimer

This list of Referrer spammers is contributed by the community and is provided as is. Use at your own discretion: it may be incomplete (although we aim to keep it up to date) and it may contain outdated entries.

## License

Under the MIT license.

> The MIT License (MIT)

> Copyright (c) 2015 DZeta and other contributors. https://www.dzeta.biz

> Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

> The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

> THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
