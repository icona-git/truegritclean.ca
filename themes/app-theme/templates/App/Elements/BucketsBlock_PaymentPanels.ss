<div class="wrap">
  <%-- <% if $Title || $HTML %>
  <div class="block-intro">
    <% if $Title && $ShowTitle %><h2>$Title</h2><% end_if %>
    <% if $HTML %><div class="typography">$HTML</div><% end_if %>
  </div>
  <% end_if %> --%>
  <div class="panel-stack">
    <% loop $ShownBuckets %>
    <div class="panel-card">
      <% if $SecondaryFile %>
      <div class="panel-card__icon">
        <img src="$SecondaryFile.URL" alt="$SecondaryFile.Title" aria-hidden="true" loading="lazy" width="120" height="120">
      </div>
      <% end_if %>
      <div class="panel-card__body">
        <% if $Title && $ShowTitle %><h3 class="panel-card__title">$Title</h3><% end_if %>
        <% if $HTML %><div class="typography">$HTML</div><% end_if %>
      </div>
    </div>
    <% end_loop %>
  </div>
</div>
