<div class="wrap">
  <% if $Title && $ShowTitle || $HTML %>
  <div class="block-intro">
    <% if $Title && $ShowTitle %><h2>$Title</h2><% end_if %>
    <% if $HTML %><div class="typography">$HTML</div><% end_if %>
  </div>
  <% end_if %>
  <div class="person-list">
    <% loop $People %>
    <div class="person-row">
      <% if $Headshot %>
      <div class="person-row__img">
        <a href="$Link"><img src="$Headshot.Fill(302,383).URL" alt="$Title" loading="lazy"></a>
      </div>
      <% end_if %>
      <div class="person-row__body">
        <h3><a href="$Link">$Title</a></h3>
        <% if $JobTitle %><p class="person-row__jobtitle">$JobTitle</p><% end_if %>
        <% if $Content %><p>$Content.Summary</p><% end_if %>
        <a href="$Link" class="text-link">More..</a>
      </div>
    </div>
    <% end_loop %>
  </div>
</div>
