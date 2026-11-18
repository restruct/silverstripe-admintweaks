<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Restruct\Silverstripe\AdminTweaks\Helpers\GeneralHelpers;
use Restruct\Silverstripe\AdminTweaks\Helpers\OutboundUrlGuard;
use Restruct\Silverstripe\AdminTweaks\Helpers\OutboundUrlRejectedException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * Destination checks for server-side fetches (admintweaks#139).
 *
 * Every URL here uses an IP literal or a name that is rejected before any lookup, so the tests
 * need no DNS and never reach the network: redirects are driven by a Guzzle mock handler.
 */
class OutboundUrlGuardTest extends SapphireTest
{
    protected $usesDatabase = false;

    /**
     * Assert that validate() rejects $url, and say which URL slipped through if it does not.
     */
    private function assertRejected(string $url): void
    {
        $rejected = false;
        try {
            OutboundUrlGuard::validate($url);
        } catch (OutboundUrlRejectedException $e) {
            $rejected = true;
        }

        $this->assertTrue($rejected, "Expected {$url} to be rejected");
    }

    public static function provideRejectedUrls(): array
    {
        return [
            'ftp scheme' => ['ftp://93.184.216.34/file.txt'],
            'file scheme' => ['file:///etc/passwd'],
            'gopher scheme' => ['gopher://93.184.216.34/'],
            'relative url' => ['/just/a/path'],
            'localhost' => ['http://localhost/'],
            'localhost upper case, trailing dot' => ['http://LOCALHOST./'],
            'subdomain of localhost' => ['http://api.localhost:8080/'],
            'ipv4 loopback' => ['http://127.0.0.1/'],
            'ipv4 loopback, other address' => ['http://127.10.20.30:9000/'],
            'unspecified' => ['http://0.0.0.0/'],
            'private 10/8' => ['http://10.1.2.3/'],
            'private 172.16/12, low end' => ['http://172.16.0.1/'],
            'private 172.16/12, high end' => ['http://172.31.255.254/'],
            'private 192.168/16' => ['https://192.168.1.1/'],
            'carrier-grade nat' => ['http://100.64.0.1/'],
            'link-local, metadata endpoint' => ['http://169.254.169.254/latest/meta-data/'],
            'documentation range' => ['http://192.0.2.10/'],
            'benchmarking range' => ['http://198.18.0.1/'],
            'multicast' => ['http://224.0.0.1/'],
            'broadcast' => ['http://255.255.255.255/'],
            'ipv6 loopback' => ['http://[::1]/'],
            'ipv6 unspecified' => ['http://[::]/'],
            'ipv6 link-local' => ['http://[fe80::1]/'],
            'ipv6 unique local' => ['http://[fd12:3456:789a::1]/'],
            'ipv6 multicast' => ['http://[ff02::1]/'],
            'ipv6 documentation' => ['http://[2001:db8::1]/'],
            'ipv4-mapped loopback' => ['http://[::ffff:127.0.0.1]/'],
            'ipv4-mapped private' => ['http://[::ffff:10.0.0.1]/'],
            '6to4' => ['http://[2002:7f00:1::1]/'],
        ];
    }

    /**
     * @dataProvider provideRejectedUrls
     */
    public function testRejectsNonPublicDestinations(string $url): void
    {
        $this->assertRejected($url);
    }

    public function testAcceptsPublicAddresses(): void
    {
        $ipv4 = OutboundUrlGuard::validate('https://93.184.216.34/path?q=1');
        $this->assertSame('93.184.216.34', $ipv4['ip']);
        $this->assertSame(443, $ipv4['port']);

        $ipv6 = OutboundUrlGuard::validate('http://[2606:4700:4700::1111]:8080/');
        $this->assertSame('2606:4700:4700::1111', $ipv6['ip']);
        $this->assertSame(8080, $ipv6['port']);
    }

    public function testRangeBoundariesAreExact(): void
    {
        // Just outside 172.16.0.0/12 and 100.64.0.0/10: public, must not be caught by a sloppy mask.
        $this->assertTrue(OutboundUrlGuard::isPublicIp('172.15.255.255'));
        $this->assertTrue(OutboundUrlGuard::isPublicIp('172.32.0.0'));
        $this->assertTrue(OutboundUrlGuard::isPublicIp('100.63.255.255'));
        $this->assertTrue(OutboundUrlGuard::isPublicIp('100.128.0.0'));
        $this->assertFalse(OutboundUrlGuard::isPublicIp('not-an-ip'));
    }

    public function testAllowlistAdmitsConfiguredRangesAndNamesOnly(): void
    {
        Config::modify()->set(OutboundUrlGuard::class, 'allowed_hosts', ['10.20.0.0/16', '127.0.0.1', 'localhost']);

        $this->assertSame('10.20.3.4', OutboundUrlGuard::validate('http://10.20.3.4/')['ip']);
        $this->assertSame('127.0.0.1', OutboundUrlGuard::validate('http://127.0.0.1:8080/')['ip']);

        // Neighbours of an allowed range stay blocked.
        $this->assertRejected('http://10.21.0.1/');
        $this->assertRejected('http://127.0.0.2/');

        // The allowlist widens destinations, never schemes.
        $this->assertRejected('ftp://10.20.3.4/');
    }

    // ------------------------------------------------------------- send()

    /**
     * A client whose handler answers from $responses and records every request it is sent.
     */
    private function mockClient(array $responses, array &$history): Client
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));

        return new Client(['handler' => $stack]);
    }

    public function testSendRejectsARedirectToAPrivateAddress(): void
    {
        $history = [];
        $client = $this->mockClient([
            new Response(302, ['Location' => 'http://10.0.0.1/admin']),
            new Response(200, [], 'must never be requested'),
        ], $history);

        $rejected = false;
        try {
            OutboundUrlGuard::send('GET', 'http://93.184.216.34/start', [], $client);
        } catch (OutboundUrlRejectedException $e) {
            $rejected = true;
        }

        $this->assertTrue($rejected, 'A redirect to a private address must be rejected');
        $this->assertCount(1, $history, 'The private redirect target must never be requested');
    }

    public function testSendFollowsAValidatedRedirectWithoutGuzzleFollowing(): void
    {
        $history = [];
        $client = $this->mockClient([
            new Response(301, ['Location' => '/moved']),
            new Response(200, [], 'arrived'),
        ], $history);

        $response = OutboundUrlGuard::send('GET', 'http://93.184.216.34/start', [], $client);

        $this->assertSame('arrived', (string) $response->getBody());
        $this->assertCount(2, $history);
        $this->assertSame('http://93.184.216.34/moved', (string) $history[1]['request']->getUri());
        foreach ($history as $transaction) {
            $this->assertFalse(
                $transaction['options']['allow_redirects'],
                'Guzzle must not follow redirects itself, or a hop would skip the check'
            );
        }
    }

    public function testSendGivesUpAfterMaxRedirects(): void
    {
        Config::modify()->set(OutboundUrlGuard::class, 'max_redirects', 2);

        $history = [];
        $client = $this->mockClient(array_fill(0, 5, new Response(302, ['Location' => '/loop'])), $history);

        $rejected = false;
        try {
            OutboundUrlGuard::send('GET', 'http://93.184.216.34/loop', [], $client);
        } catch (OutboundUrlRejectedException $e) {
            $rejected = true;
        }

        $this->assertTrue($rejected);
        $this->assertCount(3, $history, 'The first request plus two redirects, then stop');
    }

    // ------------------------------------------------------- the helpers use it

    public function testPerformHttpRequestRejectsAPrivateDestination(): void
    {
        $this->expectException(OutboundUrlRejectedException::class);

        GeneralHelpers::perform_http_request('http://169.254.169.254/latest/meta-data/');
    }
}
