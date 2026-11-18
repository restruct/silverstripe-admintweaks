<?php

namespace Restruct\Silverstripe\AdminTweaks\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\ResponseInterface;
use SilverStripe\Core\Config\Configurable;

/**
 * Validates the destination of a server-side request to a URL that may come from content,
 * configuration or a user, and sends it with the checks applied (admintweaks#139).
 *
 * What it enforces:
 * - only http and https;
 * - the host must not be `localhost` (or `*.localhost`), and every address it resolves to must be
 *   a public unicast address: loopback, private, carrier-grade NAT, link-local, multicast,
 *   documentation, benchmarking, reserved and unspecified ranges are rejected, for IPv4 and IPv6;
 * - the request is pinned to the address that was checked (cURL CURLOPT_RESOLVE), so a second DNS
 *   answer cannot swap in an internal address between the check and the connect;
 * - redirects are never followed by Guzzle itself: send() follows them one hop at a time and puts
 *   every hop through the same check.
 *
 * A project that legitimately fetches from an internal host lists it in `allowed_hosts`.
 *
 * Pinning needs Guzzle's cURL handler, which is what Guzzle uses whenever ext-curl is loaded. On
 * the stream handler the address check still runs, but the connect does its own lookup.
 */
class OutboundUrlGuard
{
    use Configurable;

    /**
     * @config
     * Destinations that may be fetched although they are not public: host names (exact match,
     * case-insensitive, e.g. `intranet.example.lan`), IP addresses or CIDR ranges
     * (e.g. `10.20.0.0/16`). Empty by default, so only public addresses are allowed.
     */
    private static array $allowed_hosts = [];

    /**
     * @config
     * Redirect hops send() follows, each one validated, before it gives up.
     */
    private static int $max_redirects = 5;

    /**
     * Ranges no outbound request may reach unless allowed in config. IPv4-mapped IPv6 addresses
     * (::ffff:a.b.c.d) are unwrapped and checked against the IPv4 list.
     */
    private const BLOCKED_RANGES = [
        // IPv4
        '0.0.0.0/8',          # "this network", includes the unspecified 0.0.0.0
        '10.0.0.0/8',         # private
        '100.64.0.0/10',      # carrier-grade NAT
        '127.0.0.0/8',        # loopback
        '169.254.0.0/16',     # link-local, includes cloud metadata endpoints
        '172.16.0.0/12',      # private
        '192.0.0.0/24',       # IETF protocol assignments
        '192.0.2.0/24',       # documentation
        '192.88.99.0/24',     # 6to4 relay anycast (deprecated)
        '192.168.0.0/16',     # private
        '198.18.0.0/15',      # benchmarking
        '198.51.100.0/24',    # documentation
        '203.0.113.0/24',     # documentation
        '224.0.0.0/4',        # multicast
        '240.0.0.0/4',        # reserved, includes broadcast 255.255.255.255
        // IPv6
        '::/8',               # unspecified, loopback, IPv4-compatible and other reserved
        '64:ff9b:1::/48',     # local-use NAT64
        '100::/64',           # discard-only
        '2001::/23',          # IETF protocol assignments (Teredo, benchmarking, ORCHID)
        '2001:db8::/32',      # documentation
        '2002::/16',          # 6to4: tunnels to an embedded IPv4 address
        'fc00::/7',           # unique local (private)
        'fe80::/10',          # link-local
        'fec0::/10',          # site-local (deprecated)
        'ff00::/8',           # multicast
    ];

