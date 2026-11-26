<%-- The company story (Figma about.html .about-story): each item is a column with its title as the heading and its
     text below; two columns from tablet width. The block title shows only when "Show title" is ticked. --%>
<div class="section section--light">
    <div class="section-shell">
        <% include App\Includes\SectionHeading Tone='dark' %>
        <% if $ShownBuckets.Count %>
        <div class="about-story">
            <% loop $ShownBuckets %>
            <article class="about-story__block">
                <% if $ShowTitle %><h2>$Title</h2><% end_if %>
                <% if $HTML %><div class="about-story__text">$HTML</div><% end_if %>
            </article>
            <% end_loop %>
        </div>
        <% end_if %>
    </div>
</div>
