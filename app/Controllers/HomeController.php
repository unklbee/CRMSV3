<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class HomeController extends BaseController
{
    protected $session;

    public function __construct()
    {
        $this->session = session();
    }

    public function index()
    {
        // Jika user sudah login, redirect ke dashboard
        if ($this->session->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        $data = [
            'title' => 'Welcome to Our Platform',
            'meta_description' => 'Professional service management platform for businesses',
            'meta_keywords' => 'service management, business platform, professional services',
            'services' => $this->getFeaturedServices(),
            'testimonials' => $this->getTestimonials(),
            'stats' => $this->getPublicStats()
        ];

        return view('frontend/home', $data);
    }

    /**
     * About Us Page
     */
    public function about(): string
    {
        $data = [
            'title' => 'About Us',
            'meta_description' => 'Learn more about our company and mission',
            'team_members' => $this->getTeamMembers(),
            'company_history' => $this->getCompanyHistory()
        ];

        return view('frontend/about', $data);
    }

    /**
     * Services Page
     */
    public function services(): string
    {
        $data = [
            'title' => 'Our Services',
            'meta_description' => 'Discover our comprehensive range of professional services',
            'service_categories' => $this->getServiceCategories(),
            'featured_services' => $this->getFeaturedServices()
        ];

        return view('frontend/services', $data);
    }

    /**
     * Contact Page
     */
    public function contact(): string
    {
        $data = [
            'title' => 'Contact Us',
            'meta_description' => 'Get in touch with our professional team',
            'contact_info' => $this->getContactInfo(),
            'office_locations' => $this->getOfficeLocations()
        ];

        return view('frontend/contact', $data);
    }

    /**
     * Process Contact Form
     */
    public function processContact()
    {
        $validation = \Config\Services::validation();

        $rules = [
            'name' => 'required|min_length[2]|max_length[100]',
            'email' => 'required|valid_email',
            'subject' => 'required|min_length[5]|max_length[200]',
            'message' => 'required|min_length[10]|max_length[1000]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        // Process contact form (send email, save to database, etc.)
        $contactData = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'subject' => $this->request->getPost('subject'),
            'message' => $this->request->getPost('message'),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Save to database or send email
        $this->sendContactEmail($contactData);
        $this->saveContactSubmission($contactData);

        return redirect()->to('/contact')->with('success', 'Thank you for your message. We will get back to you soon!');
    }

    /**
     * Blog/News Page
     */
    public function blog(): string
    {
        $data = [
            'title' => 'Blog & News',
            'meta_description' => 'Latest news and insights from our platform',
            'recent_posts' => $this->getRecentBlogPosts(),
            'featured_post' => $this->getFeaturedBlogPost(),
            'categories' => $this->getBlogCategories()
        ];

        return view('frontend/blog', $data);
    }

    /**
     * Individual Blog Post
     */
    public function blogPost($slug): string
    {
        $post = $this->getBlogPostBySlug($slug);

        if (!$post) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Blog post not found');
        }

        $data = [
            'title' => $post['title'],
            'meta_description' => $post['excerpt'],
            'post' => $post,
            'related_posts' => $this->getRelatedPosts($post['id']),
            'recent_posts' => $this->getRecentBlogPosts(5)
        ];

        return view('frontend/blog_post', $data);
    }

    /**
     * Pricing Page
     */
    public function pricing(): string
    {
        $data = [
            'title' => 'Pricing Plans',
            'meta_description' => 'Choose the perfect plan for your business needs',
            'pricing_plans' => $this->getPricingPlans(),
            'features_comparison' => $this->getFeaturesComparison()
        ];

        return view('frontend/pricing', $data);
    }

    /**
     * Privacy Policy
     */
    public function privacy(): string
    {
        $data = [
            'title' => 'Privacy Policy',
            'meta_description' => 'Our commitment to protecting your privacy',
            'last_updated' => '2024-01-01'
        ];

        return view('frontend/privacy', $data);
    }

    /**
     * Terms of Service
     */
    public function terms(): string
    {
        $data = [
            'title' => 'Terms of Service',
            'meta_description' => 'Terms and conditions for using our platform',
            'last_updated' => '2024-01-01'
        ];

        return view('frontend/terms', $data);
    }

    /**
     * Get Started Page (Lead to Registration)
     */
    public function getStarted()
    {
        // Redirect to registration if not logged in
        if (!$this->session->get('isLoggedIn')) {
            return redirect()->to('/auth/signup')->with('info', 'Create your account to get started');
        }

        // If logged in, redirect to dashboard
        return redirect()->to('/dashboard');
    }

    /**
     * API endpoint for newsletter subscription
     */
    public function subscribeNewsletter()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('/');
        }

        $email = $this->request->getPost('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Please provide a valid email address'
            ]);
        }

        // Save newsletter subscription
        $this->saveNewsletterSubscription($email);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Thank you for subscribing to our newsletter!'
        ]);
    }

    /**
     * Helper Methods (Mock Data - Replace with actual database queries)
     */
    private function getFeaturedServices(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Network Installation',
                'description' => 'Professional network setup and configuration for your business',
                'icon' => 'fas fa-network-wired',
                'price_range' => '$500 - $2000'
            ],
            [
                'id' => 2,
                'title' => 'System Maintenance',
                'description' => 'Regular maintenance to keep your systems running smoothly',
                'icon' => 'fas fa-tools',
                'price_range' => '$100 - $500'
            ],
            [
                'id' => 3,
                'title' => 'Security Audit',
                'description' => 'Comprehensive security assessment and recommendations',
                'icon' => 'fas fa-shield-alt',
                'price_range' => '$300 - $1000'
            ]
        ];
    }

    private function getTestimonials(): array
    {
        return [
            [
                'name' => 'John Smith',
                'company' => 'Tech Corp',
                'position' => 'IT Manager',
                'testimonial' => 'Exceptional service and support. Highly recommended for any business.',
                'rating' => 5,
                'avatar' => 'avatar1.jpg'
            ],
            [
                'name' => 'Sarah Johnson',
                'company' => 'Digital Solutions',
                'position' => 'CEO',
                'testimonial' => 'Professional team with great expertise. They delivered exactly what we needed.',
                'rating' => 5,
                'avatar' => 'avatar2.jpg'
            ]
        ];
    }

    private function getPublicStats(): array
    {
        return [
            'satisfied_clients' => 500,
            'projects_completed' => 1200,
            'years_experience' => 10,
            'team_members' => 25
        ];
    }

    private function getTeamMembers(): array
    {
        return [
            [
                'name' => 'David Wilson',
                'position' => 'CEO & Founder',
                'bio' => 'With over 15 years of experience in technology solutions.',
                'avatar' => 'team1.jpg',
                'social' => [
                    'linkedin' => '#',
                    'twitter' => '#'
                ]
            ]
            // Add more team members
        ];
    }

    private function getServiceCategories(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Network Services',
                'description' => 'Complete network solutions for businesses',
                'services_count' => 8
            ],
            [
                'id' => 2,
                'name' => 'Maintenance Services',
                'description' => 'Regular maintenance and support services',
                'services_count' => 12
            ]
        ];
    }

    private function getContactInfo(): array
    {
        return [
            'phone' => '+1 (555) 123-4567',
            'email' => 'info@company.com',
            'address' => '123 Business Street, City, State 12345',
            'business_hours' => [
                'monday_friday' => '9:00 AM - 6:00 PM',
                'saturday' => '9:00 AM - 2:00 PM',
                'sunday' => 'Closed'
            ]
        ];
    }

    private function getPricingPlans(): array
    {
        return [
            [
                'name' => 'Basic',
                'price' => 29,
                'billing' => 'month',
                'features' => [
                    'Up to 5 services',
                    'Email support',
                    'Basic reporting'
                ],
                'popular' => false
            ],
            [
                'name' => 'Professional',
                'price' => 79,
                'billing' => 'month',
                'features' => [
                    'Unlimited services',
                    'Priority support',
                    'Advanced reporting',
                    'API access'
                ],
                'popular' => true
            ]
        ];
    }

    // Additional helper methods for blog, contact processing, etc.
    private function getRecentBlogPosts(int $limit = 10): array { return []; }
    private function getFeaturedBlogPost(): array { return []; }
    private function getBlogCategories(): array { return []; }
    private function getBlogPostBySlug(string $slug): ?array { return null; }
    private function getRelatedPosts(int $postId): array { return []; }
    private function getCompanyHistory(): array { return []; }
    private function getOfficeLocations(): array { return []; }
    private function getFeaturesComparison(): array { return []; }

    private function sendContactEmail(array $data): void {
        // Implement email sending logic
    }

    private function saveContactSubmission(array $data): void {
        // Implement database saving logic
    }

    private function saveNewsletterSubscription(string $email): void {
        // Implement newsletter subscription logic
    }
}