<div class="wrap">
  <div class="split intro-split">

    <div class="intro__body">
      <% if $Title && $ShowTitle %><h2 class="intro__heading">$Title</h2><% end_if %>
      <% if $HTML %><div class="typography">$HTML</div><% end_if %>
      <% if $CtaLink %><a href="$CtaLink.URL" class="btn"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a><% end_if %>
    </div>

    <div class="intro__credentials">
      <% loop $Buckets %>
      <div class="credential-item">
        <% if $SecondaryFile %>
        <img src="$SecondaryFile.URL" alt="" aria-hidden="true" width="50" height="50" loading="lazy">
        <% end_if %>
        <p>$Title</p>
      </div>
      <% end_loop %>
    </div>

  </div>
</div>
