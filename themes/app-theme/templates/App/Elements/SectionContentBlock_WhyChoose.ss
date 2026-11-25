<% if $BgImage %>
<style type="text/css">
  @media only screen and (min-width: 320px) {
  <% if $BgImageMobile %>
    .block-$ID .why-choose__inner { background-image: url('{$BgImageMobile.URL}'); }
  <% else %>
    .block-$ID .why-choose__inner { background-image: url('{$BgImage.Fill(600,600).URL}'); }
  <% end_if %>
  }
  @media only screen and (min-width: 576px) {
  <% if $BgImageMobile %>
    .block-$ID .why-choose__inner { background-image: url('{$BgImageMobile.URL}'); }
  <% else %>
    .block-$ID .why-choose__inner { background-image: url('{$BgImage.Fill(900,700).URL}'); }
  <% end_if %>
  }
  @media only screen and (min-width: 1024px) {
    .block-$ID .why-choose__inner { background-image: url('{$BgImage.URL}'); background-position: right top; }
  }
</style>
<% end_if %>

<div class="why-choose__inner">

  <div class="wrap">
    <div class="why-choose__body">

      <% if $Title && $ShowTitle %><h2 class="block-heading-line">$Title</h2><% end_if %>
      <% if $HTML %><div class="why-choose__list">$HTML</div><% end_if %>

      <div class="why-choose__contact">
        <p class="why-choose__contact-label">Contact Us</p>
        <a href="mailto:$SiteConfig.Email" class="why-choose__email">
          <img src="/_resources/themes/app-theme/images/icon-email.svg" alt="" aria-hidden="true" width="30" height="30">
          $SiteConfig.Email
        </a>
      </div>

      <% if $CtaLink %><a href="$CtaLink.URL" class="btn"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title</a><% end_if %>

    </div>
  </div>

</div>
