<% if $ElementControllers %>
    <% loop $ElementControllers %>
	   <section class="block block-{$ID} $CssLabel<% if $StyleVariant %> $StyleVariant<% end_if %><% if $ExtraClass %> $ExtraClass<% end_if %> $FirstLast $PaddingTop $PaddingBottom" <% if $Element.BackgroundImage && $StyleVariant=='bg-img' %>style="background-image:url('$Element.BackgroundImage.URL')"<% end_if %>
        >
        $Me
       </section>
    <% end_loop %>
<% end_if %>
