<%-- One form field (Figma .field): label above the control, helper text (the field's Right title in the CMS) and any
     error below it. Holder classes from the CMS field's "Extra CSS class" ride along, e.g. field--full (full width). --%>
<div id="$Name" class="field<% if $extraClass %> $extraClass<% end_if %><% if $Message %> field--error<% end_if %>">
    <% if $Title %><label class="field__label" for="$ID">$Title</label><% end_if %>
    $Field
    <% if $RightTitle %><small id="{$Name}_right_title" class="field__helper">$RightTitle</small><% end_if %>
    <% if $Message %><small id="{$Name}_message" class="field__error message $MessageType">$Message</small><% end_if %>
</div>
