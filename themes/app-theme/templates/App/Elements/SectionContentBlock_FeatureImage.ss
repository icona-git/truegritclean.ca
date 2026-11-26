<%-- Full-width photo between sections (Figma service.html .service-image): the block's Background Image, cropped
     21:9 on wider screens and 8:5 on phones. Its alt text is the image's Title in the Files area. No image, no
     output. Greyscale comes from the CSS. --%>
<% if $BgImage.exists %>
<figure class="service-image">
    <picture>
        <source media="(min-width: 42rem)" srcset="$BgImage.Fill(1920,823).URL">
        <img src="$BgImage.Fill(800,500).URL" width="800" height="500" alt="$BgImage.Title.ATT" loading="lazy" decoding="async">
    </picture>
</figure>
<% end_if %>
