<div class="wrap">
  <div class="posts-header">
    <% if $Title && $ShowTitle %><h2 class="block-heading-line">$Title</h2><% end_if %>
    <% if $CtaLink %><a href="$CtaLink.URL" class="text-link posts-header__cta hide-mobile"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a><% end_if %>
  </div>
  <div class="post-grid">
    <% loop $BlogPosts %>
    <div class="post-card">
      <% if $FeaturedImage %>
      <a href="$Link" class="post-card__img">
        <img src="$FeaturedImage.Fill(640,480).URL" alt="$Title" loading="lazy">
      </a>
      <% end_if %>
      <div class="post-card__body">
        <div class="post-card__meta">
          <h3><a href="$Link">$Title</a></h3>
          <% if $PublishDate %><p class="post-card__date">$PublishDate.Format('MMM d, y')</p><% end_if %>
        </div>
        <% if $Summary %><p class="post-card__summary">$Summary</p><% end_if %>
        <a href="$Link" class="text-link">Read More</a>
      </div>
    </div>
    <% end_loop %>
  </div>
  <% if $CtaLink %><a href="$CtaLink.URL" class="text-link hide-desktop posts-footer__cta"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a><% end_if %>
</div>
