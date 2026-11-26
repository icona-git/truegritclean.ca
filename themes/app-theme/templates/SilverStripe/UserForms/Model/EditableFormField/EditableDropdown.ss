<select $AttributesHTML('class') class="field__control $extraClass"<% if $RightTitle || $Message %> aria-describedby="<% if $RightTitle %>{$Name}_right_title<% end_if %><% if $Message %> {$Name}_message<% end_if %>"<% end_if %><% if $Message %> aria-invalid="true"<% end_if %>>
<% loop $Options %>
    <option value="$Value.XML"<% if $Selected %> selected="selected"<% end_if %><% if $Disabled %> disabled="disabled"<% end_if %>>$Title.XML</option>
<% end_loop %>
</select>
