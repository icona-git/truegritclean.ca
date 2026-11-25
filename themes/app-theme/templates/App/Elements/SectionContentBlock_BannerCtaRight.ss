<div class="banner-cta__inner">
  <div class="banner-cta__body">
    <% if $Title && $ShowTitle %><h2>$Title</h2><% end_if %>
    <% if $HTML %><div class="typography">$HTML</div><% end_if %>
    <% if $CtaLink %>
    <div class="banner-cta__contact">
      <a href="$CtaLink.URL" class="btn"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a>
    </div>
    <% end_if %>
  </div>
  <% if $BgImage %>
  <div class="banner-cta__img">
    <img src="$BgImage.Fill(700,500).URL" alt="" loading="lazy">
  </div>
  <% end_if %>
</div>
