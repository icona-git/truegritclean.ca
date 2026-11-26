<%-- The three phases on the home page (Figma index.html .phases-section): heading on the left, the block's text on
     the right, then one photo card per item (the item's Image, numbered 01, 02, 03) joined by the yellow connectors,
     and the block's link under them. --%>
<div class="section section--dark phases-section">
    <div class="section-shell">
        <div class="section-heading section-heading--split">
            <div>
                <% if $Eyebrow %><p class="eyebrow eyebrow--light">$Eyebrow</p><% end_if %>
                <% if $ShowTitle %><% if $Title %><h2>$Title</h2><% end_if %><% end_if %>
            </div>
            <% if $HTML %><div class="section-heading__intro">$HTML</div><% end_if %>
        </div>
        <% if $ShownBuckets.Count %>
        <div class="home-phases">
            <% loop $ShownBuckets %>
            <article class="home-phase">
                <div class="home-phase__media">
                    <% if $Image.exists %><img src="$Image.Fill(800,520).URL" width="800" height="520" alt="$Image.Title.ATT" loading="lazy" decoding="async"><% end_if %>
                    <span class="home-phase__number" aria-hidden="true">0$Pos</span>
                </div>
                <div class="home-phase__body">
                    <% if $ShowTitle %><h3>$Title</h3><% end_if %>
                    $HTML
                </div>
            </article>
            <% if not $IsLast %><span class="home-phases__connector" aria-hidden="true"></span><% end_if %>
            <% end_loop %>
        </div>
        <% end_if %>
        <% if $CtaLink %><a class="text-link text-link--light phases-section__link" href="$CtaLink.URL"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title <span aria-hidden="true">&rarr;</span></a><% end_if %>
    </div>
</div>
