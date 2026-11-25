<% include App\Includes\Header %>

<%-- ── Person hero: info left, headshot right ────────────────────────────── --%>
<% if $HeroImageOrDefault %>
<style type="text/css">
  <% if $HeroImageMobile %>
    #person-hero .person-hero__media { background-image: url('{$HeroImageMobile.URL}'); }
  <% else %>
    #person-hero .person-hero__media { background-image: url('{$HeroImageOrDefault.Fit(600,800).URL}'); }
  <% end_if %>
  @media only screen and (min-width: 1024px) {
    #person-hero .person-hero__media { background-image: url('{$HeroImageOrDefault.URL}'); }
  }
</style>
<% end_if %>
<section id="person-hero" class="person-hero">
  <div class="person-hero__media hero-focus--{$HeroFocusPosition}"></div>
  <div class="wrap person-hero__inner">

    <div class="person-hero__info">
      <div class="person-hero__headline">
        <% if $HeroLabel %><p class="hero__label">$HeroLabel</p><% end_if %>
        <h1 class="person-hero__name">$Title</h1>
        <% if $JobTitle %><p class="person-hero__jobtitle">$JobTitle</p><% end_if %>
      </div>
      <div class="person-hero__contact">
        <% if $Phone %>
        <div class="person-hero__contact-item">
          <img src="/_resources/themes/app-theme/images/icon-hero-phone.svg" alt="" width="20" height="20" aria-hidden="true">
          <a href="tel:$Phone">$Phone</a>
        </div>
        <% end_if %>
        <% if $Email %>
        <div class="person-hero__contact-item">
          <img src="/_resources/themes/app-theme/images/icon-hero-email.svg" alt="" width="20" height="20" aria-hidden="true">
          <a href="mailto:$Email">$Email</a>
        </div>
        <% end_if %>
        <% if $LinkedIn %>
        <div class="person-hero__contact-item">
          <img src="/_resources/themes/app-theme/images/icon-hero-linkedin.svg" alt="" width="20" height="20" aria-hidden="true">
          <a href="https://www.linkedin.com/in/$LinkedIn" target="_blank" rel="noopener">$LinkedIn</a>
        </div>
        <% end_if %>
      </div>
    </div>
    <%-- Not using headshot as a separate field 
    <% if $Headshot %>
    <div class="person-hero__photo">
      <img src="$Headshot.ScaleWidth(600).URL" alt="$Title" loading="eager">
    </div>
    <% end_if %> --%>

  </div>
</section>

<%-- ── Intro: rail (credentials) left, About content right ──────────────── --%>
<main id="main-content" tabindex="-1">
  <section class="person-intro">
    <div class="wrap person-intro__inner">

      <div class="person-intro__body">
        $Breadcrumbs
        <%-- <h2 class="person-intro__heading">About $Title</h2> --%>
        <% if $Content %>
        <div class="typography">$Content</div>
        <% end_if %>
      </div>

      <aside class="person-rail">

        <% if $RailContent %>
        <div class="person-rail__section">
          <div class="person-rail__section-body">$RailContent</div>
        </div>
        <% end_if %>

        <% loop $LegalAssistants.Sort(ManySort) %>
        <div class="person-assistant">
          <% if $Headshot %>
          <img class="person-assistant__photo" src="$Headshot.Fill(240,300).URL" alt="$Title">
          <% end_if %>
          <div class="person-assistant__info">
            <p class="person-assistant__name">$Title</p>
            <% if $JobTitle %><p class="person-assistant__jobtitle">$JobTitle</p><% end_if %>
            <% if $Phone %>
            <div class="person-assistant__contact-item">
              <img src="/_resources/themes/app-theme/images/icon-phone-charcoal.svg" alt="" width="14" height="14" aria-hidden="true">
              <a href="tel:$Phone">$Phone</a>
            </div>
            <% end_if %>
            <% if $Email %>
            <div class="person-assistant__contact-item">
              <img src="/_resources/themes/app-theme/images/icon-email-charcoal.svg" alt="" width="14" height="14" aria-hidden="true">
              <a href="mailto:$Email">$Email</a>
            </div>
            <% end_if %>
            <%-- <a href="$Link" class="text-link">Read More</a> --%>
          </div>
        </div>
        <% end_loop %>

      </aside>

    </div>
  </section>

  <%-- ── Areas of Practice ──────────────────────────────────────────────── --%>
  <% if $PracticeAreas %>
  <section class="person-aop">
    <div class="wrap">
      <div class="person-aop__intro">
        <h2 class="person-aop__heading">Areas of Practice</h2>
      </div>
      <div class="person-aop__cards">
        <% loop $PracticeAreas.sort('ManySort') %>
        <div class="person-aop__card">
          <h3 class="person-aop__card-title">$Title</h3>
          <% if $TeaserContent %>
          <p class="person-aop__card-body">$TeaserContent.Summary(180)</p>
          <% end_if %>
          <a href="$Link" class="text-link">Find Out More</a>
        </div>
        <% end_loop %>
      </div>
    </div>
  </section>
  <% end_if %>


  $ElementalArea
</main>
<% include App\Includes\BookingBanner %>
<% include App\Includes\Footer %>
