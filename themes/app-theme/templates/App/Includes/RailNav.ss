<nav class="rail-nav" aria-label="Section navigation">
  <div class="rail-nav__header">
    <span class="rail-nav__title">
      $Level(1).MenuTitle
    </span>
  </div>
  <ul class="rail-nav__list">
    <% loop $Menu(2) %>
    <li class="rail-nav__item">
      <a href="$Link" class="rail-nav__link $LinkingMode">$MenuTitle</a>
    </li>
    <% end_loop %>
  </ul>
</nav>
