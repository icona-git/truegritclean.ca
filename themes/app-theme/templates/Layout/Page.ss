<%-- Default layout: plain pages such as the privacy policy, and the 404 and 500 pages. Built from the Figma parts:
     breadcrumb strip, the plain charcoal hero of quote.html, then the page's text in a narrow column and any blocks. --%>
<% include App\Includes\Header %>

<main id="main-content" tabindex="-1">
    $Breadcrumbs

    <% include App\Includes\PageHero Variant='quote', TextClass='text' %>

    <% if $Content %>
    <div class="section section--light">
        <div class="section-shell section-shell--narrow typography page-text">
            $Content
        </div>
    </div>
    <% end_if %>

    $ElementalArea

    <% include App\Includes\BookingBanner %>
</main>

<% include App\Includes\Footer %>
