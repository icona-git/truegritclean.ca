<%-- Submit area (Figma .project-form__submit): the button (its text is the page's Submit button text, in the Form
     Options tab), then the page's "Text under the button" (Main tab): the same-day line and the privacy line. --%>
<% if $Actions %>
<div class="project-form__submit">
    <% loop $Actions %>
        $Field
    <% end_loop %>
    <% if $Controller.Content %><div class="project-form__note">$Controller.Content</div><% end_if %>
</div>
<% end_if %>
