<?php

namespace SiteOrigin\PageCache\Middleware;

use Closure;
use SiteOrigin\PageCache\Exchange;
use SiteOrigin\PageCache\Events\PageRefreshed;
use Illuminate\Http\Request;
use SiteOrigin\PageCache\Page;

/**
 * Cache response middleware. This
 *
 * @package SiteOrigin\PageCache\Middleware
 */
class CacheResponse
{
    private string $disk;

    /**
     * @param string|null $disk The name of the disk to use for caching
     */
    public function __construct(string $disk=null)
    {
        $this->disk = $disk ?: config('page-cache.filesystem', 'page-cache');
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Handle other middleware first.
        $response = $next($request);
        $exchange = new Exchange($request, $response);
        $page = Page::fromUrl($request->getRequestUri());

        if ($exchange->shouldCache() && $exchange->hasChanged($page)) {
            // Everything looks good. We need to cache this.
            PageRefreshed::dispatch($exchange, $page);
            $page->putFileContents($exchange->getContent());
        }

        return $response;
    }

}
