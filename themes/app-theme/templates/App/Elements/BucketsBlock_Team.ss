<%-- The team (Figma about.html .team-grid): one card per person, with the item's Image as the photo (square crop),
     its title as the name, Subtitle as the role and its text as the bio. A card with no photo shows the text
     only (the export's "[Photo to come]" box is a design placeholder, not something to launch with). --%>
<div class="section section--grey">
    <div class="section-shell">
        <% include App\Includes\SectionHeading Tone='dark' %>
        <% if $ShownBuckets.Count %>
        <div class="team-grid">
            <% loop $ShownBuckets %>
            <article class="team-card<% if not $Image.exists %> team-card--no-photo<% end_if %>">
                <% if $Image.exists %>
                <div class="team-card__photo">
                    <img src="$Image.Fill(640,640).URL" width="640" height="640" alt="$Image.Title.ATT" loading="lazy" decoding="async">
                </div>
                <% end_if %>
                <div class="team-card__body">
                    <% if $ShowTitle %><h3>$Title</h3><% end_if %>
                    <% if $SubTitle %><p class="team-card__role">$SubTitle</p><% end_if %>
                    $HTML
                </div>
            </article>
            <% end_loop %>
        </div>
        <% end_if %>
    </div>
</div>
