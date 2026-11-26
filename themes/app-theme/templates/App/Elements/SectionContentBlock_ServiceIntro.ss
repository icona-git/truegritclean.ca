<%-- Opening paragraph of a service page (Figma service.html .service-intro): large type in a narrow column. --%>
<div class="section section--light service-intro">
    <div class="section-shell section-shell--narrow">
        <% include App\Includes\SectionHeading Tone='dark' %>
        <% if $HTML %><div class="service-intro__text">$HTML</div><% end_if %>
    </div>
</div>
