<% include App\Includes\Header %>
<% if $HeroLayout == 'bleed-intro' %>
<% include App\Includes\HeroBleedIntro %>
<% else %>
<% include App\Includes\Hero %>
<% end_if %>
<% include App\Includes\BreadcrumbBar %>

<main id="main-content" tabindex="-1">
  <section class="page-layout bg-white">
    <div class="wrap">
      <% if $HideRailNav %>
      <div class="page-layout__content page-layout__content--full typography">
        $Content
        $ContentElementalArea
      </div>
      <% else %>
      <div class="page-layout__inner">

        <div class="page-layout__content typography">
          $Content
          $ContentElementalArea
        </div>

        <aside class="page-layout__rail">
          <% include App\Includes\RailNav %>
        </aside>

      </div>
      <% end_if %>
    </div>
  </section>

  $ElementalArea
</main>

<% include App\Includes\BookingBanner %>
<% include App\Includes\Footer %>
