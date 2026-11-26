<%-- "What We Do" cards (Figma index.html .home-services): one card per item, with the line icon of the service page
     the item's link points at (App\Includes\ServiceIcon), its title, text and link. --%>
<div class="section section--light">
    <div class="section-shell">
        <% include App\Includes\SectionHeading Tone='dark' %>
        <% if $ShownBuckets.Count %>
        <div class="home-services">
            <% loop $ShownBuckets %>
            <article class="home-service">
                <% include App\Includes\ServiceIcon Key=$Link.Page.URLSegment %>
                <% if $ShowTitle %><h3 class="home-service__title">$Title</h3><% end_if %>
                $HTML
                <% if $Link %><a class="text-link home-service__link" href="$Link.URL"<% if $Link.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$Link.Title <span aria-hidden="true">&rarr;</span><span class="sr-only">: $Title</span></a><% end_if %>
            </article>
            <% end_loop %>
        </div>
        <% end_if %>
    </div>
</div>
