<div class="wrap">
  <div class="faq__inner">
    <% if $Title && $ShowTitle %>
    <h2 class="faq__heading">$Title</h2>
    <% end_if %>
    <div class="faq-list">
      <% loop $Buckets %>
      <div class="faq-item">
        <button class="faq-item__question" data-bs-toggle="collapse" data-bs-target="#faq-$ID" aria-expanded="false" aria-controls="faq-$ID">
          $Title
          <span class="faq-item__chevron" aria-hidden="true"></span>
        </button>
        <div class="collapse faq-item__answer" id="faq-$ID">
          <div class="faq-item__answer-inner">$HTML</div>
        </div>
      </div>
      <% end_loop %>
    </div>
  </div>
</div>
