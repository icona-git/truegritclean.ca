<% include App\Includes\Header %>
<% include App\Includes\Hero %>

<main id="main-content" tabindex="-1">

  <%-- ── Two-column intro: rail nav left, breadcrumbs + heading + body right ─── --%>
  <section class="pa-layout">
    <div class="wrap pa-layout__inner">

      <div class="pa-layout__content">

        $Breadcrumbs

        <% if $Content %>
        <div class="typography">
          $Content
        </div>
        <% end_if %>

      </div>

      <aside class="pa-layout__rail">
        <% include App\Includes\RailNav %>
      </aside>

    </div>
  </section>

  <%-- ── Lawyers practising in this area (internal-linking) ────────────────────── --%>
  <% if $Lawyers %>
  <section class="pa-lawyers person-aop">
    <div class="wrap">
      <div class="person-aop__intro">
        <h2 class="person-aop__heading">Lawyers Practising in This Area</h2>
      </div>
      <div class="person-aop__cards">
        <% loop $Lawyers.Sort('Sort') %>
        <div class="person-aop__card">
          <h3 class="person-aop__card-title"><a href="$Link">$Title</a></h3>
          <% if $JobTitle %>
          <p class="person-aop__card-body">$JobTitle</p>
          <% end_if %>
          <a href="$Link" class="text-link">View Profile</a>
        </div>
        <% end_loop %>
      </div>
    </div>
  </section>
  <% end_if %>

  <%-- ── Elemental blocks (full width below the intro) ────────────────────────── --%>
  $ElementalArea

</main>

<% include App\Includes\BookingBanner %>
<% include App\Includes\Footer %>
