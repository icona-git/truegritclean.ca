<div class="wrap">
  <div class="iib-layout">

    <%-- ── Left: credential buckets ──────────────────────────────────────── --%>
    <div class="iib-credentials">
      <% loop $ShownBuckets %>
      <div class="iib-credential">
        <% if $SecondaryFile %>
        <img src="$SecondaryFile.URL" alt="" aria-hidden="true" width="50" height="50" loading="lazy">
        <% end_if %>
        <p>$Title</p>
      </div>
      <% end_loop %>
    </div>

    <%-- ── Right: breadcrumbs + heading + content ────────────────────────── --%>
    <div class="iib-body">

      <% if $Title && $ShowTitle %><h2 class="iib-heading">$Title</h2><% end_if %>
      <% if $HTML %><div class="typography iib-typography">$HTML</div><% end_if %>

    </div>

  </div>
</div>
