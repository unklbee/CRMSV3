<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceModel extends Model
{
    protected $table = 'services';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name', 'slug', 'description', 'detailed_description', 'icon', 'image',
        'category', 'price_min', 'price_max', 'price_display', 'duration',
        'features', 'requirements', 'warranty', 'seo_title', 'seo_description',
        'seo_keywords', 'is_featured', 'is_active', 'sort_order'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[255]',
        'slug' => 'required|alpha_dash|is_unique[services.slug,id,{id}]',
        'description' => 'required|min_length[50]',
        'category' => 'required|in_list[laptop,komputer,jaringan,cctv,software]',
        'price_min' => 'permit_empty|decimal',
        'price_max' => 'permit_empty|decimal'
    ];

    protected $beforeInsert = ['generateSlug'];
    protected $beforeUpdate = ['generateSlug'];

    /**
     * Get active services
     */
    public function getActive($limit = null)
    {
        $builder = $this->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC');

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Get featured services
     */
    public function getFeatured($limit = 6)
    {
        return $this->where('is_featured', 1)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get services by category
     */
    public function getByCategory($category)
    {
        return $this->where('category', $category)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }

    /**
     * Get service by slug
     */
    public function getBySlug($slug)
    {
        return $this->where('slug', $slug)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Get service categories
     */
    public function getCategories()
    {
        return [
            'laptop' => [
                'name' => 'Service Laptop',
                'icon' => 'fas fa-laptop',
                'description' => 'Perbaikan dan maintenance laptop semua merk'
            ],
            'komputer' => [
                'name' => 'Service Komputer',
                'icon' => 'fas fa-desktop',
                'description' => 'Service PC dan komputer desktop'
            ],
            'jaringan' => [
                'name' => 'Instalasi Jaringan',
                'icon' => 'fas fa-network-wired',
                'description' => 'Setup dan maintenance jaringan komputer'
            ],
            'cctv' => [
                'name' => 'Instalasi CCTV',
                'icon' => 'fas fa-video',
                'description' => 'Instalasi dan maintenance sistem CCTV'
            ],
            'software' => [
                'name' => 'Instalasi Software',
                'icon' => 'fas fa-download',
                'description' => 'Instalasi OS dan software aplikasi'
            ]
        ];
    }

    /**
     * Generate slug automatically
     */
    protected function generateSlug(array $data)
    {
        if (empty($data['data']['slug']) && !empty($data['data']['name'])) {
            $slug = url_title($data['data']['name'], '-', true);

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
     * Initialize default services
     */
    public function initializeDefaults()
    {
        $services = [
            [
                'name' => 'Service Laptop Gaming',
                'slug' => 'service-laptop-gaming',
                'description' => 'Spesialis perbaikan laptop gaming ASUS ROG, MSI, Alienware, HP Omen. Perbaikan overheating, upgrade RAM, SSD, cleaning cooling system.',
                'detailed_description' => '<p>Layanan service laptop gaming terlengkap di Bandung...</p>',
                'icon' => 'fas fa-gamepad',
                'category' => 'laptop',
                'price_min' => 150000,
                'price_max' => 500000,
                'price_display' => 'Mulai Rp 150.000',
                'duration' => '1-3 hari',
                'features' => json_encode([
                    'Cleaning cooling system',
                    'Repaste thermal',
                    'Upgrade hardware',
                    'Perbaikan VGA'
                ]),
                'warranty' => '30 hari',
                'seo_title' => 'Service Laptop Gaming Bandung - ASUS ROG, MSI, Alienware',
                'seo_description' => 'Service laptop gaming terbaik di Bandung. Spesialis ASUS ROG, MSI, Alienware. Perbaikan overheating, upgrade hardware. Garansi 30 hari.',
                'seo_keywords' => 'service laptop gaming bandung, service asus rog, service msi gaming, perbaikan laptop gaming',
                'is_featured' => 1,
                'is_active' => 1,
                'sort_order' => 1
            ],
            [
                'name' => 'Service Laptop Bisnis',
                'slug' => 'service-laptop-bisnis',
                'description' => 'Perbaikan laptop HP, Dell, Lenovo ThinkPad untuk kebutuhan bisnis. Service motherboard, LCD, keyboard, baterai dengan garansi.',
                'detailed_description' => '<p>Service laptop bisnis profesional...</p>',
                'icon' => 'fas fa-briefcase',
                'category' => 'laptop',
                'price_min' => 100000,
                'price_max' => 400000,
                'price_display' => 'Mulai Rp 100.000',
                'duration' => '2-4 hari',
                'features' => json_encode([
                    'Perbaikan motherboard',
                    'Ganti LCD/LED',
                    'Service keyboard',
                    'Ganti baterai'
                ]),
                'warranty' => '30 hari',
                'seo_title' => 'Service Laptop Bisnis Bandung - HP, Dell, Lenovo ThinkPad',
                'seo_description' => 'Service laptop bisnis HP, Dell, Lenovo ThinkPad di Bandung. Perbaikan motherboard, LCD, keyboard. Teknisi berpengalaman, garansi resmi.',
                'seo_keywords' => 'service laptop bisnis bandung, service hp bandung, service dell bandung, service lenovo thinkpad',
                'is_featured' => 1,
                'is_active' => 1,
                'sort_order' => 2
            ]
            // Add more services...
        ];

        foreach ($services as $service) {
            if (!$this->where('slug', $service['slug'])->first()) {
                $this->insert($service);
            }
        }
    }
}