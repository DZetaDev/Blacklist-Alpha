<?php
/**
 * Blacklist Alpha.
 *
 * @author    DZeta <dzetadev@outlook.com>
 * @copyright 2016 DZeta <https://dzeta.biz>
 *
 * @version   0.1.0
 */

namespace DZeta\BlacklistAlpha\Main;

use DZeta\BlacklistAlpha\SpammersDomains\Spammers_Domains;

// At start of script.
$time_start = microtime(true);

if (version_compare(PHP_VERSION, '5.4', '<')) {
    trigger_error(' requires PHP version 5.4 or higher', E_USER_ERROR);
}

define('SPAMMERS_DOMAINS_TXT', '../../spammers_domains.txt');
define('SPAMMERS_DOMAINS_JSON', '../../spammers_domains.json');

/**
 * Composer Autoloader.
 */
require_once __DIR__ . '/libs/autoload.php';

/**
 * Spammers Domains.
 *
 * @since 0.1.0
 */
new Spammers_Domains();

// Finish.
echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);
