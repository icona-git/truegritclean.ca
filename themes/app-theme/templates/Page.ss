<!DOCTYPE html>
<html lang="$ContentLocale">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$SEOPageTitle</title>
    $MetaTags(false)
    <%-- Favicon --%>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="manifest" href="/site.webmanifest">
    <%-- Schema markup if present --%>
    <% if $SchemaJson %><script type="application/ld+json">$SchemaJson</script><% end_if %>
</head>
<body class="$ClassName.ShortName">

    <a class="sr-only sr-only--focusable" href="#main-content">Skip to main content</a>

    <div class="site-max">
    $Layout
    </div>

<%-- The mobile menu is included once, by App\Includes\Header, beside its toggle button. --%>

<% if $HasPerm('CMS_ACCESS') %>$SilverStripeNavigator<% end_if %>
</body>
</html>
