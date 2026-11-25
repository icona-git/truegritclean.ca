<?php

namespace App\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\HTTPMiddleware;

/**
 * Strips "X-Robots-Tag: noindex" from sitemap responses.
 *
 * wilr/silverstripe-googlesitemaps hardcodes the header, with no config switch, on every response it
 * serves: the index (GoogleSitemapController::index()), each child sitemap (::sitemap()) and the gzipped
 * index (::serveGzippedIndex(), which answers the .gz extension). Google Search Console reported a fleet
 * sitemap as "Couldn't fetch" for 20 days while the header was present and read it within the hour once
 * it was gone. nginx *_hide_header cannot remove it on Plesk: the generated "location /" block declares
 * its own proxy_hide_header, which makes nginx discard the inherited server-level hide list. Patching
 * vendor/ dies at the next composer update, so the fix lives here.
 *
 * The module routes everything under "sitemap.xml", and HTTPRequest::getURL() re-attaches the extension
 * it split off, so one prefix test covers the index ("sitemap.xml"), the gzipped index
 * ("sitemap.xml.gz") and every child ("sitemap.xml/sitemap/<class>/<page>"). Registered in
 * app/_config/middleware.yml. Verify after any deploy: /sitemap.xml must answer 200 with no
 * x-robots-tag header.
 */
class SitemapRobotsHeaderMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        $response = $delegate($request);

        if ($response && str_starts_with(ltrim($request->getURL(), '/'), 'sitemap.xml')) {
            $response->removeHeader('X-Robots-Tag');
        }

        return $response;
    }
}
