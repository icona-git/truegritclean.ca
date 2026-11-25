<%-- Contact banner. Every sentence is edited in Settings > Contact Banner and the contact details in
     Settings > Contact Info; an empty field hides its line. No firm's copy lives in this file. --%>
<section class="consultation-banner">
    <div class="wrap consultation-banner__inner">

        <div class="consultation-banner__content">
            <% if $SiteConfig.BookingBannerTitle %><h2 class="consultation-banner__title">$SiteConfig.BookingBannerTitle</h2><% end_if %>
            <% if $SiteConfig.BookingBannerBody %><p class="consultation-banner__body">$SiteConfig.BookingBannerBody</p><% end_if %>
            <div class="consultation-banner__contacts">
                <% if $SiteConfig.Phone %>
                <div class="consultation-banner__contact-item">
                    <img src="/_resources/themes/app-theme/images/icon-contact.svg" alt="" aria-hidden="true" width="24" height="24">
                    <div>
                        <span class="consultation-banner__contact-label">Phone</span>
                        <a href="tel:$SiteConfig.Phone" class="consultation-banner__contact-value">$SiteConfig.Phone</a>
                    </div>
                </div>
                <% end_if %>
                <% if $SiteConfig.Email %>
                <div class="consultation-banner__contact-item">
                    <img src="/_resources/themes/app-theme/images/icon-email.svg" alt="" aria-hidden="true" width="24" height="24">
                    <div>
                        <span class="consultation-banner__contact-label">Email</span>
                        <a href="mailto:$SiteConfig.Email" class="consultation-banner__contact-value">$SiteConfig.Email</a>
                    </div>
                </div>
                <% end_if %>
                <% if $SiteConfig.StreetAddress %>
                <div class="consultation-banner__contact-item">
                    <img src="/_resources/themes/app-theme/images/icon-address.svg" alt="" aria-hidden="true" width="24" height="24">
                    <div>
                        <span class="consultation-banner__contact-label">Address</span>
                        <span class="consultation-banner__contact-value">$SiteConfig.StreetAddress, $SiteConfig.City, $SiteConfig.Province $SiteConfig.PostalCode</span>
                    </div>
                </div>
                <% end_if %>
            </div>
            <% if $SiteConfig.BookingBannerButtonText && $PageByURL('contact') %>
            <a href="$PageByURL('contact').Link" class="btn btn--outline-white consultation-banner__cta">$SiteConfig.BookingBannerButtonText</a>
            <% end_if %>
            <% if $SiteConfig.BookingBannerDisclaimer %><p class="consultation-banner__disclaimer">$SiteConfig.BookingBannerDisclaimer</p><% end_if %>
        </div>

    </div>
</section>
