<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Keeps the marketing pages and the app on their own hosts once they're split
 * (APP_URL=https://app.example.com, MARKETING_URL=https://example.com). Until both are
 * set to different hosts this does nothing and both are served from one host.
 *
 * Only runs on web routes: /api/* (webhooks, public forms, mobile token login) answers on any host.
 */
class RedirectToSiteHost
{
    // Everything else in routes/web.php belongs to the app
    const MARKETING_PATHS = ['/', 'home', 'about', 'services', 'contact', 'requestDelivery', 'requestQuote'];

    public function handle(Request $request, Closure $next)
    {
        $appUrl = parse_url(config('app.url'));
        $marketingUrl = parse_url(config('branding.marketing_url'));
        $appHost = $appUrl['host'] ?? null;
        $marketingHost = $marketingUrl['host'] ?? null;

        if(!$appHost || !$marketingHost || $appHost === $marketingHost)
            return $next($request);

        $host = $request->getHost();
        $isMarketingPath = in_array($request->path(), self::MARKETING_PATHS);

        if($host === $appHost) {
            if($request->path() === '/')
                return redirect('/app');
            if($isMarketingPath)
                return $this->redirectTo($marketingUrl, $request);
            return $next($request);
        }

        // www.example.com <-> example.com: send whichever isn't configured to the one that is
        $marketingAlias = str_starts_with($marketingHost, 'www.') ? substr($marketingHost, 4) : 'www.' . $marketingHost;
        if($host === $marketingAlias)
            return $this->redirectTo($marketingUrl, $request);

        if($host === $marketingHost && !$isMarketingPath)
            return $this->redirectTo($appUrl, $request);

        return $next($request);
    }

    /**
     * Same path and query on the target host. 308 rather than 301 for non-GET requests so the method survives.
     */
    private function redirectTo(array $target, Request $request)
    {
        $root = ($target['scheme'] ?? 'https') . '://' . $target['host'] . (isset($target['port']) ? ':' . $target['port'] : '');

        return redirect()->away($root . $request->getRequestUri(), $request->isMethod('GET') || $request->isMethod('HEAD') ? 301 : 308);
    }
}
