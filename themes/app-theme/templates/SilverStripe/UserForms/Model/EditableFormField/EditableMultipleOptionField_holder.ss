<%-- A radio group (Figma fieldset.field with a legend over the pill buttons). The pills are
     FormField/UserFormsOptionSetField.ss. --%>
<fieldset id="$Name" class="field<% if $extraClass %> $extraClass<% end_if %><% if $Message %> field--error<% end_if %>"<% if $RightTitle || $Message %> aria-describedby="<% if $RightTitle %>{$Name}_right_title<% end_if %><% if $Message %> {$Name}_message<% end_if %>"<% end_if %>>
    <% if $Title %><legend class="field__label">$Title</legend><% end_if %>
    $Field
    <% if $RightTitle %><small id="{$Name}_right_title" class="field__helper">$RightTitle</small><% end_if %>
    <% if $Message %><small id="{$Name}_message" class="field__error message $MessageType">$Message</small><% end_if %>
</fieldset>
