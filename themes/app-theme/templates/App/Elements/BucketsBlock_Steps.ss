<%-- "How it works" (Figma index.html .home-steps): numbered steps on grey, each with its title and text. --%>
<div class="section section--grey">
    <div class="section-shell">
        <% include App\Includes\SectionHeading Tone='dark' %>
        <% if $ShownBuckets.Count %>
        <ol class="home-steps">
            <% loop $ShownBuckets %>
            <li class="home-step">
                <span class="home-step__number" aria-hidden="true">$Pos</span>
                <% if $ShowTitle %><h3>$Title</h3><% end_if %>
                $HTML
            </li>
            <% end_loop %>
        </ol>
        <% end_if %>
    </div>
</div>
