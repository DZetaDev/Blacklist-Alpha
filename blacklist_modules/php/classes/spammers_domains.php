<?php
/**
 * Generator for Spammers Domains.
 *  - Apache (.htaccess)
 *  - Nginx (referral_spam.conf)
 *  - Varnish (referral_spam.vcl)
 *  - Google Analytics Exclude Regex (google-exclude-regex.txt)
 *
 * @version   0.1.0
 */

namespace DZeta\BlacklistAlpha\SpammersDomains;

use Mso\IdnaConvert\IdnaConvert;

/**
 * Class Spammers_Domains
 * @package DZeta\BlacklistAlpha\SpammersDomains
 */
class Spammers_Domains
{

    private $projectUrl = 'https://github.com/DZetaDev/Blacklist-Alpha';

    /**
     * Class Spammers_Domains constructor.
     */
    public function __construct()
    {
        date_default_timezone_set('UTC');
        $date = date('Y-m-d H:i:s');

        $lines = $this->domainWorker();

        $this->createApache($date, $lines);
        $this->createNginx($date, $lines);
        $this->createVarnish($date, $lines);
        $this->createGoogleExclude($lines);
    }

    /**
     * Domain Worker.
     * @return array
     */
    public function domainWorker()
    {
        $domainsFile = SPAMMERS_DOMAINS_TXT;

        $handle = fopen($domainsFile, 'r');
        if (!$handle) {
            throw new \RuntimeException('Error opening file ' . $domainsFile);
        }
        $lines = array();
        while (($line = fgets($handle)) !== false) {
            $line = trim(preg_replace('/\s\s+/', ' ', $line));

            // Convert russian domains.
            if (preg_match('/[А-Яа-яЁё]/u', $line)) {
                $idn = new IdnaConvert();

                $line = $idn->encode($line);
            }

            if (empty($line)) {
                continue;
            }
            $lines[] = $line;
        }
        fclose($handle);
        $uniqueLines = array_unique($lines, SORT_STRING);
        sort($uniqueLines, SORT_STRING);
        if (is_writable($domainsFile)) {
            file_put_contents($domainsFile, implode("\n", $uniqueLines));
        } else {
            trigger_error('Permission denied');
        }

        return $lines;
    }

    /**
     * Create the list of Apache.
     * @param string $date
     * @param array $lines
     */
    public function createApache($date, array $lines)
    {
        $file = __DIR__ . '/../../../.htaccess';
        $data = "# " . $this->projectUrl . "\n# Spammers Domains \n# Updated " . $date . "\n\n<IfModule mod_rewrite.c>\n\nRewriteEngine On\n\n";
        foreach ($lines as $line) {
            if ($line === end($lines)) {
                $data .= 'RewriteCond %{HTTP_REFERER} ^http(s)?://(www.)?.*' . preg_quote($line) . ".*$ [NC]\n";
                break;
            }

            $data .= 'RewriteCond %{HTTP_REFERER} ^http(s)?://(www.)?.*' . preg_quote($line) . ".*$ [NC,OR]\n";
        }

        $data .= "RewriteRule ^(.*)$ – [F,L]\n\n</IfModule>\n\n<IfModule mod_setenvif.c>\n\n";
        foreach ($lines as $line) {
            $data .= "SetEnvIfNoCase Referer " . preg_quote($line) . " spambot=yes\n";
        }
        $data .= "\n</IfModule>\n\n# Apache 2.2\n<IfModule !mod_authz_core.c>\n\t<IfModule mod_authz_host.c>\n\t\tOrder allow,deny\n\t\tAllow from all\n\t\tDeny from env=spambot\n\t</IfModule>\n</IfModule>\n# Apache 2.4\n<IfModule mod_authz_core.c>\n\t<RequireAll>\n\t\tRequire all granted\n\t\tRequire not env spambot\n\t</RequireAll>\n</IfModule>";

        if (file_exists($file) && is_readable($file) && is_writable($file)) {
            file_put_contents($file, $data);
            if (!chmod($file, 0644)) {
                trigger_error("Couldn't not set .htaccess permissions to 644");
            }

        } else {
            trigger_error('Permission denied');
        }
    }

    /**
     * Create the config of Ngnix.
     * @param string $date
     * @param array $lines
     */
    public function createNginx($date, array $lines)
    {
        $file = __DIR__ . '/../../../referral_spam.conf';
        $data = "# " . $this->projectUrl . "\n# Updated " . $date . "\n#\n# /etc/nginx/referral-spam.conf\n#\n# With referral-spam.conf in /etc/nginx, include it globally from within /etc/nginx/nginx.conf:\n#\n#     include referral-spam.conf;\n#\n# Add the following to each /etc/nginx/site-available/your-site.conf that needs protection:\n#\n#      server {\n#        if (\$bad_referer) {\n#          return 403;\n#        }\n#      }\n#\nmap \$http_referer \$bad_referer {\n\tdefault 0;\n\n";
        foreach ($lines as $line) {
            $data .= "\t\"~*" . preg_quote($line) . "\" 1;\n";
        }
        $data .= "\n}";

        if (file_exists($file) && is_readable($file) && is_writable($file)) {
            file_put_contents($file, $data);
            if (!chmod($file, 0644)) {
                trigger_error("Couldn't not set referral-spam.conf permissions to 644");
            }
        } else {
            trigger_error('Permission denied');
        }
    }

    /**
     * Create the list of Varnish.
     * @param string $date
     * @param array $lines
     */
    public function createVarnish($date, array $lines)
    {
        $file = __DIR__ . '/../../../referral_spam.vcl';

        $data = "# " . $this->projectUrl . "\n# Updated " . $date . "\nsub block_referral_spam {\n\tif (\n";
        foreach ($lines as $line) {
            if ($line === end($lines)) {
                $data .= "\t\treq.http.Referer ~ \"(?i)" . preg_quote($line) . "\"\n";
                break;
            }

            $data .= "\t\treq.http.Referer ~ \"(?i)" . preg_quote($line) . "\" ||\n";
        }

        $data .= "\t) {\n\t\t\treturn (synth(403, \"Forbidden\"));\n\t}\n}";

        if (file_exists($file) && is_readable($file) && is_writable($file)) {
            file_put_contents($file, $data);
            if (!chmod($file, 0644)) {
                trigger_error("Couldn't not set referral-spam.vcl permissions to 644");
            }
        } else {
            trigger_error('Permission denied');
        }
    }

    /**
     * Create the list of Google exclude domains.
     * @param array $lines
     */
    public function createGoogleExclude(array $lines)
    {
        $file = __DIR__ . '/../../../google-exclude-regex.txt';
        $reqexLines = array();
        foreach ($lines as $line) {
            $reqexLines[] = preg_quote($line);
        }
        $data = implode('|', $reqexLines);

        if (file_exists($file) && is_readable($file) && is_writable($file)) {
            file_put_contents($file, $data);
        } else {
            trigger_error('Permission denied');
        }

    }
}
