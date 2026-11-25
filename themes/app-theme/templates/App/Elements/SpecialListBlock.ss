<%-- No Block Style chosen. A list block takes its source from the variant (Blog Posts, Services), so
     without one there is nothing to list: the block's own heading and text still render. --%>
<div class="wrap">
  <% if $Title && $ShowTitle || $HTML %>
  <div class="block-intro">
    <% if $Title && $ShowTitle %><h2>$Title</h2><% end_if %>
    <% if $HTML %><div class="typography">$HTML</div><% end_if %>
  </div>
  <% end_if %>
  <% if $CtaLink %><a href="$CtaLink.URL" class="btn"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a><% end_if %>
</div>
