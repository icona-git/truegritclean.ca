<%-- "Who this is for" cards (Figma service.html .audience-grid): numbered white cards on grey, three across from
     tablet width (two, or four on wide screens, when the block has two or four items). --%>
<div class="section section--grey">
    <div class="section-shell">
        <% include App\Includes\SectionHeading Tone='dark' %>
        <% if $ShownBuckets.Count %>
        <div class="audience-grid audience-grid--{$ShownBuckets.Count}">
            <% loop $ShownBuckets %>
            <article class="audience-card">
                <span class="audience-card__number" aria-hidden="true">0$Pos</span>
                <% if $ShowTitle %><h3>$Title</h3><% end_if %>
                <% if $HTML %><div class="audience-card__body">$HTML</div><% end_if %>
            </article>
            <% end_loop %>
        </div>
        <% end_if %>
    </div>
</div>
