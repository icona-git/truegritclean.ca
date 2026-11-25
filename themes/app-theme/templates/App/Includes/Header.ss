<header class="site-header" id="header">
    <div class="site-header__inner wrap">

        <a class="site-header__logo" href="/" aria-label="$SiteConfig.Title — Home">
            <img src="/_resources/themes/app-theme/images/logo-white.svg"
                 alt="$SiteConfig.Title" width="240" height="44" loading="eager">
        </a>

        <nav class="site-header__nav" aria-label="Primary navigation">
            <% loop $MainMenu %>
            <div class="nav-item">
                <a href="$Link" class="nav-link $LinkingMode">$MenuTitle</a>
                <% if $ShowTopNavSubMenu && $Children %>
                <div class="submenu">
                    <% if $Children.First.Children %>
                    <div class="submenu__divided">
                        <% loop $Children %>
                        <% if $Children %>
                        <div class="submenu__section">
                            <a class="submenu__parent" href="$Link">$MenuTitle</a>
                            <nav class="submenu__children">
                                <% loop $Children %>
                                <a href="$Link">$MenuTitle</a>
                                <% end_loop %>
                            </nav>
                        </div>
                        <% else %>
                        <div class="submenu__full">
                            <a href="$Link">$MenuTitle</a>
                        </div>
                        <% end_if %>
                        <% end_loop %>
                    </div>
                    <% else %>
                    <% loop $Children %>
                    <a href="$Link">$MenuTitle</a>
                    <% end_loop %>
                    <% end_if %>
                </div>
                <% end_if %>
            </div>
            <% end_loop %>
        </nav>

        <button class="hamburger hide-desktop"
            id="mobile-menu-toggle"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#mobile-menu-canvas"
            aria-controls="mobile-menu-canvas"
            aria-expanded="false"
            aria-label="Open menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

    </div>
</header>
<% include App\Includes\MobileMenu %>
