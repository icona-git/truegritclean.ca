<%-- A block's heading, as the Figma sections mark it up: the optional Eyebrow (small capitals line) above the
     block title. Tone is 'dark' on white and grey sections, 'light' on charcoal ones (eyebrow colour). An empty
     Eyebrow and an unticked "Show title" render nothing. --%>
<% if $Eyebrow || $ShowTitle %>
<div class="section-heading">
    <% if $Eyebrow %><p class="eyebrow eyebrow--{$Tone}">$Eyebrow</p><% end_if %>
    <% if $ShowTitle %><% if $Title %><h2>$Title</h2><% end_if %><% end_if %>
</div>
<% end_if %>
