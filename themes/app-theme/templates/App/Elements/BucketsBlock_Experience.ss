<% if $BgImage %>
<style type="text/css">
    @media only screen and (min-width: 320px) {
        .block-$ID { background-image: url('{$BgImage.Fill(600,400).URL}'); }
    }
    @media only screen and (min-width: 576px) {
        .block-$ID { background-image: url('{$BgImage.Fill(900,500).URL}'); }
    }
    @media only screen and (min-width: 992px) {
        .block-$ID { background-image: url('{$BgImage.URL}'); }
    }
</style>
<% end_if %>
<div class="wrap">
  <div class="experience-header">
    <% if $Title && $ShowTitle %><h2 class="block-heading-line">$Title</h2><% end_if %>
    <% if $CtaLink %><a href="$CtaLink.URL" class="text-link"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a><% end_if %>
  </div>
  <div class="experience-grid">
    <% loop $Buckets %>
    <div class="experience-card">
      <% if $SecondaryFile %>
      <div class="experience-card__icon">
        <img src="$SecondaryFile.URL" alt="" aria-hidden="true" loading="lazy">
      </div>
      <% else_if $Image %>
      <div class="experience-card__icon">
        <img src="$Image.URL" alt="" aria-hidden="true" loading="lazy">
      </div>
      <% end_if %>
      <p class="experience-card__desc">$Title</p>
      <% if $SubTitle %><span class="experience-card__date">$SubTitle</span><% end_if %>
    </div>
    <% end_loop %>
  </div>
</div>
