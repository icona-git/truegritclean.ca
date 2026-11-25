<%-- Mobile off-canvas menu. Toggled by minioffcanvas.js via data-bs-toggle="offcanvas". --%>
<div class="offcanvas" id="mobile-menu-canvas" tabindex="-1" aria-labelledby="mobile-menu-toggle">
    <nav class="offcanvas__body" id="mobile-menu" aria-label="Mobile navigation">

        <% loop $MainMenu %>
        <% if $ShowTopNavSubMenu && $Children %>

        <div class="nav-item-row">
            <a class="nav-link $LinkingMode" href="$Link">$MenuTitle</a>
            <button class="nav-collapse-toggle"
                data-bs-toggle="collapse"
                data-bs-target="#mobile-sub-$ID"
                aria-expanded="false"
                aria-controls="mobile-sub-$ID"
                aria-label="Expand $MenuTitle">
            </button>
        </div>
        <div id="mobile-sub-$ID" class="collapse">
            <% loop $Children %>
            <a href="$Link" class="$LinkingMode">$MenuTitle</a>
            <% end_loop %>
        </div>

        <% else %>
        <a class="nav-link $LinkingMode" href="$Link">$MenuTitle</a>
        <% end_if %>
        <% end_loop %>

    </nav>
</div>
