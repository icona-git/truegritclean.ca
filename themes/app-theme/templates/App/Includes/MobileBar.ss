<%-- Sticky mobile bar, from the Figma export (css/src/components/_mobile-bar.scss): Call + Get a quote, fixed to
     the bottom of the screen below 992px. Included once, by Page.ss. Not on the quote page itself, which has the
     form (the export's quote page has no bar). --%>
<% if $QuotePage.ID != $ID %>
<% if $SiteConfig.Phone || $QuotePage %>
<nav class="mobile-bar" aria-label="Quick actions">
    <% if $SiteConfig.Phone %>
    <a class="mobile-bar__button mobile-bar__button--call" href="tel:$SiteConfig.PhoneLink">Call</a>
    <% end_if %>
    <% if $QuotePage %>
    <a class="mobile-bar__button mobile-bar__button--quote" href="$QuotePage.Link">Get a quote</a>
    <% end_if %>
</nav>
<% end_if %>
<% end_if %>
