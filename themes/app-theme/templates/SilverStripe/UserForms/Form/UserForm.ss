<%-- The quote form in the markup of the Figma export (design/figma-export/quote.html, form.project-form). The theme's
     class replaces userforms' own ("userform"): its jQuery and stylesheet are switched off on the quote page
     (app/_config/userforms.yml), and the starter's form.userform styles would fight the Figma ones.
     A form-wide message (a refused spam check, moved here by UserFormRefusalExtension) shows above the sections.
     One page only: the quote form has no steps, and without userforms' script a multi-step form shows every step. --%>
<form $AttributesHTML('class') class="project-form">
<% if $Message %>
    <p id="{$FormName}_error" class="project-form__message message $MessageType" role="alert">$Message</p>
<% else %>
    <p id="{$FormName}_error" class="message $MessageType" aria-hidden="true" style="display: none;"></p>
<% end_if %>

<% include SilverStripe\\UserForms\\Form\\UserFormFields %>
<% include SilverStripe\\UserForms\\Form\\UserFormActionNav %>
</form>
