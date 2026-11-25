<div class="wrap dark-cta__inner">
  <div class="dark-cta__content">
    <% if $Title && $ShowTitle %><h2 class="dark-cta__title">$Title</h2><% end_if %>
    <% if $HTML %><div class="dark-cta__body">$HTML</div><% end_if %>
    <% if $CtaLink %><a href="$CtaLink.URL" class="btn btn--outline-white"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a><% end_if %>
  </div>
</div>
