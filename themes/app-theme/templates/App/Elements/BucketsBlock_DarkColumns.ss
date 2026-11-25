<div class="wrap">

  <%-- ── Header: title + intro sentence ───────────────────────────────────── --%>
  <div class="dark-cols__header">
    <% if $Title && $ShowTitle %><h2 class="dark-cols__title">$Title</h2><% end_if %>
    <% if $HTML %><div class="dark-cols__intro">$HTML</div><% end_if %>
  </div>

  <%-- ── Tiles: left-bordered columns ─────────────────────────────────────── --%>
  <div class="dark-cols__tiles">
    <% loop $Buckets %>
    <div class="dark-cols__tile">
      <h3 class="dark-cols__tile-heading">$Title</h3>
      <% if $HTML %><div class="dark-cols__tile-body">$HTML</div><% end_if %>
    </div>
    <% end_loop %>
  </div>

</div>
