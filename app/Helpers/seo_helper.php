<?php
// app/Helpers/seo_helper.php - Complete version

if (!function_exists('seo_setting')) {
    /**
     * Get SEO setting value with fallback
     */
    function seo_setting($key, $default = null)
    {
        static $seoModel = null;

        try {
            if ($seoModel === null) {
                $seoModel = new \App\Models\SeoSettingModel();
            }

            return $seoModel->getSetting($key, $default);
        } catch (\Exception $e) {
            // Fallback values if database not ready
            $fallbacks = [
                'site_name' => 'GawaiKita - Service Laptop Bandung',
                'business_name' => 'GawaiKita - Service Laptop Bandung',
                'business_phone' => '(022) 123-4567',
                'business_whatsapp' => '6282123456789',
                'business_email' => 'info@gawaikita.com',
                'business_address' => 'Jl. Sudirman No. 123, Bandung 40123',
                'business_hours' => 'Senin - Sabtu: 08:00 - 18:00 WIB',
                'site_description' => 'Service laptop terbaik di Bandung dengan teknisi berpengalaman',
                'default_meta_title' => 'Service Laptop Bandung Terbaik | GawaiKita',
                'default_meta_description' => 'Service laptop Bandung terbaik dengan teknisi berpengalaman. Garansi 30 hari, spare part original, harga terjangkau.',
                'business_latitude' => '-6.9175',
                'business_longitude' => '107.6191',
                'service_areas' => ['Bandung', 'Cimahi'],
                'facebook_url' => '',
                'instagram_url' => '',
                'youtube_url' => '',
                'tiktok_url' => '',
                'google_analytics_id' => '',
                'google_tag_manager_id' => ''
            ];

            return $fallbacks[$key] ?? $default;
        }
    }
}

if (!function_exists('generate_meta_tags')) {
    /**
     * Generate meta tags for page
     */
    function generate_meta_tags($data = [])
    {
        $defaults = [
            'title' => seo_setting('default_meta_title'),
            'description' => seo_setting('default_meta_description'),
            'keywords' => '',
            'canonical_url' => current_url(),
            'og_image' => base_url('assets/images/og-default.jpg'),
            'robots' => 'index, follow'
        ];

        $meta = array_merge($defaults, $data);

        $html = '';
        $html .= '<title>' . esc($meta['title']) . '</title>' . "\n";
        $html .= '<meta name="description" content="' . esc($meta['description']) . '">' . "\n";

        if (!empty($meta['keywords'])) {
            $html .= '<meta name="keywords" content="' . esc($meta['keywords']) . '">' . "\n";
        }

        $html .= '<meta name="robots" content="' . esc($meta['robots']) . '">' . "\n";
        $html .= '<link rel="canonical" href="' . esc($meta['canonical_url']) . '">' . "\n";

        // Open Graph tags
        $html .= '<meta property="og:title" content="' . esc($meta['title']) . '">' . "\n";
        $html .= '<meta property="og:description" content="' . esc($meta['description']) . '">' . "\n";
        $html .= '<meta property="og:image" content="' . esc($meta['og_image']) . '">' . "\n";
        $html .= '<meta property="og:url" content="' . esc($meta['canonical_url']) . '">' . "\n";

        return $html;
    }
}

if (!function_exists('generate_breadcrumb_schema')) {
    /**
     * Generate breadcrumb schema markup
     */
    function generate_breadcrumb_schema($breadcrumbs)
    {
        if (empty($breadcrumbs)) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];

        foreach ($breadcrumbs as $index => $breadcrumb) {
            $schema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb['name'],
                'item' => $breadcrumb['url'] ?: null
            ];
        }

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }
}

if (!function_exists('optimize_content_seo')) {
    /**
     * Optimize content for SEO
     */
    function optimize_content_seo($content, $keyword = '')
    {
        // Add alt tags to images without them
        $content = preg_replace_callback('/<img(?![^>]*alt=)[^>]*>/i', function ($match) {
            return str_replace('<img', '<img alt="Image"', $match[0]);
        }, $content);

        // Add loading="lazy" to images
        $content = preg_replace_callback('/<img(?![^>]*loading=)[^>]*>/i', function ($match) {
            return str_replace('<img', '<img loading="lazy"', $match[0]);
        }, $content);

        // Optimize headings structure
        if (!empty($keyword)) {
            // Ensure keyword appears in first paragraph
            $paragraphs = explode('</p>', $content);
            if (count($paragraphs) > 0 && stripos($paragraphs[0], $keyword) === false) {
                $paragraphs[0] = str_replace('<p>', '<p>' . $keyword . ' - ', $paragraphs[0]);
                $content = implode('</p>', $paragraphs);
            }
        }

        return $content;
    }
}

