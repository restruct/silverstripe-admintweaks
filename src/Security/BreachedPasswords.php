<?php

namespace Restruct\Silverstripe\AdminTweaks\Security;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use Throwable;

/**
 * Looks a password up in the Have I Been Pwned "Pwned Passwords" range API.
 *
 * Uses the k-anonymity model: only the first 5 characters of the password's SHA-1 hash leave the
 * server, the API answers with every hash suffix that shares that prefix, and the match is made
 * locally. Neither the password nor its full hash is ever sent.
 * https://haveibeenpwned.com/API/v3#PwnedPasswords
 *
 * The lookup fails OPEN: when the API cannot be reached in time, the password is treated as not
 * found, because an outage at HIBP must never stop people from setting a password or logging in.
 *
 * Used by BreachedPasswordValidatorExtension (refuse a breached password when it is set) and
 * BreachedPasswordMemberExtension (optionally expire a breached password at login).
 */
class BreachedPasswords
{
    use Configurable;
    use Injectable;

    /**
     * @config
     * Master switch for both the check when a password is set and the check at login.
     * On by default; passwords.yml switches it off outside live, next to the permissive dev
     * password policy (a dev admin:admin would be refused otherwise).
     */
    private static bool $enabled = true;

    /**
     * @config
     * A password counts as breached when it occurs at least this many times in the HIBP data.
     * 1 = any occurrence at all.
     */
    private static int $min_breach_count = 1;

    /**
     * @config
     * Also check the password a member logs in with, and expire it when it is breached, so the
     * member is sent to the change-password form. Off by default: it adds an outbound request to
     * every successful login, and a breached password only gets caught here if it was set before
     * the check existed (new passwords are already refused by the validator).
     */
    private static bool $expire_on_login = false;

    /**
     * @config
     */
    private static string $api_url = 'https://api.pwnedpasswords.com/range/';

    /**
     * @config
     * Kept short on purpose: this runs inside a form submission (password change, login). Guzzle
     * has no default timeout at all (see GeneralHelpers::$http_connect_timeout).
     */
    private static int $http_connect_timeout = 2;

    /**
     * @config
     */
    private static int $http_timeout = 4;

    private ?ClientInterface $httpClient = null;

    /**
     * Tests swap in a client with a Guzzle MockHandler here, so no request leaves the test run.
     */
    public function setHttpClient(?ClientInterface $client): static
    {
        $this->httpClient = $client;
        return $this;
    }

    public function getHttpClient(): ClientInterface
    {
        if (!$this->httpClient) {
            $this->httpClient = new Client([
                'connect_timeout' => static::config()->get('http_connect_timeout'),
                'timeout' => static::config()->get('http_timeout'),
            ]);
        }
        return $this->httpClient;
    }

    public function isEnabled(): bool
    {
        return (bool) static::config()->get('enabled');
    }

    /**
     * True when the password occurs in the HIBP data at least min_breach_count times.
     * False when it does not, when the check is switched off, or when the lookup failed.
     */
    public function isBreached(?string $password): bool
    {
        if (!$this->isEnabled() || $password === null || $password === '') {
            return false;
        }
        $count = $this->breachCount($password);
        return $count !== null && $count >= max(1, (int) static::config()->get('min_breach_count'));
    }

    /**
     * How often the password occurs in the HIBP data (0 = not found), or null when the lookup failed.
     */
    public function breachCount(string $password): ?int
    {
        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        try {
            $response = $this->getHttpClient()->request('GET', static::config()->get('api_url') . $prefix, [
                'headers' => [
                    # Padding makes every response roughly the same size, so the response length
                    # tells an observer nothing about the prefix. Padded rows have a count of 0.
                    'Add-Padding' => 'true',
                    'User-Agent' => 'restruct/silverstripe-admintweaks',
                ],
            ]);
            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('HTTP ' . $response->getStatusCode());
            }
            $body = (string) $response->getBody();
        } catch (Throwable $e) {
            # Fail open, but leave a trace: a check that silently never runs looks exactly like
            # a check that never finds anything.
            Injector::inst()->get(LoggerInterface::class)->warning(
                'Breached-password lookup failed, password accepted unchecked: ' . $e->getMessage()
            );
            return null;
        }

        # Each line is "SUFFIX:COUNT", suffixes in upper case.
        foreach (preg_split('/\R/', $body) as $line) {
            [$lineSuffix, $count] = array_pad(explode(':', trim($line), 2), 2, '0');
            if ($lineSuffix === $suffix) {
                return (int) $count;
            }
        }
        return 0;
    }
}
