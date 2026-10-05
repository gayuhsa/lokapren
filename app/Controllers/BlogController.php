<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\BlogCategoryModel;
use App\Models\BlogPostModel;

/**
 * Public journal ("Cerita"), covering both lokapren stories and craft guides.
 *
 * Reads only. Drafts and pending posts are filtered in the query rather than
 * after it, so a guest can never reach an unpublished article by guessing an id.
 */
class BlogController extends BaseController
{
    private const PER_PAGE = 9;

    public function index()
    {
        $page   = max(1, (int) $this->request->getGet('page'));
        $offset = ($page - 1) * self::PER_PAGE;

        $posts       = new BlogPostModel();
        $categorySlug = trim((string) $this->request->getGet('category'));

        $builder = $this->listing($posts, $categorySlug)
            ->orderBy('blog_posts.published_at', 'DESC');

        $rows = $posts->newRows($builder->limit(self::PER_PAGE, $offset));

        // The count runs on its own model: calling `newQuery()` again on the
        // same instance would reset the shared builder the row query is holding.
        $counter = new BlogPostModel();
        $total   = $this->listing($counter, $categorySlug)->countAllResults();

        return view('blog/index', [
            'title'      => 'Cerita & Panduan',
            'posts'      => $rows,
            // Alphabetical: `blog_categories` has no manual ordering column
            // and there is no interface to reorder the list, so the sort must
            // be derivable from the data itself.
            'categories' => (new BlogCategoryModel())->orderBy('name', 'ASC')->findAll(),
            'page'       => $page,
            'pages'      => (int) max(1, ceil($total / self::PER_PAGE)),
            'total'      => $total,
            'category'   => $categorySlug,
            'cart_count' => $this->cartCount(),
        ]);
    }

    public function show(int $id)
    {
        $posts = new BlogPostModel();

        $post = $posts->newRow($posts->newQuery()
            ->select('blog_posts.*, blog_categories.name AS category_name, blog_categories.slug AS category_slug, users.username AS author_username, seller_profiles.display_name AS shop_name, seller_profiles.slug AS shop_slug, seller_profiles.logo_path AS shop_logo')
            ->join('blog_categories', 'blog_categories.id = blog_posts.category_id', 'left')
            ->join('users', 'users.id = blog_posts.seller_id', 'left')
            ->join('seller_profiles', 'seller_profiles.user_id = blog_posts.seller_id', 'left')
            ->where('blog_posts.status', BlogPostModel::STATUS_PUBLISHED)
            ->where('blog_posts.id', $id)
        );

        if ($post === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $posts->recordView((int) $post['id']);

        // Related reading comes from a separate model so the `recordView()`
        // update above cannot disturb the query that filled this page.
        $related = new BlogPostModel();

        return view('blog/show', [
            'title'      => $post['title'],
            'post'       => $post,
            'related'    => $related->newRows(
                $related->newQuery()
                    ->select('blog_posts.*, blog_categories.name AS category_name')
                    ->join('blog_categories', 'blog_categories.id = blog_posts.category_id', 'left')
                    ->where('blog_posts.status', BlogPostModel::STATUS_PUBLISHED)
                    ->where('blog_posts.id !=', $post['id'])
                    ->where('blog_posts.category_id', $post['category_id'])
                    ->orderBy('blog_posts.published_at', 'DESC'),
                3
            ),
            'cart_count' => $this->cartCount(),
        ]);
    }

    /**
     * The published-post query, optionally narrowed to one category.
     *
     * Shared by the row query and the count so both agree on which posts exist.
     */
    private function listing(BlogPostModel $posts, string $categorySlug): \CodeIgniter\Database\BaseBuilder
    {
        $builder = $posts->newQuery()
            ->select('blog_posts.*, blog_categories.name AS category_name, blog_categories.slug AS category_slug, seller_profiles.display_name AS shop_name, seller_profiles.slug AS shop_slug, seller_profiles.logo_path AS shop_logo')
            ->join('blog_categories', 'blog_categories.id = blog_posts.category_id', 'left')
            ->join('seller_profiles', 'seller_profiles.user_id = blog_posts.seller_id', 'left')
            ->where('blog_posts.status', BlogPostModel::STATUS_PUBLISHED);

        if ($categorySlug !== '') {
            $builder->where('blog_categories.slug', $categorySlug);
        }

        return $builder;
    }
}
