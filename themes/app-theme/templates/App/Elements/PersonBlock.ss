<%-- No Block Style chosen. Renders the block's people plainly; the variant templates
     (PersonBlock_*.ss) carry the designed layouts. --%>
<div class="wrap">
  <% if $Title && $ShowTitle || $HTML %>
  <div class="block-intro">
    <% if $Title && $ShowTitle %><h2>$Title</h2><% end_if %>
    <% if $HTML %><div class="typography">$HTML</div><% end_if %>
  </div>
  <% end_if %>
  <div class="person-grid">
    <% loop $People %>
    <div class="person-card">
      <% if $Headshot %>
      <a href="$Link"><img src="$Headshot.Fill(302,383).URL" alt="$Title" loading="lazy"></a>
      <% end_if %>
      <h3><a href="$Link">$Title</a></h3>
      <% if $JobTitle %><p class="person-card__title">$JobTitle</p><% end_if %>
    </div>
    <% end_loop %>
  </div>
  <% if $CtaLink %><a href="$CtaLink.URL" class="btn"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a><% end_if %>
</div>
