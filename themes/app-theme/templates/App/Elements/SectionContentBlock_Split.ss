<div class="wrap">
  <div class="split">
    <div class="split__body">
      <% if $Title && $ShowTitle %><h2>$Title</h2><% end_if %>
      <% if $HTML %><div class="typography">$HTML</div><% end_if %>
      <% if $CtaLink %><div class="block-cta"><a href="$CtaLink.URL" class="btn"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a></div><% end_if %>
    </div>
    <div class="split__img">
      <% if $BgImage %>
      <img src="$BgImage.URL" alt="" loading="lazy">
      <% end_if %>
    </div>
  </div>
</div>
