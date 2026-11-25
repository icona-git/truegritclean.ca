<div class="dark-split">
  <div class="dark-split__text">
    <% if $Title && $ShowTitle %><h2 class="dark-split__title">$Title</h2><% end_if %>
    <% if $HTML %><div class="dark-split__body">$HTML</div><% end_if %>
    <% if $CtaLink %><a href="$CtaLink.URL" class="btn btn--outline-white"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a><% end_if %>
  </div>
  <% if $BgImage %>
  <div class="dark-split__img">
    <img src="$BgImage.URL" alt="" loading="lazy">
  </div>
  <% end_if %>
</div>
