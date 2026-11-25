<% loop $Options %>
    <div class="$Class">
        <label for="$ID" class="control control--radio">$Title
            <input id="$ID" type="radio" name="$Name" value="$Value.ATT"<% if $isChecked %>
               checked<% end_if %><% if $isDisabled %> disabled<% end_if %> <% if $Up.Required %>required<% end_if %>/>
            <div class="control__indicator"></div>
        </label>
    </div>
<% end_loop %>
