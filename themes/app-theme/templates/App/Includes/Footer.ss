<%-- Site footer, from the Figma export (css/src/components/_footer.scss). Every sentence comes from Settings:
     tagline (Main), phone, email and service area (Contact Info), exclusions line and photo credits (Footer).
     An empty field hides its line. Columns: Services lists every service page (PracticeAreaPage), Company is the
     top-level pages ticked "Show in Footer Menu" ($FooterMenu), never $MainMenu. No street address: this is a
     service-area business. --%>
<footer class="site-footer" id="footer">
    <div class="section-shell">

        <div class="site-footer__top">

            <div class="site-footer__brand">
                <% include App\Includes\Logo Variant='white' %>
                <% if $SiteConfig.Tagline %><p class="site-footer__tagline">$SiteConfig.Tagline</p><% end_if %>
            </div>

            <% if $SiteConfig.Phone || $SiteConfig.Email || $SiteConfig.ServiceArea %>
            <div class="site-footer__column">
                <h2 class="site-footer__heading">Contact</h2>
                <% if $SiteConfig.Phone || $SiteConfig.Email %>
                <address>
                    <% if $SiteConfig.Phone %><a href="tel:$SiteConfig.PhoneLink">Call or text $SiteConfig.Phone</a><% end_if %>
                    <% if $SiteConfig.Email %><a href="mailto:$SiteConfig.Email">$SiteConfig.Email</a><% end_if %>
                </address>
                <% end_if %>
                <% if $SiteConfig.ServiceArea %><p>$SiteConfig.ServiceArea</p><% end_if %>
            </div>
            <% end_if %>

            <% if $FooterPracticeAreas %>
            <nav class="site-footer__column site-footer__links" aria-label="Footer services">
                <h2 class="site-footer__heading">Services</h2>
                <% loop $FooterPracticeAreas %>
                <a href="$Link">$MenuTitle</a>
                <% end_loop %>
            </nav>
            <% end_if %>

            <% if $FooterMenu %>
            <nav class="site-footer__column site-footer__links" aria-label="Footer company">
                <h2 class="site-footer__heading">Company</h2>
                <% loop $FooterMenu %>
                <a href="$Link">$MenuTitle</a>
                <% end_loop %>
            </nav>
            <% end_if %>

        </div>

        <div class="site-footer__fine-print">
            <% if $SiteConfig.FooterExclusions %><p>$SiteConfig.FooterExclusions</p><% end_if %>
            <%-- Credentials line (content doc, footer): each part shows only once its Settings > Claims box is ticked. --%>
            <% if $SiteConfig.ClaimInsured || $SiteConfig.ClaimWorkSafeBC %>
            <p class="site-footer__credentials"><% if $SiteConfig.ClaimInsured %>Insured<% end_if %><% if $SiteConfig.ClaimInsured %><% if $SiteConfig.ClaimWorkSafeBC %> &middot; <% end_if %><% end_if %><% if $SiteConfig.ClaimWorkSafeBC %>WorkSafeBC registered<% end_if %></p>
            <% end_if %>
            <% if $SiteConfig.PhotoCredits %><p>$SiteConfig.PhotoCredits</p><% end_if %>
            <%-- Braces close the lookup: unbraced, a full stop after Title makes the parser render the SiteConfig
                 object itself (HTTP 500 on every page). --%>
            <p>&copy; $Now.Format('yyyy') {$SiteConfig.Title}</p>
        </div>

    </div>
</footer>
