<% include App\Includes\Header %>

<main id="main-content" tabindex="-1">
  <%-- Breadcrumb strip and the Figma quote.html hero (Phase 4). The form layout, the reassurance panel beside it
       and the numbered field sections are Phase 5. --%>
  $Breadcrumbs
  <% include App\Includes\PageHero Variant='quote', TextClass='text' %>

  <%-- ContactPage extends UserDefinedForm: $Form is the userforms form built in the CMS (Form Fields tab).
       After a submission userforms blanks $Form and puts the confirmation message in $Content. --%>
  <section class="contact-intro">
    <div class="wrap contact-intro__inner">

      <div class="contact-intro__text">
        <% if $Content %><div class="typography">$Content</div><% end_if %>
        $Form
      </div>

      <% if $SiteConfig.StreetAddress || $SiteConfig.Email || $SiteConfig.Phone %>
      <aside class="contact-coords">
        <ul class="contact-coords__list">
          <% if $SiteConfig.StreetAddress %>
          <li>
            <span class="contact-coords__icon">
              <img src="/_resources/themes/app-theme/images/icon-address.svg" alt="" aria-hidden="true" width="48" height="48">
            </span>
            <div>
              <p class="contact-coords__label">Address</p>
              <p>$SiteConfig.StreetAddress<br>$SiteConfig.City, $SiteConfig.Province $SiteConfig.PostalCode</p>
            </div>
          </li>
          <% end_if %>
          <% if $SiteConfig.Email %>
          <li>
            <span class="contact-coords__icon">
              <img src="/_resources/themes/app-theme/images/icon-email.svg" alt="" aria-hidden="true" width="48" height="48">
            </span>
            <div>
              <p class="contact-coords__label">Email</p>
              <a href="mailto:$SiteConfig.Email">$SiteConfig.Email</a>
            </div>
          </li>
          <% end_if %>
          <% if $SiteConfig.Phone %>
          <li>
            <span class="contact-coords__icon">
              <img src="/_resources/themes/app-theme/images/icon-contact.svg" alt="" aria-hidden="true" width="48" height="48">
            </span>
            <div>
              <p class="contact-coords__label">Phone</p>
              <a href="tel:$SiteConfig.Phone">$SiteConfig.Phone</a>
            </div>
          </li>
          <% end_if %>
        </ul>
      </aside>
      <% end_if %>

    </div>
  </section>

  $ElementalArea

</main>

<% include App\Includes\BookingBanner %>
<% include App\Includes\Footer %>
