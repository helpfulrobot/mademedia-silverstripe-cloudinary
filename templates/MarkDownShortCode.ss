<figure class="unit-media page-image" itemscope itemtype="http://schema.org/ImageObject">
    <div class="media">
        <picture>
            <% if $height && $width %>
                <img srcset="$FillImage($height, $width).Link" alt="$alt" />
            <% else %>
                <img srcset="$Link" alt="$alt"/>
            <% end_if %>
        </picture>
    </div>
    <figcaption class="meta">
        <% if $Credit %><span itemprop="credit" class="credit">$Credit</span><% end_if %>
        <% if $Caption %><span itemprop="caption" class="caption">$Caption</span><% end_if %>
        <meta itemprop="datePublished" content="$Created.Rfc2822">
    </figcaption>
</figure>