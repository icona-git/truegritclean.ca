<footer class="site-footer" id="footer">
    <div class="site-footer__inner wrap">

        <div class="site-footer__top">

            <a class="site-footer__logo" href="/" aria-label="$SiteConfig.Title — Home">
                <img src="/_resources/themes/app-theme/images/logo.svg"
                     alt="$SiteConfig.Title" width="240" height="44" loading="lazy">
            </a>

            <nav class="site-footer__nav" aria-label="Footer navigation">

                <% if $FooterPracticeAreas %>
                <div class="site-footer__col">
                    <span class="site-footer__col-title">Practice Areas</span>
                    <div class="site-footer__links">
                        <% loop $FooterPracticeAreas %>
                        <a href="$Link">$MenuTitle</a>
                        <% end_loop %>
                    </div>
                </div>
                <% end_if %>

                <% if $FooterMenu %>
                <div class="site-footer__col">
                    <span class="site-footer__col-title">Firm</span>
                    <div class="site-footer__links">
                        <% loop $FooterMenu %>
                        <a href="$Link">$MenuTitle</a>
                        <% end_loop %>
                    </div>
                </div>
                <% end_if %>

                <div class="site-footer__col">
                    <span class="site-footer__col-title">Contact</span>
                    <div class="site-footer__links site-footer__links--contact">
                        <% if $SiteConfig.StreetAddress %>
                        <span class="site-footer__address">
                            $SiteConfig.StreetAddress<br>
                            $SiteConfig.City, $SiteConfig.Province $SiteConfig.PostalCode
                        </span>
                        <% end_if %>
                        <% if $SiteConfig.Phone %>
                        <a href="tel:$SiteConfig.Phone">$SiteConfig.Phone</a>
                        <% end_if %>
                        <% if $SiteConfig.Email %>
                        <a href="mailto:$SiteConfig.Email">$SiteConfig.Email</a>
                        <% end_if %>
                    </div>
                </div>

            </nav>

        </div>

        <div class="site-footer__bottom">

            <% if $SocialLinks %>
            <div class="site-footer__social">
                <% loop $SocialLinks %>
                <a href="$URL" class="site-footer__social-link" aria-label="$Name" target="_blank" rel="noopener noreferrer">
                    <img src="/_resources/themes/app-theme/images/$Icon" alt="" aria-hidden="true" width="24" height="24">
                </a>
                <% end_loop %>
            </div>
            <% end_if %>

            <p class="site-footer__copy">
                <%-- Braces close the lookup. Unbraced, the full stop after Title makes the parser render
                     the SiteConfig object itself: MissingTemplateException, HTTP 500 on every page. --%>
                &copy;$Now.Format('yyyy') {$SiteConfig.Title}. All rights reserved.
                <% if $PageByURL('privacy-policy') %><% with $PageByURL('privacy-policy') %><a href="$Link">$MenuTitle</a><% end_with %><% end_if %><% if $PageByURL('privacy-policy') && $PageByURL('terms-of-service') %> | <% end_if %><% if $PageByURL('terms-of-service') %><% with $PageByURL('terms-of-service') %><a href="$Link">$MenuTitle</a><% end_with %><% end_if %>
            </p>

        </div>

    </div>
</footer>
