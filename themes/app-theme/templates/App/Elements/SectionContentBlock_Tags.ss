<%-- A heading and a short list shown as tags (the Figma .industry-tags, on grey): the bulleted list in the block's
     text becomes the tags. Used for "Renovations we clean after". --%>
<div class="section section--grey tags-block">
    <div class="section-shell">
        <% include App\Includes\SectionHeading Tone='dark' %>
        <% if $HTML %><div class="tags-block__list">$HTML</div><% end_if %>
    </div>
</div>
