<?php

namespace App\Models;

use CodeIgniter\Model;

class TestimonialModel extends Model
{
    protected $table = 'testimonials';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name', 'email', 'phone', 'company', 'position', 'avatar',
        'testimonial', 'rating', 'service_id', 'device_type', 'source',
        'is_featured', 'is_approved', 'is_public', 'approved_by', 'approved_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'name' => 'required|min_length[2]|max_length[100]',
        'testimonial' => 'required|min_length[20]|max_length[1000]',
        'rating' => 'required|integer|greater_than[0]|less_than[6]',
        'email' => 'permit_empty|valid_email'
    ];

    /**
     * Get approved testimonials
     */
    public function getApproved($limit = null)
    {
        $builder = $this->where('is_approved', 1)
            ->where('is_public', 1)
            ->orderBy('created_at', 'DESC');

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Get featured testimonials
     */
    public function getFeatured($limit = 6)
    {
        return $this->where('is_featured', 1)
            ->where('is_approved', 1)
            ->where('is_public', 1)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get testimonials by rating
     */
    public function getByRating($rating, $limit = null)
    {
        $builder = $this->where('rating', $rating)
            ->where('is_approved', 1)
            ->where('is_public', 1)
            ->orderBy('created_at', 'DESC');

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Get testimonials with service info
     */
    public function getWithServiceInfo($limit = null)
    {
        $builder = $this->select('testimonials.*, services.name as service_name, services.category')
            ->join('services', 'services.id = testimonials.service_id', 'left')
            ->where('testimonials.is_approved', 1)
            ->where('testimonials.is_public', 1)
            ->orderBy('testimonials.created_at', 'DESC');

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Get average rating
     */
    public function getAverageRating()
    {
        $result = $this->select('AVG(rating) as avg_rating, COUNT(*) as total_reviews')
            ->where('is_approved', 1)
            ->where('is_public', 1)
            ->first();

        return [
            'average' => round($result['avg_rating'], 1),
            'total' => (int)$result['total_reviews']
        ];
    }

    /**
     * Approve testimonial
     */
    public function approve($id, $userId)
    {
        return $this->update($id, [
            'is_approved' => 1,
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }
}