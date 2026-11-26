<%-- CTA band, from the Figma export (css/src/components/_booking-banner.scss). Every sentence is edited in
     Settings > Contact Banner and the phone number in Settings > Contact Info; no copy lives in this file.
     The home page adds the text line and "or call or text <phone>" under the button (index.html); other pages
     show the heading and the button (service.html, about.html). An empty heading hides the band. --%>
<% if $SiteConfig.BookingBannerTitle %>
<section class="cta-band" aria-labelledby="cta-title">
    <% if $ClassName.ShortName == 'HomePage' %>
    <div class="section-shell cta-band__inner home-cta">
        <div>
            <h2 id="cta-title">$SiteConfig.BookingBannerTitle</h2>
            <% if $SiteConfig.BookingBannerBody %><p>$SiteConfig.BookingBannerBody</p><% end_if %>
        </div>
        <div class="home-cta__action">
            <% if $SiteConfig.BookingBannerButtonText && $QuotePage %>
            <a class="button button--black" href="$QuotePage.Link">$SiteConfig.BookingBannerButtonText</a>
            <% end_if %>
            <% if $SiteConfig.BookingBannerPhoneText && $SiteConfig.Phone %>
            <small>$SiteConfig.BookingBannerPhoneText <a href="tel:$SiteConfig.PhoneLink">$SiteConfig.Phone</a></small>
            <% end_if %>
        </div>
    </div>
    <% else %>
    <div class="section-shell cta-band__inner">
        <h2 id="cta-title">$SiteConfig.BookingBannerTitle</h2>
        <% if $SiteConfig.BookingBannerButtonText && $QuotePage %>
        <a class="button button--black" href="$QuotePage.Link">$SiteConfig.BookingBannerButtonText</a>
        <% end_if %>
    </div>
    <% end_if %>
</section>
<% end_if %>
