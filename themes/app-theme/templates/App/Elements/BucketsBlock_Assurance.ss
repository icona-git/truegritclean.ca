<%-- The reassurance panel beside the quote form (Figma quote.html .quote-assurance): the block title, one ticked line
     per item, then the block's text ("Prefer to talk?") over the phone number and email from Settings > Contact
     Info. Phase 5 places it beside the form; until then it renders where the block sits. --%>
<aside class="quote-assurance"<% if $ShowTitle %><% if $Title %> aria-labelledby="assurance-$ID"<% end_if %><% end_if %>>
    <% if $ShowTitle %><% if $Title %><h2 id="assurance-$ID">$Title</h2><% end_if %><% end_if %>
    <% if $ShownBuckets.Count %>
    <ul class="quote-assurance__list">
        <% loop $ShownBuckets %>
        <li><span aria-hidden="true">&#10003;</span>$Title</li>
        <% end_loop %>
    </ul>
    <% end_if %>
    <div class="quote-assurance__contact">
        $HTML
        <% if $SiteConfig.Phone %><a class="quote-assurance__phone" href="tel:$SiteConfig.PhoneLink">$SiteConfig.Phone</a><% end_if %>
        <% if $SiteConfig.Email %><a class="quote-assurance__email" href="mailto:$SiteConfig.Email">$SiteConfig.Email</a><% end_if %>
    </div>
</aside>
