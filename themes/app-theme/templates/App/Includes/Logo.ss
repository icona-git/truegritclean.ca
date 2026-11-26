<%-- PLACEHOLDER LOGO, from the Figma export: a CSS-drawn TG monogram and wordmark (css/src/components/_header.scss).
     Replace with the client's SVG once logo concept 3 is signed off: an <img> of the horizontal lockup in
     themes/app-theme/images/, white for the header and the footer (both are dark). Variant='white' is the
     footer's all-white version. --%>
<a class="logo<% if $Variant == 'white' %> logo--white<% else %> logo--horizontal<% end_if %>" href="/" aria-label="{$SiteConfig.Title} home">
    <span class="logo__mark" aria-hidden="true">
        <span class="logo__letter logo__letter--t">T</span>
        <span class="logo__slash"></span>
        <span class="logo__letter logo__letter--g">G</span>
    </span>
    <span class="logo__wordmark" aria-hidden="true">TRUEGRIT <small>CLEAN CO.</small></span>
</a>
