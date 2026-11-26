<%-- The line icon on a service card (Figma index.html .home-service__icon), chosen by the service page's URL
     segment: Key is the page's URLSegment. A service page whose address is none of these four gets the crane. --%>
<div class="home-service__icon">
    <% if $Key == 'renovation-cleaning' %>
    <svg aria-hidden="true" viewBox="0 0 40 40">
        <path d="M5 19 20 7l15 12M9 17v17h22V17M17 34V23h6v11" />
        <path d="m27 8 6 6M28 5l7 7M31 3l5 5" />
    </svg>
    <% else_if $Key == 'restaurant-cleaning' %>
    <svg aria-hidden="true" viewBox="0 0 40 40">
        <path d="M6 16h28v19H6zM4 16l4-9h24l4 9M11 16v5M17 16v5M23 16v5M29 16v5M10 25h9v10M24 25h6v5h-6z" />
    </svg>
    <% else_if $Key == 'hoarding-cleaning' %>
    <svg aria-hidden="true" viewBox="0 0 40 40">
        <path d="M5 20h14v14H5zM21 15h14v19H21zM8 12h11v8H8zM11 24h3M25 20h6M25 25h6" />
    </svg>
    <% else %>
    <svg aria-hidden="true" viewBox="0 0 40 40">
        <path d="M7 34h27M12 34V7h4v27M16 10h17M16 15h13M30 10l4 5M22 15v6M19 24h6v5h-6z" />
    </svg>
    <% end_if %>
</div>
