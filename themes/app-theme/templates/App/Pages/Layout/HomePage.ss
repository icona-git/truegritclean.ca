<% include App\Includes\Header %>

<% if $HeroImageOrDefault %>
<style type="text/css">
    @media only screen and (min-width: 320px) {
    <% if $HeroImageMobile %>
        #hero .hero__media { background-image: url('{$HeroImageMobile.URL}'); }
    <% else %>
        #hero .hero__media { background-image: url('{$HeroImageFill(600,800).URL}'); }
    <% end_if %>
    }
    @media only screen and (min-width: 1024px) {
        #hero .hero__media { background-image: url('{$HeroImageOrDefault.URL}'); }
    }
</style>
<% end_if %>

<section id="hero" class="hero hero--home">
    <div class="hero__media hero-focus--{$HeroFocusPosition}"></div>
    <div class="hero__inner wrap">

        <% if $HeroLabel %>
        <p class="hero__label">$HeroLabel</p>
        <% else_if $Parent %>
        <p class="hero__label">$Parent.MenuTitle</p>
        <% end_if %>
        <h1 class="hero__title"><% if $HeroTitle %>$HeroTitle<% else %>$Title<% end_if %></h1>
        <div class="hero__content typography">$HeroContent</div>
        
    </div>
</section>

<main id="main-content" tabindex="-1">
    $ElementalArea
</main>

<% include App\Includes\BookingBanner %>
<% include App\Includes\Footer %>
