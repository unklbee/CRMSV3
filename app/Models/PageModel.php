<?php
// app/Models/PageModel.php

namespace App\Models;

use CodeIgniter\Model;

class PageModel extends Model
{
    protected $table = 'pages';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'title', 'slug', 'meta_title', 'meta_description', 'meta_keywords',
        'canonical_url', 'og_title', 'og_description', 'og_image',
        'content', 'excerpt', 'template', 'page_type', 'status', 'visibility',
        'password', 'featured', 'featured_image', 'parent_id', 'sort_order',
        'view_count', 'schema_markup', 'seo_settings', 'published_at',
        'author_id', 'editor_id'
    ];

    protected $useTimestamps = true;
    protected $deletedField = 'deleted_at';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $dateFormat = 'datetime';

    protected $validationRules = [
        'title' => 'required|min_length[3]|max_length[255]',
        'slug' => 'required|min_length[3]|max_length[255]|is_unique[pages.slug,id,{id}]|alpha_dash',
        'meta_title' => 'required|min_length[10]|max_length[70]',
        'meta_description' => 'required|min_length[50]|max_length[160]',
        'content' => 'required|min_length[100]',
        'page_type' => 'required|in_list[page,post,service,landing]',
        'status' => 'required|in_list[draft,published,scheduled,archived]',
        'visibility' => 'required|in_list[public,private,password]',
        'author_id' => 'required|integer'
    ];

    protected $validationMessages = [
        'meta_title' => [
            'min_length' => 'Meta title harus minimal 10 karakter untuk SEO',
            'max_length' => 'Meta title maksimal 70 karakter untuk SEO optimal'
        ],
        'meta_description' => [
            'min_length' => 'Meta description harus minimal 50 karakter',
            'max_length' => 'Meta description maksimal 160 karakter untuk SEO optimal'
        ],
        'slug' => [
            'alpha_dash' => 'Slug hanya boleh berisi huruf, angka, dash, dan underscore',
            'is_unique' => 'Slug sudah digunakan'
        ]
    ];

    protected $beforeInsert = ['generateSlug', 'setAuthor', 'optimizeSEO'];
    protected $beforeUpdate = ['generateSlug', 'setEditor', 'optimizeSEO'];

    /**
     * Get published pages
     */
    public function getPublished($limit = null, $type = null)
    {
        $builder = $this->where('status', 'published')
            ->where('visibility', 'public')
            ->where('published_at <=', date('Y-m-d H:i:s'))
            ->orderBy('published_at', 'DESC');

        if ($type) {
            $builder->where('page_type', $type);
        }

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Get featured pages
     */
    public function getFeatured($limit = 6)
    {
        return $this->where('featured', 1)
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->orderBy('sort_order', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get page by slug with SEO data
     */
    public function getBySlug($slug)
    {
        $page = $this->where('slug', $slug)
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->first();

        if ($page) {
            // Increment view count
            $this->update($page['id'], ['view_count' => $page['view_count'] + 1]);

            // Get related keywords
            $page['keywords'] = $this->getPageKeywords($page['id']);
        }

        return $page;
    }

    /**
     * Get page keywords
     */
    public function getPageKeywords($pageId)
    {
        $db = \Config\Database::connect();
        return $db->table('page_keywords pk')
            ->select('k.keyword, pk.keyword_type, pk.density')
            ->join('keywords k', 'k.id = pk.keyword_id')
            ->where('pk.page_id', $pageId)
            ->orderBy('pk.keyword_type', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Search pages
     */
    public function search($term, $type = null)
    {
        $builder = $this->select('id, title, slug, excerpt, meta_description, published_at')
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->groupStart()
            ->like('title', $term)
            ->orLike('content', $term)
            ->orLike('meta_keywords', $term)
            ->orLike('excerpt', $term)
            ->groupEnd();

        if ($type) {
            $builder->where('page_type', $type);
        }

        return $builder->orderBy('published_at', 'DESC')->findAll();
    }

    /**
     * Get sitemap data
     */
    public function getSitemapData()
    {
        return $this->select('slug, updated_at, page_type')
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->orderBy('updated_at', 'DESC')
            ->findAll();
    }

    /**
     * Generate slug automatically
     */
    protected function generateSlug(array $data)
    {
        if (empty($data['data']['slug']) && !empty($data['data']['title'])) {
            $slug = url_title($data['data']['title'], '-', true);

            // Check for uniqueness
            $count = 1;
            $originalSlug = $slug;
            while ($this->where('slug', $slug)->first()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $data['data']['slug'] = $slug;
        }
        return $data;
    }

    /**
     * Set author on insert
     */
    protected function setAuthor(array $data)
    {
        if (empty($data['data']['author_id'])) {
            $session = session();
            $data['data']['author_id'] = $session->get('user_id') ?? 1;
        }
        return $data;
    }

    /**
     * Set editor on update
     */
    protected function setEditor(array $data)
    {
        $session = session();
        $data['data']['editor_id'] = $session->get('user_id') ?? 1;
        return $data;
    }

    /**
     * Optimize SEO fields
     */
    protected function optimizeSEO(array $data)
    {
        // Auto-generate meta title if empty
        if (empty($data['data']['meta_title']) && !empty($data['data']['title'])) {
            $data['data']['meta_title'] = substr($data['data']['title'], 0, 70);
        }

        // Auto-generate meta description if empty
        if (empty($data['data']['meta_description']) && !empty($data['data']['excerpt'])) {
            $data['data']['meta_description'] = substr(strip_tags($data['data']['excerpt']), 0, 160);
        }

        // Auto-generate OG title
        if (empty($data['data']['og_title'])) {
            $data['data']['og_title'] = $data['data']['meta_title'] ?? $data['data']['title'];
        }

        // Auto-generate OG description
        if (empty($data['data']['og_description'])) {
            $data['data']['og_description'] = $data['data']['meta_description'];
        }

        return $data;
    }

    /**
     * Get SEO analysis for page
     */
    public function getSEOAnalysis($pageId)
    {
        $page = $this->find($pageId);
        if (!$page) return [];

        $analysis = [
            'title_length' => strlen($page['meta_title']),
            'description_length' => strlen($page['meta_description']),
            'content_length' => strlen(strip_tags($page['content'])),
            'keyword_density' => [],
            'readability_score' => 0,
            'seo_score' => 0
        ];

        // Calculate keyword density
        $keywords = $this->getPageKeywords($pageId);
        $content = strtolower(strip_tags($page['content']));
        $wordCount = str_word_count($content);

        foreach ($keywords as $keyword) {
            $keywordCount = substr_count($content, strtolower($keyword['keyword']));
            $density = $wordCount > 0 ? ($keywordCount / $wordCount) * 100 : 0;
            $analysis['keyword_density'][$keyword['keyword']] = round($density, 2);
        }

        // Calculate basic SEO score
        $score = 0;
        if ($analysis['title_length'] >= 30 && $analysis['title_length'] <= 70) $score += 20;
        if ($analysis['description_length'] >= 120 && $analysis['description_length'] <= 160) $score += 20;
        if ($analysis['content_length'] >= 300) $score += 20;
        if (!empty($page['featured_image'])) $score += 10;
        if (!empty($page['meta_keywords'])) $score += 10;
        if (!empty($page['canonical_url'])) $score += 10;
        if (!empty($page['schema_markup'])) $score += 10;

        $analysis['seo_score'] = $score;

        return $analysis;
    }
}