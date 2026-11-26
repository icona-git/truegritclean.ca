<%-- FAQ accordion (Figma .faq): <details> rows, no JavaScript; the first answer starts open. Only items whose
     claims are confirmed show ($ShownBuckets), the same list the FAQPage schema reads; with none left, the section
     is not rendered. --%>
<% if $ShownBuckets.Count %>
<div class="section section--light">
    <div class="section-shell section-shell--narrow">
        <% include App\Includes\SectionHeading Tone='dark' %>
        <div class="faq">
            <% loop $ShownBuckets %>
            <details class="faq__item"<% if $IsFirst %> open<% end_if %>>
                <summary class="faq__summary">$Title</summary>
                <div class="faq__content">$HTML</div>
            </details>
            <% end_loop %>
        </div>
    </div>
</div>
<% end_if %>
