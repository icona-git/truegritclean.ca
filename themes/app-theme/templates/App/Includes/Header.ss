<%-- Site header, from the Figma export (css/src/components/_header.scss). Sticky; from 992px it shows the
     primary nav and the GET A QUOTE pill, below that a call icon and a <details> menu (no JavaScript).
     Nav items are the top-level pages ticked "Show in Main Menu" ($MainMenu). The quote page is reached by the
     pill, so it is not ticked. A page, or the section a page is in, gets the yellow underline. --%>
<header class="site-header" id="header">
    <div class="site-header__inner">

        <% include App\Includes\Logo %>

        <% if $MainMenu %>
        <nav class="site-header__nav" aria-label="Primary navigation">
            <% loop $MainMenu %>
            <a class="site-header__nav-link<% if $LinkingMode != 'link' %> site-header__nav-link--active<% end_if %>" href="$Link"<% if $isCurrent %> aria-current="page"<% end_if %>>$MenuTitle</a>
            <% end_loop %>
        </nav>
        <% end_if %>

        <% if $QuotePage %>
        <a class="button button--primary site-header__quote" href="$QuotePage.Link"<% if $QuotePage.ID == $ID %> aria-current="page"<% end_if %>>Get a quote</a>
        <% end_if %>

        <div class="site-header__mobile-actions">
            <% if $SiteConfig.Phone %>
            <a class="icon-button" href="tel:$SiteConfig.PhoneLink" aria-label="Call {$SiteConfig.Title}">
                <svg aria-hidden="true" viewBox="0 0 24 24">
                    <path d="M7.2 3.5 9.5 8l-2.1 1.5c1.2 2.7 3.3 4.8 6 6l1.6-2.1 4.4 2.3-.7 3.2c-.2.9-1 1.6-2 1.6C9.4 20.5 3.5 14.6 3.5 7.3c0-.9.7-1.8 1.6-2l2.1-.5Z" />
                </svg>
            </a>
            <% end_if %>
            <% if $MainMenu %>
            <details class="mobile-menu">
                <summary class="icon-button" aria-label="Open navigation menu">
                    <span class="mobile-menu__lines" aria-hidden="true"></span>
                </summary>
                <nav class="mobile-menu__panel" aria-label="Mobile navigation">
                    <% loop $MainMenu %>
                    <a href="$Link"<% if $isCurrent %> aria-current="page"<% end_if %>>$MenuTitle</a>
                    <% end_loop %>
                </nav>
            </details>
            <% end_if %>
        </div>

    </div>
</header>
