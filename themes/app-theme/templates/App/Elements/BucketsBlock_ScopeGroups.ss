<%-- Detailed scope (Figma service.html .scope-groups): each item is a labelled group (its title) with a ticked list
     (the bulleted list in its text). Two groups side by side from tablet width; a block with a single group lets
     its list run in two columns instead. An item with "Show title" unticked has no label. --%>
<div class="section section--light">
    <div class="section-shell">
        <% include App\Includes\SectionHeading Tone='dark' %>
        <% if $ShownBuckets.Count %>
        <div class="scope-groups<% if $ShownBuckets.Count == 1 %> scope-groups--single<% end_if %>">
            <% loop $ShownBuckets %>
            <article class="scope-group">
                <% if $ShowTitle %><h3 class="scope-group__label">$Title</h3><% end_if %>
                <% if $HTML %><div class="scope-group__body">$HTML</div><% end_if %>
            </article>
            <% end_loop %>
        </div>
        <% end_if %>
    </div>
</div>
