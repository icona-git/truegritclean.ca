<% if $HeroLayout == 'bleed-intro' %>
<% include App\Includes\HeroBleedIntro %>
<% else %>
<% if $HeroImageOrDefault %>
<style type="text/css">
    @media only screen and (min-width: 320px) {
    <% if $HeroImageMobile %>
        #hero { background-image: url('{$HeroImageMobile.URL}'); }
    <% else %>
        #hero { background-image: url('{$HeroImageFill(400,300).URL}'); }
    <% end_if %>
    }
    @media only screen and (min-width: 576px) {
    <% if $HeroImageMobile %>
        #hero { background-image: url('{$HeroImageMobile.URL}'); }
    <% else %>
        #hero { background-image: url('{$HeroImageFill(800,400).URL}'); }
    <% end_if %>
    }
    @media only screen and (min-width: 992px) {
        #hero { background-image: url('{$HeroImageOrDefault.URL}'); }
    }
</style>
<% end_if %>
<%-- The scrim darkens a photo so the headline stays readable; with no photo the hero is already the dark
     background colour, and a scrim would only make a design's plain background darker than specified. --%>
<section id="hero" class="hero<% if $HeroImageOrDefault %> hero--scrim<% end_if %> hero-focus--{$HeroFocusPosition}">
    <div class="hero__inner wrap">
        <% if $HeroLabel %>
        <p class="hero__label">$HeroLabel</p>
        <% else_if $Parent %>
        <p class="hero__label">$Parent.MenuTitle</p>
        <% end_if %>
        <h1 class="hero__title"><% if $HeroTitle %>$HeroTitle<% else %>$Title<% end_if %></h1>
        <% if $HeroContent %>
        $HeroContent
        <% end_if %>
    </div>
</section>
<% end_if %>
