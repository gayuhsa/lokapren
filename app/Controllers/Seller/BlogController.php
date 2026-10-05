<?php

declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Controllers\BaseController;
use App\Models\BlogCategoryModel;
use App\Models\BlogPostModel;

/**
 * Seller blog management.
 *
 * AGENTS.md keeps seller content attached directly to the seller, so posts are
 * scoped by `seller_id` on every read and write. `author_id`, `status` and
 * `view_count` are never taken from the request.
 */
class BlogController extends BaseController
{
    public function index()
    {
        $sellerId = $this->requireUserId();
        $posts    = new BlogPostModel();

        return view('seller/blogs/index', [
            'title'      => 'Cerita Saya',
            'posts'      => $posts->ownedBy($sellerId)->orderBy('updated_at', 'DESC')->findAll(),
            'cart_count' => $this->cartCount(),
        ]);
    }

    public function create()
    {
        return view('seller/blogs/form', [
            'title'      => 'Tulis Cerita',
            'post'       => null,
            'categories' => $this->categories(),
            'action'     => route_to('seller_blog_store'),
            'cart_count' => $this->cartCount(),
        ]);
    }

    public function edit(int $id)
    {
        $sellerId = $this->requireUserId();
        $post     = (new BlogPostModel())->findOwnedBy($id, $sellerId);

        if ($post === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('seller/blogs/form', [
            'title'      => 'Ubah Cerita',
            'post'       => $post,
            'categories' => $this->categories(),
            'action'     => route_to('seller_blog_update', $id),
            'cart_count' => $this->cartCount(),
        ]);
    }

    public function store()
    {
        $sellerId = $this->requireUserId();
        $data     = $this->validated();

        if ($data === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $posts = new BlogPostModel();
        $now   = date('Y-m-d H:i:s');

        $postId = $posts->insertRow([
            // The owner comes from the session, never the form.
            'seller_id'  => $sellerId,
            'category_id' => $data['category_id'],
            'title'      => $data['title'],
            'slug'       => $this->uniqueSlug($this->slugify($data['title'])),
            'excerpt'    => $data['excerpt'],
            'body'       => $data['body'],
            'cover_path' => $data['cover_path'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // A draft has no `published_at`; the public blog filters on status, so
        // an unpublished post is invisible either way.
        $status = (string) $this->request->getPost('status');

        if ($status === BlogPostModel::STATUS_PUBLISHED) {
            $posts->publish($postId, $sellerId);
        }

        return redirect()
            ->route('seller_blogs')
            ->with('success', 'Cerita tersimpan.');
    }

    public function update(int $id)
    {
        $sellerId = $this->requireUserId();
        $posts    = new BlogPostModel();

        if ($posts->findOwnedBy($id, $sellerId) === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = $this->validated();

        if ($data === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $posts->updateWhere([
            'category_id' => $data['category_id'],
            'title'       => $data['title'],
            'excerpt'     => $data['excerpt'],
            'body'        => $data['body'],
            'cover_path'  => $data['cover_path'],
            'updated_at'  => date('Y-m-d H:i:s'),
        ], ['id' => $id, 'seller_id' => $sellerId]);

        $status = (string) $this->request->getPost('status');

        if ($status === BlogPostModel::STATUS_PUBLISHED) {
            $posts->publish($id, $sellerId);
        } elseif ($status === BlogPostModel::STATUS_DRAFT) {
            $posts->updateWhere(
                ['status' => BlogPostModel::STATUS_DRAFT, 'is_published' => 0],
                ['id' => $id, 'seller_id' => $sellerId]
            );
        }

        return redirect()
            ->route('seller_blogs')
            ->with('success', 'Cerita diperbarui.');
    }

    public function destroy(int $id)
    {
        $sellerId = $this->requireUserId();
        $posts    = new BlogPostModel();

        if ($posts->findOwnedBy($id, $sellerId) === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $posts->updateWhere(
            ['deleted_at' => date('Y-m-d H:i:s')],
            ['id' => $id, 'seller_id' => $sellerId]
        );

        return redirect()
            ->route('seller_blogs')
            ->with('success', 'Cerita dihapus.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categories(): array
    {
        return (new BlogCategoryModel())->orderBy('name', 'ASC')->findAll();
    }

    /**
     * Validate the post form.
     *
     * @return array<string, mixed>|null
     */
    private function validated(): ?array
    {
        $rules = [
            'title'       => 'required|max_length[200]',
            'excerpt'     => 'permit_empty|max_length[500]',
            'body'        => 'permit_empty|max_length[20000]',
            'category_id' => 'permit_empty|is_natural_no_zero',
            'cover_path'  => 'permit_empty|max_length[255]',
            'status'      => 'permit_empty|in_list[draft,published]',
        ];

        if (! $this->validate($rules)) {
            return null;
        }

        $categoryId = $this->request->getPost('category_id');

        return [
            'title'       => trim((string) $this->request->getPost('title')),
            'excerpt'     => trim((string) $this->request->getPost('excerpt')) ?: null,
            'body'        => trim((string) $this->request->getPost('body')) ?: null,
            'category_id' => ($categoryId === '' || $categoryId === null) ? null : (int) $categoryId,
            'cover_path'  => trim((string) $this->request->getPost('cover_path')) ?: null,
        ];
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-') ?: 'cerita';
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $n    = 2;

        while ((new BlogPostModel())->newRow(
            (new BlogPostModel())->newQuery()->where('slug', $slug)
        ) !== null) {
            $slug = $base . '-' . $n;
            $n++;
        }

        return $slug;
    }
}
