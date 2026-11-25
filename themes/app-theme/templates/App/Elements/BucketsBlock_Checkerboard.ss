<div class="wrap">
  <% if $Title && $ShowTitle || $HTML %>
  <div class="block-intro">
    <% if $Title && $ShowTitle %><h2>$Title</h2><% end_if %>
    <% if $HTML %><div class="typography">$HTML</div><% end_if %>
  </div>
  <% end_if %>
  <div class="checker-grid">
    <% loop $Buckets %>
    <div class="checker-card">
      <%-- No image, no image column: the card body takes the row. Never a remote placeholder image. --%>
      <% if $Image %>
      <div class="checker-card__img">
        <img src="$Image.ScaleWidth(800).URL" alt="$Title" loading="lazy">
      </div>
      <% end_if %>
      <div class="checker-card__body">
        <% if $Title && $ShowTitle %><h3>$Title</h3><% end_if %>
        <% if $SubTitle %><p class="bucket__subtitle">$SubTitle</p><% end_if %>
        <% if $HTML %><div class="typography">$HTML</div><% end_if %>
        <% if $Link %><a href="$Link.URL" class="text-link"<% if $Link.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$Link.Title</a><% end_if %>
      </div>
    </div>
    <% end_loop %>
  </div>
</div>
