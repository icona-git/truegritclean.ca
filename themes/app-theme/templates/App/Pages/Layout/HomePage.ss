<% include App\Includes\Header %>

<main id="main-content" tabindex="-1">

    <%-- Home hero (Figma index.html .home-hero). Label, title and intro come from the page's Hero tab; the tagline
         under the title is Settings > Main > Tagline; the first button is Settings > Contact Banner's button text
         and links to the quote page, the second calls the Settings phone number. The photo is cropped server-side
         with $HeroImageFill so the side set in Hero image focus stays in view on a phone. --%>
    <section class="home-hero" aria-labelledby="hero-title">
        <% if $HeroImage.exists %>
        <picture>
            <source media="(min-width: 62rem)" srcset="$HeroImageFill(1920,1100).URL">
            <source media="(min-width: 42rem)" srcset="$HeroImageFill(1400,1100).URL">
            <img class="home-hero__photo hero-focus--$HeroFocusPosition" src="$HeroImageFill(720,1280).URL" width="720" height="1280" alt="$HeroImage.Title.ATT" fetchpriority="high">
        </picture>
        <% end_if %>
        <div class="home-hero__overlay" aria-hidden="true"></div>
        <div class="section-shell home-hero__inner">
            <div class="home-hero__content">
                <% if $HeroLabel %><p class="eyebrow eyebrow--light">$HeroLabel</p><% end_if %>
                <h1 class="home-hero__title" id="hero-title"><% if $HeroTitle %>$HeroTitle<% else %>$Title<% end_if %></h1>
                <% if $SiteConfig.Tagline %><p class="home-hero__tagline">$SiteConfig.Tagline</p><% end_if %>
                <% if $HeroContent %><div class="home-hero__lede">$HeroContent</div><% end_if %>
                <div class="home-hero__actions">
                    <% if $QuotePage %><% if $SiteConfig.BookingBannerButtonText %>
                    <a class="button button--primary" href="$QuotePage.Link">$SiteConfig.BookingBannerButtonText</a>
                    <% end_if %><% end_if %>
                    <% if $SiteConfig.Phone %>
                    <a class="button button--secondary" href="tel:$SiteConfig.PhoneLink">Call or text $SiteConfig.Phone</a>
                    <% end_if %>
                </div>
            </div>

            <% include App\Includes\TrustRow %>
        </div>
    </section>

    <div class="slash-divider" aria-hidden="true"></div>

    $ElementalArea

    <% include App\Includes\BookingBanner %>
</main>

<% include App\Includes\Footer %>
