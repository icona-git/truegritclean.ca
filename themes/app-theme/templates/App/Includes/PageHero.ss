<%-- Page hero for every layout except the home page, in the Figma markup of the page it copies:
     Variant 'service' (service.html), 'about' (about.html) or 'quote' (quote.html); TextClass is the intro's
     element name in that page ('lede' or 'text').
     Photo=1 shows the hero photo, cropped server-side with $HeroImageFill so the side set in Hero image focus stays
     in view on a phone (text never covers a face). Without it the hero is the plain charcoal treatment of the
     quote page. Button=1 adds the quote button: Settings > Contact Banner button text, linking to the quote page.
     Every word comes from the page's Hero tab: label, title (the page title when empty) and intro. --%>
<section class="{$Variant}-hero<% if not $Photo %> page-hero--plain<% end_if %>" aria-labelledby="page-title">
    <% if $Photo %>
    <picture>
        <source media="(min-width: 62rem)" srcset="$HeroImageFill(1920,820).URL">
        <source media="(min-width: 42rem)" srcset="$HeroImageFill(1400,900).URL">
        <img class="{$Variant}-hero__photo hero-focus--$HeroFocusPosition" src="$HeroImageFill(720,1150).URL" width="720" height="1150" alt="$HeroImageOrDefault.Title.ATT" fetchpriority="high">
    </picture>
    <div class="{$Variant}-hero__overlay" aria-hidden="true"></div>
    <% end_if %>
    <div class="section-shell {$Variant}-hero__inner">
        <div class="{$Variant}-hero__content">
            <% if $HeroLabel %><p class="eyebrow eyebrow--light">$HeroLabel</p><% end_if %>
            <h1 class="{$Variant}-hero__title" id="page-title"><% if $HeroTitle %>$HeroTitle<% else %>$Title<% end_if %></h1>
            <% if $HeroContent %><div class="{$Variant}-hero__{$TextClass}">$HeroContent</div><% end_if %>
            <% if $Button %><% if $QuotePage %><% if $SiteConfig.BookingBannerButtonText %>
            <a class="button button--primary" href="$QuotePage.Link">$SiteConfig.BookingBannerButtonText</a>
            <% end_if %><% end_if %><% end_if %>
        </div>
    </div>
</section>
<div class="slash-divider" aria-hidden="true"></div>
