<% include App\Includes\Header %>

<%-- ── Article body ────────────────────────────────────────────────────────── --%>
<main id="main-content" tabindex="-1">
  <article class="post-article wrap">
    <div class="post-article__inner">

      <div class="post-article__body">

        $Breadcrumbs

        <div class="post-article__head">
          <p class="post-article__meta">
            <time datetime="$PublishDate.Format('yyyy-MM-dd')">$PublishDate.Format('MMM d, y')</time>
            <% if $Categories %>
            <span class="post-hero__cats">
              <% loop $Categories %><a href="$Link">$Title</a><% if not $Last %>, <% end_if %><% end_loop %>
            </span>
            <% end_if %>
          </p>
          <h1 class="post-article__title">$Title</h1>
        </div>

        <div class="typography">
          <% if $FeaturedImage %>
          <div class="post-article__img">
            <img src="$FeaturedImage.Fill(600,600).URL" alt="$Title" loading="eager">
          </div>
          <% end_if %>

          $Content

          <% if $DownloadableFile %>
          <p><a href="$DownloadableFile.URL" target="_blank">Download File</a></p>
          <% end_if %>

          <% if $ExternalLink %>
          <% with $ExternalLink %>
          <p><a href="$URL"<% if $OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$Title</a></p>
          <% end_with %>
          <% end_if %>
        </div>

        <% if $Tags %>
        <div class="post-tags">
          <% loop $Tags %><a href="$Link" class="post-tag">$Title</a><% end_loop %>
        </div>
        <% end_if %>

        <% if $Parent.Disclaimer %>
        <div class="post-disclaimer">
          <strong>Disclaimer</strong> $Parent.Disclaimer
        </div>
        <% end_if %>
      </div>

      <%-- ── Sidebar ──────────────────────────────────────────────────── --%>
      <% if $Parent.Categories %>
      <aside class="blog-sidebar">
        <div class="blog-sidebar__head">
          <p>Categories</p>
        </div>
        <ul class="blog-sidebar__categories">
          <li><a href="$Parent.Link">All Articles</a></li>
          <% loop $Parent.Categories %>
          <li><a href="$Link">$Title</a></li>
          <% end_loop %>
        </ul>
      </aside>
      <% end_if %>
    </div>
    <%-- Prev / next post navigation --%>
        <% if $PreviousPost || $NextPost %>
        <nav class="post-nav" aria-label="Post navigation">
          <div class="post-nav__prev">
            <% if $PreviousPost %>
            <% with $PreviousPost %>
            <a href="$Link">
              <span class="post-nav__label">&larr; Previous Article</span>
              <span class="post-nav__title">$Title</span>
            </a>
            <% end_with %>
            <% end_if %>
          </div>
          <div class="post-nav__next">
            <% if $NextPost %>
            <% with $NextPost %>
            <a href="$Link">
              <span class="post-nav__label">Next Article &rarr;</span>
              <span class="post-nav__title">$Title</span>
            </a>
            <% end_with %>
            <% end_if %>
          </div>
        </nav>
        <% end_if %>
  </article>

  $ElementalArea
</main>
<% include App\Includes\BookingBanner %>
<% include App\Includes\Footer %>
