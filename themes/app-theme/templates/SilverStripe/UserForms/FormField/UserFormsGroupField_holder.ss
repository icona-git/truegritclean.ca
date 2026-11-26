<%-- A numbered form section (Figma .project-form__section): a userforms Field Group in the CMS, its title as the
     heading. The number in the yellow box (01, 02 ...) is a CSS counter, so reordering sections in the CMS renumbers
     them. Fields sit in the two-column .quote-fields grid; a field whose Extra CSS class is field--full spans both. --%>
<div id="$Name" class="project-form__section" role="group"<% if $Title %> aria-labelledby="{$Name}_heading"<% end_if %>>
    <% if $Title %>
    <div class="project-form__section-heading">
        <span aria-hidden="true"></span>
        <h2 id="{$Name}_heading">$Title</h2>
    </div>
    <% end_if %>
    <div class="quote-fields">
    <% loop $FieldList %>
        $FieldHolder
    <% end_loop %>
    </div>
</div>
