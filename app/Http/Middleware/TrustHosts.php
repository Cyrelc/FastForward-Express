<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;
use Illuminate\Http\Request;

/**
 * Rejects requests whose Host header is not one of ours (Laravel answers 400 "Bad hostname"; it was 404 before Laravel 11).
 *
 * Without it, links the app builds from the request - password reset emails above all - point at
 * whatever host the caller sent, so a forged Host header turns a reset email into a token-stealing
 * link. Apache's catch-all vhost is the first line of defence; this is the second.
 */
class TrustHosts extends Middleware {
    public function hosts() {
        $domains = array_merge(
            [
                parse_url(config('app.url'), PHP_URL_HOST),
                parse_url(config('branding.marketing_url'), PHP_URL_HOST),
            ],
            config('app.trusted_hosts', [])
        );

        // Each domain plus its subdomains (www., app.) - we control their DNS, so they can't be forged
        return array_values(array_unique(array_map(
            fn($domain) => '^(.+\.)?' . preg_quote(preg_replace('/^www\./', '', strtolower($domain))) . '$',
            array_filter($domains)
        )));
    }

    public function handle(Request $request, $next) {
        if($this->shouldSpecifyTrustedHosts()) {
            Request::setTrustedHosts(array_filter($this->hosts()));
            // Symfony only checks the Host when something reads it - often mid-render, surfacing as a 500.
            // Read it now so a forged Host is rejected up front (Laravel renders that as a 400).
            $request->getHost();
        }

        return $next($request);
    }

    /**
     * Production only. Dev runs on localhost while its APP_URL names the real domain, and tests run
     * with APP_ENV=development, so enforcing everywhere would lock both out.
     */
    protected function shouldSpecifyTrustedHosts() {
        return $this->app->environment(['prod', 'production']);
    }
}
