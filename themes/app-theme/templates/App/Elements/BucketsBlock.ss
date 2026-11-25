<%-- No Block Style chosen. Renders the block and its buckets plainly; the variant templates
     (BucketsBlock_*.ss) carry the designed layouts. --%>
<div class="wrap">
  <% if $Title && $ShowTitle || $HTML %>
  <div class="block-intro">
    <% if $Title && $ShowTitle %><h2>$Title</h2><% end_if %>
    <% if $HTML %><div class="typography">$HTML</div><% end_if %>
  </div>
  <% end_if %>
  <div class="bucket-list">
    <% loop $Buckets %>
    <div class="bucket">
      <% if $Title && $ShowTitle %><h3>$Title</h3><% end_if %>
      <% if $SubTitle %><p class="bucket__subtitle">$SubTitle</p><% end_if %>
      <% if $HTML %><div class="typography">$HTML</div><% end_if %>
      <% if $Link %><a href="$Link.URL" class="text-link"<% if $Link.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$Link.Title</a><% end_if %>
    </div>
    <% end_loop %>
  </div>
  <% if $CtaLink %><a href="$CtaLink.URL" class="btn"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a><% end_if %>
</div>
