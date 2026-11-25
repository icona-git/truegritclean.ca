<% include App\Includes\Header %>
<% include App\Includes\Hero %>
<% include App\Includes\BreadcrumbBar %>

<main id="main-content" tabindex="-1">
    <section>
        <div class="wrap blog-layout">

            <%-- ── Sidebar: logo bleed area + category nav ──────────────────── --%>
            <aside class="blog-layout__sidebar">
                <nav class="blog-categories" aria-label="Post categories">
                    <ul>
                        <li class="<% if not $CurrentCategory %>current<% end_if %>">
                            <a href="$Link">All</a>
                        </li>
                        <% loop $Categories %>
                        <li class="$LinkingMode">
                            <a href="$Link">$Title</a>
                        </li>
                        <% end_loop %>
                    </ul>
                </nav>
            </aside>

            <%-- ── Post list ─────────────────────────────────────────────────── --%>
            <div class="blog-layout__posts">

                <% if $CurrentCategory %>
                <p class="blog-filter-label">Articles in: <strong>$CurrentCategory.Title</strong></p>
                <% end_if %>

                <% loop $PaginatedList %>
                <article class="blog-post-item">
                    <p class="blog-post-item__meta">$PublishDate.Format('MMMM d, y')</p>
                    <% if $Categories.Count %>
                    <p class="blog-post-item__cat">$Categories.First.Title</p>
                    <% end_if %>
                    <h2 class="blog-post-item__title"><a href="$Link">$Title</a></h2>
                    <% if $Summary %>
                    <p class="blog-post-item__excerpt">$Summary</p>
                    <% end_if %>
                    <a href="$Link" class="blog-post-item__more">Read More</a>
                </article>
                <% end_loop %>

                <% if $PaginatedList.MoreThanOnePage %>
                <nav aria-label="Post pagination" class="pagination-wrap">
                    <ul class="pagination">
                        <% if $PaginatedList.NotFirstPage %>
                        <li class="page-item">
                            <a class="page-link" href="$PaginatedList.PrevLink">&laquo; Prev</a>
                        </li>
                        <% end_if %>
                        <% loop $PaginatedList.PaginationSummary(6) %>
                        <% if $CurrentBool %>
                        <li class="page-item active"><a class="page-link" href="#">$PageNum</a></li>
                        <% else %>
                        <% if $Link %>
                        <li class="page-item"><a class="page-link" href="$Link">$PageNum</a></li>
                        <% else %>
                        <li class="page-item"><span class="page-link">&hellip;</span></li>
                        <% end_if %>
                        <% end_if %>
                        <% end_loop %>
                        <% if $PaginatedList.NotLastPage %>
                        <li class="page-item">
                            <a class="page-link" href="$PaginatedList.NextLink">Next &raquo;</a>
                        </li>
                        <% end_if %>
                    </ul>
                </nav>
                <% end_if %>

            </div>
        </div>
    </section>

    $ElementalArea
</main>
<% include App\Includes\BookingBanner %>
<% include App\Includes\Footer %>
