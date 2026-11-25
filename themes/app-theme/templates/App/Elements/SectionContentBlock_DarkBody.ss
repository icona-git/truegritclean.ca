<% if $BgImage %>
<div class="dark-body__bg-img" aria-hidden="true">
  <img src="$BgImage.URL" alt="" loading="lazy">
</div>
<% end_if %>
<div class="wrap dark-body__inner">
  <div class="dark-body__content">
    <% if $Title && $ShowTitle %><h2 class="dark-body__title">$Title</h2><% end_if %>
    <% if $HTML %><div class="dark-body__html">$HTML</div><% end_if %>
    <% if $CtaLink %><a href="$CtaLink.URL" class="btn btn--outline-white"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a><% end_if %>
  </div>
</div>
