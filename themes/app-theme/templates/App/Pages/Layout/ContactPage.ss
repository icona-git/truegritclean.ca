<% include App\Includes\Header %>

<main id="main-content" tabindex="-1">
  <%-- The quote page in the layout of the Figma export (design/figma-export/quote.html): breadcrumb strip and hero,
       then the form with the reassurance panel beside it (below it on a phone) and the page's other blocks (the FAQ)
       under the form. --%>
  $Breadcrumbs
  <% include App\Includes\PageHero Variant='quote', TextClass='text' %>

  <section class="quote-layout-section" aria-label="Project quote request">
    <div class="section-shell quote-layout">
      <div class="quote-layout__main">
        <%-- ContactPage extends UserDefinedForm: $Form is the form built in the CMS (Form Fields tab). After a submission
             userforms sends the visitor to /quote/finished, where $Form is empty and $Content holds the On complete
             message; finished_anchor (app/_config/userforms.yml) lands them on this panel. --%>
        <% if $Form %>
          $Form
        <% else %>
          <section class="quote-success" id="quote-success" tabindex="-1" aria-live="polite">
            <span class="quote-success__icon" aria-hidden="true">&#10003;</span>
            $Content
          </section>
        <% end_if %>

        <%-- One grid cell (Figma .quote-faq) for every block under the form, so a second block stacks below the FAQ
             instead of landing in the same cell. --%>
        <% if $QuoteMainBlocks.Count %>
          <div class="quote-faq">
            <% loop $QuoteMainBlocks %>$Me<% end_loop %>
          </div>
        <% end_if %>
      </div>

      <%-- Blocks in the Reassurance panel style (BucketsBlock_Assurance) sit beside the form. --%>
      <% loop $QuotePanelBlocks %>
        $Me
      <% end_loop %>
    </div>
  </section>

</main>

<% include App\Includes\Footer %>
