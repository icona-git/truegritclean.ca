<%-- Service page (Figma service.html): breadcrumb strip, service hero with the quote button, then the blocks and the
     CTA band. The hero shows a photo only when one is set on this page itself, never the parent's: the hoarding
     page carries no photo by design, and a page without one gets the plain charcoal hero. --%>
<% include App\Includes\Header %>

<main id="main-content" tabindex="-1">
    $Breadcrumbs

    <% if $HeroImage.exists %>
    <% include App\Includes\PageHero Variant='service', TextClass='lede', Photo='1', Button='1' %>
    <% else %>
    <% include App\Includes\PageHero Variant='service', TextClass='lede', Button='1' %>
    <% end_if %>

    <% if $Content %>
    <div class="section section--light">
        <div class="section-shell section-shell--narrow typography">$Content</div>
    </div>
    <% end_if %>

    $ElementalArea

    <% include App\Includes\BookingBanner %>
</main>

<% include App\Includes\Footer %>
