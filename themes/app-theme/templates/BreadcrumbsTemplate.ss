<%-- The breadcrumb trail, as the Figma breadcrumb strip (service.html .breadcrumb: a charcoal band under the header).
     SiteTree::Breadcrumbs() renders this wherever a template calls $Breadcrumbs, which is every layout except the
     home page's. $Pages is the page's ancestor chain INCLUDING the page itself. The home page is not in it
     (top-level pages are its siblings), so it goes first, from $BreadcrumbHome: the same record and name the
     BreadcrumbList JSON-LD starts with (App\Extensions\SEOMetaExtension). The current page is text, not a link to
     itself. Separators are drawn in CSS, so screen readers never read one.
     Two guards: nothing renders on the home page itself, whatever its page type; and the home crumb is left out
     when the home page cannot be found (e.g. unpublished), because <% with %> does not skip a null value.
     Keep id="breadcrumbs": the build-job template's render check looks for it. --%>
<% if $BreadcrumbHome.ID != $ID %>
<nav id="breadcrumbs" class="breadcrumb" aria-label="Breadcrumb">
    <div class="section-shell">
        <ol class="breadcrumb__list">
            <% if $BreadcrumbHome %>
            <% with $BreadcrumbHome %>
            <li><a href="$Link">$MenuTitle</a></li>
            <% end_with %>
            <% end_if %>
            <% loop $Pages %>
            <% if $IsLast %>
            <li aria-current="page">$MenuTitle</li>
            <% else %>
            <li><a href="$Link">$MenuTitle</a></li>
            <% end_if %>
            <% end_loop %>
        </ol>
    </div>
</nav>
<% end_if %>
