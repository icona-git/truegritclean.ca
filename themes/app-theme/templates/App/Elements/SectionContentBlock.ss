<%-- No Block Style chosen: a plain white section in the Figma markup, with the eyebrow, title and the block's text
     (headings, lists and tables styled by .typography) and the optional link as a text link. The Services page's
     phase table and packages paragraph use it. --%>
<div class="section section--light">
    <div class="section-shell">
        <% include App\Includes\SectionHeading Tone='dark' %>
        <% if $HTML %><div class="typography section-text">$HTML</div><% end_if %>
        <% if $CtaLink %><a class="text-link" href="$CtaLink.URL"<% if $CtaLink.OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$CtaLink.Title <span aria-hidden="true">&rarr;</span></a><% end_if %>
    </div>
</div>
