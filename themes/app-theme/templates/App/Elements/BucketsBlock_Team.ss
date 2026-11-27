<%-- The team (Figma about.html .team-grid): one card per person, with the item's Image as the photo (square crop),
     its title as the name, Subtitle as the role and its text as the bio. A card with no photo shows the export's
     placeholder box, labelled "Photo needed", on the local site and staging ($ShowMissingPhotos, see
     App\Models\MissingContent); on live it shows the text only, the launch state agreed in Phase 4. --%>
<div class="section section--grey">
    <div class="section-shell">
        <% include App\Includes\SectionHeading Tone='dark' %>
        <% if $ShownBuckets.Count %>
        <div class="team-grid">
            <% loop $ShownBuckets %>
            <article class="team-card<% if not $Image.exists %><% if not $ShowMissingPhotos %> team-card--no-photo<% end_if %><% end_if %>">
                <% if $Image.exists %>
                <div class="team-card__photo">
                    <img src="$Image.Fill(640,640).URL" width="640" height="640" alt="$Image.Title.ATT" loading="lazy" decoding="async">
                </div>
                <% else_if $ShowMissingPhotos %>
                <div class="team-card__photo-placeholder content-missing-photo" role="img" aria-label="<%t App\Models\MissingContent.PHOTO_NEEDED 'Photo needed' %>: $Title.ATT">
                    <span class="content-missing__label"><span class="content-missing__flag"><%t App\Models\MissingContent.PHOTO_NEEDED 'Photo needed' %></span></span>
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
