<%-- "The standard we hold" (Figma about.html .standards-grid): numbered statements on charcoal, two across from
     tablet width. Also the hoarding page's "How we work". --%>
<div class="section section--dark">
    <div class="section-shell">
        <% include App\Includes\SectionHeading Tone='light' %>
        <% if $ShownBuckets.Count %>
        <div class="standards-grid">
            <% loop $ShownBuckets %>
            <article class="standard-item">
                <span class="standard-item__number" aria-hidden="true">0$Pos</span>
                <div>
                    <% if $ShowTitle %><h3>$Title</h3><% end_if %>
                    $HTML
                </div>
            </article>
            <% end_loop %>
        </div>
        <% end_if %>
    </div>
</div>
