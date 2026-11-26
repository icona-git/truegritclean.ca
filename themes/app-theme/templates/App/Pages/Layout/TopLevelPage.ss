<%-- Top-level page such as About and Services (Figma about.html): breadcrumb strip, about hero (photo when the page
     has one, plain charcoal otherwise), then the blocks and the CTA band. --%>
<% include App\Includes\Header %>

<main id="main-content" tabindex="-1">
    $Breadcrumbs

    <% if $HeroImage.exists %>
    <% include App\Includes\PageHero Variant='about', TextClass='text', Photo='1' %>
    <% else %>
    <% include App\Includes\PageHero Variant='about', TextClass='text' %>
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
