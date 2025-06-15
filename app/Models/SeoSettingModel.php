<?php
// app/Models/SeoSettingModel.php

namespace App\Models;

use CodeIgniter\Model;

class SeoSettingModel extends Model
{
    protected $table = 'seo_settings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'setting_key', 'setting_value', 'setting_type', 'category', 'description', 'is_editable'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Cache for settings
    private static $settingsCache = [];

    /**
     * Get setting value by key
     */
    public function getSetting($key, $default = null)
    {
        if (isset(self::$settingsCache[$key])) {
            return self::$settingsCache[$key];
        }

        $setting = $this->where('setting_key', $key)->first();

        if (!$setting) {
            return $default;
        }

        $value = $this->parseSettingValue($setting['setting_value'], $setting['setting_type']);
        self::$settingsCache[$key] = $value;

        return $value;
    }

    /**
     * Set setting value
     */
    public function setSetting($key, $value, $type = 'text')
    {
        $data = [
            'setting_key' => $key,
            'setting_value' => is_array($value) ? json_encode($value) : $value,
            'setting_type' => $type
        ];

        $existing = $this->where('setting_key', $key)->first();

        if ($existing) {
            $result = $this->update($existing['id'], $data);
        } else {
            $result = $this->insert($data);
        }

        // Clear cache
        if (isset(self::$settingsCache[$key])) {
            unset(self::$settingsCache[$key]);
        }

        return $result;
    }

    /**
     * Get all settings by category
     */
    public function getByCategory($category)
    {
        $settings = $this->where('category', $category)->findAll();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $this->parseSettingValue(
                $setting['setting_value'],
                $setting['setting_type']
            );
        }

        return $result;
    }

    /**
     * Parse setting value by type
     */
    private function parseSettingValue($value, $type)
    {
        switch ($type) {
            case 'json':
                return json_decode($value, true) ?? [];
            case 'boolean':
                return (bool)$value;
            case 'number':
                return is_numeric($value) ? (float)$value : 0;
            default:
                return $value;
        }
    }

    /**
     * Initialize default SEO settings
     */
    public function initializeDefaults()
    {
        $defaults = [
            // General SEO
            'site_name' => ['GawaiKita - Service Laptop Bandung', 'text', 'general'],
            'site_description' => ['Service laptop terbaik di Bandung dengan teknisi berpengalaman', 'textarea', 'general'],
            'default_meta_title' => ['Service Laptop Bandung Terbaik & Terpercaya | GawaiKita', 'text', 'general'],
            'default_meta_description' => ['Service laptop Bandung terbaik ⭐ Ahli perbaikan laptop gaming ASUS, HP, Lenovo, Dell, Acer. Garansi 30 hari, teknisi berpengalaman.', 'textarea', 'general'],
            'canonical_domain' => ['https://www.gawaikita.com', 'text', 'general'],

            // Contact Info
            'business_name' => ['GawaiKita - Service Laptop Bandung', 'text', 'contact'],
            'business_phone' => ['(022) 123-4567', 'text', 'contact'],
            'business_whatsapp' => ['6282123456789', 'text', 'contact'],
            'business_email' => ['info@gawaikita.com', 'text', 'contact'],
            'business_address' => ['Jl. Sudirman No. 123, Bandung 40123', 'text', 'contact'],
            'business_hours' => ['Senin - Sabtu: 08:00 - 18:00 WIB', 'text', 'contact'],

            // Social Media
            'facebook_url' => ['', 'text', 'social'],
            'instagram_url' => ['', 'text', 'social'],
            'youtube_url' => ['', 'text', 'social'],
            'tiktok_url' => ['', 'text', 'social'],

            // Analytics
            'google_analytics_id' => ['', 'text', 'analytics'],
            'google_tag_manager_id' => ['', 'text', 'analytics'],
            'facebook_pixel_id' => ['', 'text', 'analytics'],
            'hotjar_id' => ['', 'text', 'analytics'],

            // Local SEO
            'business_latitude' => ['-6.9175', 'text', 'local'],
            'business_longitude' => ['107.6191', 'text', 'local'],
            'service_areas' => [json_encode(['Bandung Kota', 'Cimahi', 'Bandung Selatan', 'Bandung Utara']), 'json', 'local'],

            // Technical
            'robots_txt' => ['User-agent: *\nAllow: /\nSitemap: https://www.gawaikita.com/sitemap.xml', 'textarea', 'technical'],
            'enable_breadcrumbs' => [1, 'boolean', 'technical'],
            'enable_schema_markup' => [1, 'boolean', 'technical'],
        ];

        foreach ($defaults as $key => [$value, $type, $category]) {
            if (!$this->where('setting_key', $key)->first()) {
                $this->insert([
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'setting_type' => $type,
                    'category' => $category,
                    'is_editable' => 1
                ]);
            }
        }
    }
}
