<%-- Ticked list of reasons (Figma index.html and about.html .reasons-list): each item's title on its own line, two
     columns from tablet width. Only items whose claims are confirmed show ($ShownBuckets); with none left, the
     section is not rendered. Off the home page it takes about.html's narrower width (.about-reasons). --%>
<% if $ShownBuckets.Count %>
<div class="section section--light">
    <div class="section-shell">
        <% include App\Includes\SectionHeading Tone='dark' %>
        <ul class="reasons-list<% if $Page.ClassName.ShortName != 'HomePage' %> about-reasons<% end_if %>">
            <% loop $ShownBuckets %>
            <li><span aria-hidden="true">&#10003;</span>$Title</li>
            <% end_loop %>
        </ul>
    </div>
</div>
<% end_if %>
