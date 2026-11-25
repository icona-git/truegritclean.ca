<div class="wrap">
  <div class="oc-layout">

    <%-- ── Left: heading + intro text ────────────────────────────────────────── --%>
    <div class="oc-intro">
      <% if $Title && $ShowTitle %><h2 class="oc-intro__title">$Title</h2><% end_if %>
      <% if $HTML %><div class="oc-intro__body">$HTML</div><% end_if %>
    </div>

    <%-- ── Person cards ───────────────────────────────────────────────────────── --%>
    <div class="oc-cards">
      <% loop $People.Sort(ManySort) %>
      <a href="$Link" class="oc-card">
        <% if $Headshot %>
        <div class="person-photo-wrap">
          <img class="person-photo--color" src="$Headshot.Fill(302,383).URL" alt="$Title" loading="lazy">
          <% if $TeaserImage %>
          <img class="person-photo--bw" data-desktop-src="$TeaserImage.Fill(302,383).URL" alt="" aria-hidden="true">
          <% end_if %>
        </div>
        <% end_if %>
        <div class="oc-card__body">
          <div class="oc-card__meta">
            <p class="oc-card__name">$Title</p>
            <% if $JobTitle %><p class="oc-card__jobtitle">$JobTitle</p><% end_if %>
          </div>
          <% if $TeaserContent %><div class="oc-card__bio">$TeaserContent</div><% end_if %>
          <span class="text-link">Read more</span>
        </div>
      </a>
      <% end_loop %>
    </div>

  </div>
</div>
