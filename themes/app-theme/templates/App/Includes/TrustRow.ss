<%-- Trust badges under the home hero (Figma index.html .trust-row). Insured, WorkSafeBC and HEPA are claims: each
     badge renders only once its box is ticked in Settings > Claims. The service-area badge always shows. --%>
<ul class="trust-row home-hero__trust" aria-label="Service assurances">
    <% if $SiteConfig.ClaimInsured %>
    <li class="trust-badge">
        <svg class="trust-badge__icon" aria-hidden="true" viewBox="0 0 24 24">
            <path d="m12 2 8 3v6c0 5.1-3.4 9-8 11-4.6-2-8-5.9-8-11V5l8-3Z" />
            <path d="m8 12 2.5 2.5L16 9" />
        </svg>
        <span>Insured</span>
    </li>
    <% end_if %>
    <% if $SiteConfig.ClaimWorkSafeBC %>
    <li class="trust-badge">
        <svg class="trust-badge__icon" aria-hidden="true" viewBox="0 0 24 24">
            <path d="M6 20V9a6 6 0 0 1 12 0v11M4 20h16M8 9h8" />
            <path d="M9 5V3h6v2" />
        </svg>
        <span>WorkSafeBC Registered</span>
    </li>
    <% end_if %>
    <% if $SiteConfig.ClaimHEPA %>
    <li class="trust-badge">
        <svg class="trust-badge__icon" aria-hidden="true" viewBox="0 0 24 24">
            <path d="M5 7h14v10H5zM8 10h8M8 14h5" />
            <path d="M9 7V4h6v3M8 17v3M16 17v3" />
        </svg>
        <span>HEPA Equipment</span>
    </li>
    <% end_if %>
    <li class="trust-badge">
        <svg class="trust-badge__icon" aria-hidden="true" viewBox="0 0 24 24">
            <path d="M12 21s7-6.1 7-12A7 7 0 0 0 5 9c0 5.9 7 12 7 12Z" />
            <circle cx="12" cy="9" r="2.2" />
        </svg>
        <span>Vancouver &amp; Lower Mainland</span>
    </li>
</ul>
