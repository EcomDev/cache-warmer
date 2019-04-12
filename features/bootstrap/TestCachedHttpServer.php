<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

use EcomDev\CacheWarmer\PageCacheService;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory as LoopFactory;
use React\Http\Response;
use React\Socket\Server as SocketServer;

class TestCachedHttpServer
{
    const CLEAR_CACHE_SIGNAL = SIGCHLD;
    const SHUTDOWN_SIGNAL = SIGINT;

    /**
     * @var int
     */
    private $port;

    /**
     * @var PageCacheService
     */
    private $cacheService;

    public function __construct(int $port, PageCacheService $cacheService)
    {
        $this->port = $port;
        $this->cacheService = $cacheService;
    }

    public static function create(int $port): self
    {
        return new self($port, PageCacheService::create());
    }

    public function run()
    {
        $loop = LoopFactory::create();

        $socket = new SocketServer(
            sprintf('tcp://127.0.0.1:%d', $this->port),
            $loop
        );

        $server = new React\Http\Server([$this, 'handleRequest']);
        $server->listen($socket);
        $loop->addSignal(
            self::SHUTDOWN_SIGNAL,
            function () use ($loop) {
                $loop->stop();
            }
        );

        $loop->addSignal(
            self::CLEAR_CACHE_SIGNAL,
            function () {
                $this->cacheService->clearAll();
            }
        );

        $loop->run();
    }

    public function handleRequest(ServerRequestInterface $request): Response
    {
        if ($request->getMethod() === 'BAN') {
            return $this->cleanCache($request);
        }

        $requestPath = $this->extractRequestPath($request);

        if ($request->getMethod() === 'CACHED') {
            if ($this->cacheService->isCached($requestPath)) {
                return $this->createHttpResponse(200, 'Page is cached');
            }

            return $this->createHttpResponse(404, 'Page is not cached');
        }

        $responseHeaders = $request->getHeaderLine('X-Response-Headers');

        return $this->defaultPageProcessor(
            $requestPath,
            $responseHeaders ? json_decode($responseHeaders, true) : []
        );
    }

    private function pageIsCachedResponse(): Response
    {
        return $this->createHttpResponse(304, '');
    }

    private function pageIsDynamic(): Response
    {
        return $this->createHttpResponse(200, 'Dynamic Page Response');
    }

    private function createHttpResponse(int $status, string $body): Response
    {
        return new Response($status, ['Content-Type' => 'text/plain', 'Date' => '', 'X-Powered-By' => ''], $body);
    }

    private function defaultPageProcessor($requestPath, array $headers): Response
    {
        if ($this->cacheService->isCached($requestPath)) {
            return $this->pageIsCachedResponse();
        }

        $this->cacheService->visit($requestPath, $headers);

        return $this->pageIsDynamic();
    }

    private function extractRequestPath(ServerRequestInterface $request)
    {
        $uri = $request->getUri();
        return $uri->getQuery() ? sprintf('%s?%s', $uri->getPath(), $uri->getQuery()) : $uri->getPath();
    }

    private function cleanCache(ServerRequestInterface $request)
    {
        $urlRegExp = $request->getHeaderLine('X-Ban-Regexp');
        $path = $request->getHeaderLine('X-Ban-Url');

        $headerName = $request->getHeaderLine('X-Ban-Header-Name');
        $headerValue = $request->getHeaderLine('X-Ban-Header-Value');

        if ($path) {
            $this->cacheService->clearByPath($path);
        } elseif ($urlRegExp) {
            $this->cacheService->clearByRegExp($urlRegExp);
        } elseif ($headerName && $headerValue) {
            $this->cacheService->clearByHeader($headerName, $headerValue);
        }

        return $this->createHttpResponse(200, 'Cache is cleared');
    }
}