if (!function_exists('calculate_reading_time')) {
    /**
     * Calculate reading time for content
     */
    function calculate_reading_time($content, $wpm = 200)
    {
        $word_count = str_word_count(strip_tags($content));
        $reading_time = ceil($word_count / $wpm);

        return $reading_time;
    }
}

if (!function_exists('generate_excerpt')) {
    /**
     * Generate excerpt from content
     */
    function generate_excerpt($content, $length = 160)
    {
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);

        if (strlen($text) <= $length) {
            return $text;
        }

        $excerpt = substr($text, 0, $length);
        $excerpt = substr($excerpt, 0, strrpos($excerpt, ' '));

        return $excerpt . '...';
    }
}

if (!function_exists('format_phone')) {
    /**
     * Format phone number for display
     */
    function format_phone($phone)
    {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Format Indonesian phone number
        if (substr($phone, 0, 2) === '62') {
            return '+62 ' . substr($phone, 2, 3) . '-' . substr($phone, 5, 4) . '-' . substr($phone, 9);
        } elseif (substr($phone, 0, 1) === '0') {
            return substr($phone, 0, 4) . '-' . substr($phone, 4, 4) . '-' . substr($phone, 8);
        }

        return $phone;
    }
}

if (!function_exists('seo_friendly_url')) {
    /**
     * Generate SEO friendly URL
     */
    function seo_friendly_url($string)
    {
        // Convert to lowercase
        $string = strtolower($string);

        // Replace Indonesian characters
        $indonesian = ['á', 'à', 'ä', 'â', 'ā', 'ą', 'ã', 'é', 'è', 'ë', 'ê', 'ē', 'ę', 'í', 'ì', 'ï', 'î', 'ī', 'į', 'ó', 'ò', 'ö', 'ô', 'ō', 'ø', 'õ', 'ú', 'ù', 'ü', 'û', 'ū', 'ų'];
        $english = ['a', 'a', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u', 'u'];
        $string = str_replace($indonesian, $english, $string);

        // Replace spaces and special characters with dash
        $string = preg_replace('/[^a-z0-9]+/', '-', $string);

        // Remove multiple dashes
        $string = preg_replace('/-+/', '-', $string);

        // Trim dashes from ends
        return trim($string, '-');
    }
}

if (!function_exists('generate_schema_markup')) {
    /**
     * Generate structured data schema markup
     */
    function generate_schema_markup($type, $data)
    {
        $schema = ['@context' => 'https://schema.org'];

        switch ($type) {
            case 'organization':
                $schema['@type'] = 'Organization';
                $schema['name'] = $data['name'] ?? '';
                $schema['url'] = $data['url'] ?? base_url();
                $schema['logo'] = $data['logo'] ?? base_url('assets/images/logo.png');
                $schema['contactPoint'] = [
                    '@type' => 'ContactPoint',
                    'telephone' => $data['phone'] ?? '',
                    'contactType' => 'customer service'
                ];
                break;

            case 'local_business':
                $schema['@type'] = 'LocalBusiness';
                $schema['name'] = $data['name'] ?? '';
                $schema['address'] = [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $data['address'] ?? '',
                    'addressLocality' => $data['city'] ?? 'Bandung',
                    'addressRegion' => $data['region'] ?? 'Jawa Barat',
                    'addressCountry' => 'ID'
                ];
                $schema['telephone'] = $data['phone'] ?? '';
                $schema['openingHours'] = $data['hours'] ?? '';
                break;

            case 'service':
                $schema['@type'] = 'Service';
                $schema['name'] = $data['name'] ?? '';
                $schema['description'] = $data['description'] ?? '';
                $schema['provider'] = [
                    '@type' => 'Organization',
                    'name' => $data['provider'] ?? seo_setting('business_name')
                ];
                break;
        }

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }
}

if (!function_exists('get_seo_score')) {
    /**
     * Calculate basic SEO score for content
     */
    function get_seo_score($title, $description, $content, $keyword = '')
    {
        $score = 0;
        $max_score = 100;

        // Title length check (30-70 characters)
        $title_length = strlen($title);
        if ($title_length >= 30 && $title_length <= 70) {
            $score += 20;
        } elseif ($title_length >= 20 && $title_length <= 80) {
            $score += 10;
        }

        // Description length check (120-160 characters)
        $desc_length = strlen($description);
        if ($desc_length >= 120 && $desc_length <= 160) {
            $score += 20;
        } elseif ($desc_length >= 100 && $desc_length <= 180) {
            $score += 10;
        }

        // Content length check (minimum 300 words)
        $word_count = str_word_count(strip_tags($content));
        if ($word_count >= 500) {
            $score += 20;
        } elseif ($word_count >= 300) {
            $score += 15;
        } elseif ($word_count >= 200) {
            $score += 10;
        }

        // Keyword optimization (if keyword provided)
        if (!empty($keyword)) {
            $keyword_lower = strtolower($keyword);
            $title_lower = strtolower($title);
            $desc_lower = strtolower($description);
            $content_lower = strtolower(strip_tags($content));

            // Keyword in title
            if (strpos($title_lower, $keyword_lower) !== false) {
                $score += 15;
            }

            // Keyword in description
            if (strpos($desc_lower, $keyword_lower) !== false) {
                $score += 10;
            }

            // Keyword density in content (1-3%)
            $keyword_count = substr_count($content_lower, $keyword_lower);
            $density = $word_count > 0 ? ($keyword_count / $word_count) * 100 : 0;
            if ($density >= 1 && $density <= 3) {
                $score += 15;
            } elseif ($density > 0 && $density < 1) {
                $score += 5;
            }
        }

        return min($score, $max_score);
    }
}

if (!function_exists('validate_meta_tags')) {
    /**
     * Validate meta tags for SEO best practices
     */
    function validate_meta_tags($title, $description)
    {
        $errors = [];
        $warnings = [];

        // Title validation
        $title_length = strlen($title);
        if ($title_length < 10) {
            $errors[] = 'Title terlalu pendek (minimum 10 karakter)';
        } elseif ($title_length > 70) {
            $errors[] = 'Title terlalu panjang (maksimum 70 karakter)';
        } elseif ($title_length < 30 || $title_length > 60) {
            $warnings[] = 'Title sebaiknya 30-60 karakter untuk SEO optimal';
        }

        // Description validation
        $desc_length = strlen($description);
        if ($desc_length < 50) {
            $errors[] = 'Meta description terlalu pendek (minimum 50 karakter)';
        } elseif ($desc_length > 160) {
            $errors[] = 'Meta description terlalu panjang (maksimum 160 karakter)';
        } elseif ($desc_length < 120) {
            $warnings[] = 'Meta description sebaiknya 120-160 karakter untuk SEO optimal';
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'is_valid' => empty($errors)
        ];
    }
}

if (!function_exists('generate_sitemap_urls')) {
    /**
     * Generate sitemap URLs array
     */
    function generate_sitemap_urls()
    {
        $urls = [];

        // Static pages
        $static_pages = [
            ['url' => '', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => 'services', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['url' => 'about', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['url' => 'contact', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['url' => 'blog', 'priority' => '0.7', 'changefreq' => 'daily']
        ];

        foreach ($static_pages as $page) {
            $urls[] = [
                'loc' => base_url($page['url']),
                'lastmod' => date('Y-m-d'),
                'priority' => $page['priority'],
                'changefreq' => $page['changefreq']
            ];
        }

        return $urls;
    }
}

if (!function_exists('compress_html')) {
    /**
     * Compress HTML output for better performance
     */
    function compress_html($html)
    {
        // Remove HTML comments (except IE conditionals)
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);

        // Remove unnecessary whitespace
        $html = preg_replace('/>\s+</', '><', $html);
        $html = preg_replace('/\s+/', ' ', $html);

        // Remove whitespace around block elements
        $html = preg_replace('/\s*(<\/?(div|p|h[1-6]|ul|ol|li|nav|section|article|header|footer|main)[^>]*>)\s*/', '$1', $html);

        return trim($html);
    }
}