<%-- Exclusions box (Figma service.html .service-callout): charcoal panel with a yellow edge on a grey band. The
     block title is the panel heading; the block text (paragraphs and lists) goes under it. --%>
<div class="service-callout-section">
    <div class="section-shell section-shell--narrow">
        <aside class="service-callout"<% if $ShowTitle %><% if $Title %> aria-labelledby="callout-$ID"<% end_if %><% end_if %>>
            <% if $ShowTitle %><% if $Title %><h2 class="service-callout__title" id="callout-$ID">$Title</h2><% end_if %><% end_if %>
            <% if $HTML %><div class="service-callout__body">$HTML</div><% end_if %>
        </aside>
    </div>
</div>
