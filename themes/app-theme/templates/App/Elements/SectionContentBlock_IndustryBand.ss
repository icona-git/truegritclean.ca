<%-- "Who we work with" band (Figma index.html .industry-band): the block's Background Image on one half, black
     text half on the other. The client groups are the bulleted list in the block's text and the closing line its
     paragraph; blocks/_truegrit.scss styles them as the export's .industry-tags and .industry-band__statement. --%>
<div class="industry-band<% if not $BgImage.exists %> industry-band--no-photo<% end_if %>">
    <% if $BgImage.exists %>
    <div class="industry-band__media">
        <img src="$BgImage.Fill(720,600).URL" srcset="$BgImage.Fill(720,600).URL 720w, $BgImage.Fill(1440,1200).URL 1440w" sizes="(min-width: 62rem) 50vw, 100vw" width="720" height="600" alt="$BgImage.Title.ATT" loading="lazy" decoding="async">
    </div>
    <% end_if %>
    <div class="industry-band__content">
        <div class="industry-band__content-inner">
            <% if $Eyebrow %><p class="eyebrow eyebrow--light">$Eyebrow</p><% end_if %>
            <% if $ShowTitle %><% if $Title %><h2>$Title</h2><% end_if %><% end_if %>
            <% if $HTML %><div class="industry-band__body">$HTML</div><% end_if %>
        </div>
    </div>
</div>
