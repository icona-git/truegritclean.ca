<%-- The three phases on a service page (Figma service.html .service-phases): numbered charcoal cards with a yellow
     base. An item's text can hold a list; blocks/_truegrit.scss styles it for the dark card. --%>
<div class="section section--dark service-phases">
    <div class="section-shell">
        <% include App\Includes\SectionHeading Tone='light' %>
        <% if $ShownBuckets.Count %>
        <div class="service-phases__grid">
            <% loop $ShownBuckets %>
            <article class="service-phase">
                <span class="service-phase__number" aria-hidden="true">0$Pos</span>
                <% if $ShowTitle %><h3>$Title</h3><% end_if %>
                <% if $HTML %><div class="service-phase__body">$HTML</div><% end_if %>
            </article>
            <% end_loop %>
        </div>
        <% end_if %>
    </div>
</div>
