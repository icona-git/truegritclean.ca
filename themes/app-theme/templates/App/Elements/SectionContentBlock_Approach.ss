<div class="wrap">
  <div class="approach__layout">

    <%-- ── Left: mask image ─────────────────────────────────────────────── --%>
    <div class="approach__img-col">
      <% if $BgImage %>
      <img src="$BgImage.URL" alt="" loading="lazy">
      <% end_if %>
    </div>

    <%-- ── Right: heading, body text with bullets, CTA ───────────────────────── --%>
    <div class="approach__body">
      <% if $Title && $ShowTitle %><h2 class="approach__title">$Title</h2><% end_if %>
      <% if $HTML %><div class="approach__content typography">$HTML</div><% end_if %>
      <% if $CtaLink %><div class="block-cta"><a href="$CtaLink.URL" class="btn"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a></div><% end_if %>
    </div>

  </div>
</div>
