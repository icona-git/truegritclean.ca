<div class="wrap">

  <%-- ── Intro: heading + red rule + body text ────────────────────────────── --%>
  <div class="oa-intro">
    <% if $Title && $ShowTitle %><h2 class="oa-heading">$Title</h2><% end_if %>
    <span class="oa-rule" aria-hidden="true"></span>
    <% if $HTML %><div class="oa-intro__body">$HTML</div><% end_if %>
  </div>

  <%-- ── Cards: subheading + 5-col icon grid ──────────────────────────────── --%>
  <div class="oa-emphasizes">
    <p class="oa-subheading">Our approach emphasizes:</p>
    <div class="oa-cards">
      <% loop $Buckets %>
      <div class="oa-card">
        <% if $SecondaryFile %>
        <img src="$SecondaryFile.URL" alt="" aria-hidden="true" width="60" height="60" loading="lazy">
        <% end_if %>
        <h3 class="oa-card__title">$Title</h3>
        <% if $HTML %><div class="oa-card__desc">$HTML</div><% end_if %>
      </div>
      <% end_loop %>
    </div>
  </div>

</div>
