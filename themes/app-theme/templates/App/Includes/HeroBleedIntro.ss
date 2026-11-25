<% if $HeroImageOrDefault %>
<style type="text/css">
    @media only screen and (min-width: 320px) {
    <% if $HeroImageMobile %>
        #hero-bg { background-image: url('{$HeroImageMobile.URL}'); }
    <% else %>
        #hero-bg { background-image: url('{$HeroImageFill(600,800).URL}'); }
    <% end_if %>
    }
    @media only screen and (min-width: 576px) {
    <% if $HeroImageMobile %>
        #hero-bg { background-image: url('{$HeroImageMobile.URL}'); }
    <% else %>
        #hero-bg { background-image: url('{$HeroImageFill(1200,900).URL}'); }
    <% end_if %>
    }
    @media only screen and (min-width: 992px) {
        #hero-bg { background-image: url('{$HeroImageOrDefault.URL}'); }
    }
</style>
<% end_if %>

<div id="hero-bg" class="hero-bleed-wrap hero-focus--{$HeroFocusPosition}">
    <section id="hero" class="hero hero--bleed">
        <div class="hero__inner wrap">
            <% if $HeroLabel %>
            <p class="hero__label">$HeroLabel</p>
            <% else_if $Parent %>
            <p class="hero__label">$Parent.MenuTitle</p>
            <% end_if %>
            <h1 class="hero__title"><% if $HeroTitle %>$HeroTitle<% else %>$Title<% end_if %></h1>
        </div>
    </section>
    <div class="hero-intro">
        <div class="wrap hero-intro__content">
            <div class="hero-intro__text">
                $HeroContent
            </div>
        </div>
    </div>
</div>
