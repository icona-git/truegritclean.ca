<div class="wrap">
  <% if $Title && $ShowTitle || $HTML %>
  <div class="block-intro">
    <% if $Title && $ShowTitle %><h2>$Title</h2><% end_if %>
    <% if $HTML %><div class="typography">$HTML</div><% end_if %>
  </div>
  <% end_if %>
  <div class="text-cards">
    <% loop $Buckets %>
    <div class="text-card">
      <% if $Title && $ShowTitle %><h3>$Title</h3><% end_if %>
      <% if $HTML %><div class="typography">$HTML</div><% end_if %>
      <% if $Link %><a href="$Link.URL" class="text-link"<% if $Link.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$Link.Title</a><% end_if %>
    </div>
    <% end_loop %>
  </div>
</div>
