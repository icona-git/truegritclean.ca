<!DOCTYPE html>
<html lang="$ContentLocale">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$SEOPageTitle</title>
    $MetaTags(false)
    <%-- Self-hosted fonts (css/src/_fonts.scss): preloaded because the headings and body need them first. --%>
    <link rel="preload" href="/_resources/themes/app-theme/fonts/montserrat-latin-700-900.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/_resources/themes/app-theme/fonts/inter-latin-400-600.woff2" as="font" type="font/woff2" crossorigin>
    <%-- Favicon --%>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#000000">
    <%-- Schema markup if present --%>
    <% if $SchemaJson %><script type="application/ld+json">$SchemaJson</script><% end_if %>
</head>
<body class="$ClassName.ShortName">

    <a class="skip-link" href="#main-content">Skip to content</a>

    <div class="site-max">
    $Layout
    <% include App\Includes\MobileBar %>
    </div>

<% if $HasPerm('CMS_ACCESS') %>$SilverStripeNavigator<% end_if %>
</body>
</html>