    /**
     * Check a URL and return what the request must be pinned to.
     *
     * @param string $url
     * @return array{scheme: string, host: string, port: int, ip: string}
     * @throws OutboundUrlRejectedException when the URL may not be fetched
     */
    public static function validate(string $url): array
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            throw new OutboundUrlRejectedException('Outbound request blocked: not an absolute URL');
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new OutboundUrlRejectedException("Outbound request blocked: scheme '{$scheme}' is not allowed");
        }

        // parse_url() keeps the brackets of an IPv6 literal; a trailing dot is the same host.
        $host = rtrim(strtolower(trim($parts['host'], '[]')), '.');
        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);

        if ($host === '') {
            throw new OutboundUrlRejectedException('Outbound request blocked: empty host');
        }

        $hostAllowed = self::isAllowedByConfig($host);

        if (!$hostAllowed && ($host === 'localhost' || substr($host, -10) === '.localhost')) {
            throw new OutboundUrlRejectedException("Outbound request blocked: '{$host}' is a local name");
        }

        $ips = self::resolve($host);
        if (!$ips) {
            throw new OutboundUrlRejectedException("Outbound request blocked: '{$host}' does not resolve");
        }

        // Every address must pass, not just one: the connect may use any of them.
        foreach ($ips as $ip) {
            if (!$hostAllowed && !self::isAllowedByConfig($ip) && !self::isPublicIp($ip)) {
                throw new OutboundUrlRejectedException(
                    "Outbound request blocked: '{$host}' resolves to a non-public address"
                );
            }
        }

        return [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'ip' => $ips[0],
        ];
    }

    /**
     * Send a request with the destination checks applied, following redirects hop by hop.
     *
     * $options are Guzzle request options. `allow_redirects` in them is ignored: following is done
     * here, so that every hop is validated and pinned.
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @param ClientInterface|null $client a preconfigured client (tests pass one with a mock handler)
     * @return ResponseInterface
     * @throws OutboundUrlRejectedException when the URL, or a redirect target, may not be fetched
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function send(string $method, string $url, array $options = [], ?ClientInterface $client = null): ResponseInterface
    {
        $client = $client ?: new Client();
        $maxRedirects = (int) static::config()->get('max_redirects');

        for ($hop = 0; ; $hop++) {
            $target = self::validate($url);

            $hopOptions = $options;
            $hopOptions['allow_redirects'] = false;
            # Pin the connection to the address validate() checked. CURLOPT_RESOLVE entries are
            # host:port:address, an IPv6 address in brackets. An IP literal needs no pinning.
            if (defined('CURLOPT_RESOLVE') && @inet_pton($target['host']) === false) {
                $pinnedIp = strpos($target['ip'], ':') !== false ? "[{$target['ip']}]" : $target['ip'];
                $hopOptions['curl'] = ($options['curl'] ?? []) + [
                    CURLOPT_RESOLVE => ["{$target['host']}:{$target['port']}:{$pinnedIp}"],
                ];
            }

            $response = $client->request($method, $url, $hopOptions);

            $status = $response->getStatusCode();
            $location = $response->getHeaderLine('Location');
            if ($status < 300 || $status >= 400 || $location === '') {
                return $response;
            }

            if ($hop >= $maxRedirects) {
                throw new OutboundUrlRejectedException("Outbound request blocked: more than {$maxRedirects} redirects");
            }

            $url = (string) UriResolver::resolve(new Uri($url), new Uri($location));

            // Same method rules as Guzzle's own redirect middleware in non-strict mode: a 303,
            // and a 301/302 after anything but GET or HEAD, continue as a GET without a body.
            if ($status === 303 || (in_array($status, [301, 302], true) && !in_array(strtoupper($method), ['GET', 'HEAD'], true))) {
                $method = 'GET';
                unset($options['body'], $options['form_params'], $options['multipart'], $options['json']);
            }
        }
    }

    /**
     * Whether an address is a public unicast address.
     */
    public static function isPublicIp(string $ip): bool
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return false;
        }

        // IPv4-mapped IPv6 (::ffff:a.b.c.d): judge the IPv4 address it carries.
        if (strlen($packed) === 16 && substr($packed, 0, 12) === str_repeat("\0", 10) . "\xff\xff") {
            return self::isPublicIp(inet_ntop(substr($packed, 12)));
        }

        foreach (self::BLOCKED_RANGES as $range) {
            if (self::inRange($packed, $range)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether an address (packed) lies in a CIDR range. Ranges of the other family never match.
     */
    private static function inRange(string $packed, string $cidr): bool
    {
        [$subnet, $bits] = array_pad(explode('/', $cidr, 2), 2, null);
        $packedSubnet = @inet_pton($subnet);
        if ($packedSubnet === false || strlen($packedSubnet) !== strlen($packed)) {
            return false;
        }

        $bits = $bits === null ? strlen($packed) * 8 : (int) $bits;
        $fullBytes = intdiv($bits, 8);
        if (substr($packed, 0, $fullBytes) !== substr($packedSubnet, 0, $fullBytes)) {
            return false;
        }

        $remainder = $bits % 8;
        if ($remainder === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainder)) & 0xff;

        return (ord($packed[$fullBytes]) & $mask) === (ord($packedSubnet[$fullBytes]) & $mask);
    }

    /**
     * Whether a host name or address is listed in `allowed_hosts`.
     */
    private static function isAllowedByConfig(string $hostOrIp): bool
    {
        $packed = @inet_pton($hostOrIp);
        foreach ((array) static::config()->get('allowed_hosts') as $allowed) {
            $allowed = strtolower(trim((string) $allowed));
            if ($allowed === '') {
                continue;
            }
            if ($packed !== false && @inet_pton(explode('/', $allowed, 2)[0]) !== false) {
                if (self::inRange($packed, $allowed)) {
                    return true;
                }
                continue;
            }
            if ($allowed === $hostOrIp) {
                return true;
            }
        }

        return false;
    }

    /**
     * The addresses a host resolves to. An IP literal resolves to itself, without DNS.
     *
     * @return string[]
     */
    private static function resolve(string $host): array
    {
        if (@inet_pton($host) !== false) {
            return [$host];
        }

        // gethostbynamel() goes through the system resolver, so it also honours /etc/hosts and
        // the numeric forms (2130706433, 0x7f.1) the connect itself would accept; IPv4 only.
        $ips = gethostbynamel($host) ?: [];
        $aaaa = @dns_get_record($host, DNS_AAAA) ?: [];
        foreach ($aaaa as $record) {
            if (!empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }
}
