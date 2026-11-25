<div class="wrap">
  <% if $Title && $ShowTitle || $HTML || $CtaLink %>
  <div class="block-intro">
    <% if $Title && $ShowTitle %><h2>$Title</h2><% end_if %>
    <% if $HTML %><div class="typography">$HTML</div><% end_if %>
    <% if $CtaLink %>
    <div class="block-cta">
      <a href="$CtaLink.URL" class="btn"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a>
    </div>
    <% end_if %>
  </div>
  <% end_if %>
  <div class="bucket-grid">
    <% loop $Buckets %>
    <div class="bucket">
      <% if $Image %>
      <div class="bucket__img">
        <% if $Image.Extension == svg %>
        <img src="$Image.URL" alt="" loading="lazy">
        <% else %>
        <img src="$Image.ScaleWidth(80).URL" alt="" loading="lazy">
        <% end_if %>
      </div>
      <% end_if %>
      <% if $Title && $ShowTitle %><h3>$Title</h3><% end_if %>
      <% if $SubTitle %><p class="bucket__subtitle">$SubTitle</p><% end_if %>
      <% if $HTML %><div class="typography">$HTML</div><% end_if %>
      <% if $Link %><a href="$Link.URL" class="text-link"<% if $Link.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$Link.Title</a><% end_if %>
    </div>
    <% end_loop %>
  </div>
</div>
