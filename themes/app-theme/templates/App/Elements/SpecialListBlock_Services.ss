<%-- The service pages as cards (Figma index.html .home-services): every service page (PracticeAreaPage) in site-tree
     order, with its line icon (App\Includes\ServiceIcon), title, Teaser Text and a link. The block's own text, if
     any, sits under the heading. --%>
<div class="section section--light">
    <div class="section-shell">
        <% include App\Includes\SectionHeading Tone='dark' %>
        <% if $HTML %><div class="typography section-text">$HTML</div><% end_if %>
        <% if $PracticeAreas %>
        <div class="home-services">
            <% loop $PracticeAreas %>
            <article class="home-service">
                <% include App\Includes\ServiceIcon Key=$URLSegment %>
                <h3 class="home-service__title">$Title</h3>
                $TeaserContent
                <a class="text-link home-service__link" href="$Link">Learn more <span aria-hidden="true">&rarr;</span><span class="sr-only">: $Title</span></a>
            </article>
            <% end_loop %>
        </div>
        <% end_if %>
    </div>
</div>
