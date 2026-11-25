<div class="wrap">
  <% if $Title && $ShowTitle || $HTML || $CtaLink %>
  <div class="person-grid__intro">
    <% if $Title && $ShowTitle %><h2 class="person-grid__title">$Title</h2><% end_if %>
    <% if $HTML %><div class="person-grid__body typography">$HTML</div><% end_if %>
    <%-- The block's own CTA link (Content tab), so the label and target are the site's, not the starter's. --%>
    <% if $CtaLink %><a href="$CtaLink.URL" class="btn"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a><% end_if %>
  </div>
  <% end_if %>
  <div class="person-grid">
    <% loop $People %>
    <div class="person-card">
      <% if $Headshot %>
      <a href="$Link">
        <div class="person-photo-wrap">
          <img class="person-photo--color" src="$Headshot.Fill(302,383).URL" alt="$Title" loading="lazy">
          <% if $TeaserImage %>
          <img class="person-photo--bw" data-desktop-src="$TeaserImage.Fill(302,383).URL" alt="" aria-hidden="true">
          <% end_if %>
        </div>
      </a>
      <% end_if %>
      <h3><a href="$Link">$Title</a></h3>
      <% if $JobTitle %><p class="person-card__title">$JobTitle</p><% end_if %>
      <% if $TeaserContent %><div class="person-card__bio">$TeaserContent</div><% end_if %>
      <a href="$Link" class="text-link">Read more</a>
    </div>
    <% end_loop %>
  </div>
</div>
