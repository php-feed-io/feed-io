<?php

declare(strict_types=1);

namespace FeedIo\Adapter\Http;

use DateTime;
use FeedIo\Adapter\NotFoundException;
use FeedIo\Adapter\ServerErrorException;
use Nyholm\Psr7\Response as PsrResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Http\Message\RequestInterface;

class ClientTest extends TestCase
{
    private PsrClientInterface $psrClient;
    private Client $client;

    protected function setUp(): void
    {
        $this->psrClient = $this->createMock(PsrClientInterface::class);
        $this->client = new Client($this->psrClient);
    }

    public function testGetResponseWithSuccess(): void
    {
        $psrResponse = new PsrResponse(200, [], 'feed content');

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($psrResponse);

        $response = $this->client->getResponse('https://example.com/feed.xml');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('feed content', $response->getBody());
    }

    public function testGetResponseWith304NotModified(): void
    {
        $modifiedSince = new DateTime('2025-01-01');
        
        // HEAD request returns 304
        $headResponse = new PsrResponse(304);

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($modifiedSince) {
                return $request->getMethod() === 'HEAD' 
                    && $request->hasHeader('If-Modified-Since')
                    && $request->getHeaderLine('If-Modified-Since') === $modifiedSince->format(DateTime::RFC2822);
            }))
            ->willReturn($headResponse);

        $response = $this->client->getResponse('https://example.com/feed.xml', $modifiedSince);

        $this->assertEquals(304, $response->getStatusCode());
    }

    public function testGetResponseWithModifiedSinceButFeedChanged(): void
    {
        $modifiedSince = new DateTime('2025-01-01');
        
        // HEAD request returns 200 (modified)
        $headResponse = new PsrResponse(200);
        // GET request also returns 200 with content
        $getResponse = new PsrResponse(200, [], 'new feed content');

        $this->psrClient
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls($headResponse, $getResponse);

        $response = $this->client->getResponse('https://example.com/feed.xml', $modifiedSince);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('new feed content', $response->getBody());
    }

    public function testGetResponseThrowsNotFoundOn404(): void
    {
        $psrResponse = new PsrResponse(404);

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($psrResponse);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('not found');

        $this->client->getResponse('https://example.com/feed.xml');
    }

    public function testGetResponseThrowsServerErrorOnServerError(): void
    {
        $psrResponse = new PsrResponse(500);

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($psrResponse);

        $this->expectException(ServerErrorException::class);

        $this->client->getResponse('https://example.com/feed.xml');
    }

    /**
     * @dataProvider redirectStatusCodeProvider
     */
    public function testGetResponseFollowsRedirects(int $statusCode): void
    {
        $redirectResponse = new PsrResponse($statusCode, ['Location' => 'https://example.com/new-feed.xml']);
        $finalResponse = new PsrResponse(200, [], 'redirected feed content');

        $this->psrClient
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls($redirectResponse, $finalResponse);

        $response = $this->client->getResponse('https://example.com/old-feed.xml');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('redirected feed content', $response->getBody());
    }

    public static function redirectStatusCodeProvider(): array
    {
        return [
            '301 Moved Permanently' => [301],
            '302 Found' => [302],
            '303 See Other' => [303],
            '307 Temporary Redirect' => [307],
            '308 Permanent Redirect' => [308],
        ];
    }

    public function testGetResponseFollowsMultipleRedirects(): void
    {
        $redirect1 = new PsrResponse(301, ['Location' => 'https://example.com/redirect2.xml']);
        $redirect2 = new PsrResponse(302, ['Location' => 'https://example.com/final.xml']);
        $finalResponse = new PsrResponse(200, [], 'final content');

        $this->psrClient
            ->expects($this->exactly(3))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls($redirect1, $redirect2, $finalResponse);

        $response = $this->client->getResponse('https://example.com/start.xml');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('final content', $response->getBody());
    }

    public function testGetResponsePreservesModifiedSinceThroughRedirects(): void
    {
        $modifiedSince = new DateTime('2025-01-01');
        
        // HEAD request with If-Modified-Since returns redirect
        $headRedirect = new PsrResponse(301, ['Location' => 'https://example.com/new-location.xml']);
        // HEAD request to new location returns 200
        $headResponse = new PsrResponse(200);
        // GET request to new location
        $getResponse = new PsrResponse(200, [], 'content');

        $this->psrClient
            ->expects($this->exactly(3))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls($headRedirect, $headResponse, $getResponse);

        $response = $this->client->getResponse('https://example.com/old-location.xml', $modifiedSince);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetResponseWithEmptyLocationHeaderThrowsException(): void
    {
        $redirectResponse = new PsrResponse(301, ['Location' => '']);

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($redirectResponse);

        $this->expectException(ServerErrorException::class);
        
        $this->client->getResponse('https://example.com/feed.xml');
    }

    public function testResponseDurationIsTracked(): void
    {
        $psrResponse = new PsrResponse(200, [], 'content');

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($psrResponse);

        $response = $this->client->getResponse('https://example.com/feed.xml');

        $this->assertIsFloat($response->getDuration());
        $this->assertGreaterThanOrEqual(0, $response->getDuration());
    }

    public function testResponseDurationIsTrackedOnError(): void
    {
        $psrResponse = new PsrResponse(404);

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($psrResponse);

        try {
            $this->client->getResponse('https://example.com/feed.xml');
            $this->fail('Expected NotFoundException to be thrown');
        } catch (NotFoundException $e) {
            $this->assertIsFloat($e->getDuration());
            $this->assertGreaterThanOrEqual(0, $e->getDuration());
        }
    }
}
