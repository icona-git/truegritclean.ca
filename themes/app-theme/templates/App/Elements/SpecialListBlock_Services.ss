<div class="wrap">
  <% if $Title && $ShowTitle || $HTML %>
  <div class="services-intro">
    <% if $Title && $ShowTitle %><h2 class="services-intro__title">$Title</h2><% end_if %>
    <% if $HTML %><div class="services-intro__body">$HTML</div><% end_if %>
  </div>
  <% end_if %>
  <div class="services-grid">
    <% loop $PracticeAreas %>
    <a href="$Link" class="service-card">
      <% if $TeaserImage %>
      <div class="service-card__img">
        <img src="$TeaserImage.Fill(600,400).URL" alt="$Title" loading="lazy">
      </div>
      <% end_if %>
      <div class="service-card__body">
        <h3>$Title</h3>
        <% if $TeaserContent %><div class="typography">$TeaserContent</div><% end_if %>
      </div>
    </a>
    <% end_loop %>
  </div>
</div>
