<%-- Radio buttons as the Figma pill buttons (.pill-group / .choice-pill): four to a row on wider screens, three
     when the group has three options, two on a phone. --%>
<div class="pill-group<% if $Options.Count == 3 %> pill-group--three<% end_if %>">
<% loop $Options %>
    <label class="choice-pill" for="$ID"><input id="$ID" type="radio" name="$Name" value="$Value.ATT"<% if $isChecked %> checked<% end_if %><% if $isDisabled %> disabled<% end_if %><% if $Up.Required %> required<% end_if %>><span>$Title</span></label>
<% end_loop %>
</div>
