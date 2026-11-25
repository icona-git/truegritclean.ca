<div class="wrap">
  <div class="photo-box">
    <%-- Dark box first in DOM → column-reverse on mobile puts image above it --%>
    <div class="photo-box__dark">
      <% if $Title && $ShowTitle %><h2 class="photo-box__title">$Title</h2><% end_if %>
      <% if $HTML %><div class="photo-box__body">$HTML</div><% end_if %>
      <% if $CtaLink %><a href="$CtaLink.URL" class="btn btn--outline-white"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a><% end_if %>
    </div>
    <% if $BgImage %>
    <div class="photo-box__img">
      <img src="$BgImage.ScaleWidth(900).URL" alt="" loading="lazy">
    </div>
    <% end_if %>
  </div>
</div>
