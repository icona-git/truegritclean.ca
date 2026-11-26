<%-- The quote form's photo field (App\Forms\PhotosUploadField) in the Figma markup: the drop zone, the helper text
     (the field's Right title in the CMS) and, once photos are chosen, a preview of each (js/src/photos.js fills
     .upload-previews; the export's three sample previews were for review only). The section heading above it carries
     the field's name, so the field has no label of its own: the drop zone is the input's label. --%>
<div id="$Name" class="field<% if $extraClass %> $extraClass<% end_if %><% if $Message %> field--error<% end_if %>">
    $Field
    <% if $RightTitle %><p id="{$Name}_right_title" class="field__helper">$RightTitle</p><% end_if %>
    <p id="{$Name}_status" class="field__error quote-upload__status" role="alert"<% if not $Message %> hidden<% end_if %>><% if $Message %>$Message<% end_if %></p>
    <div class="upload-previews" id="{$Name}_previews" hidden></div>
</div>
