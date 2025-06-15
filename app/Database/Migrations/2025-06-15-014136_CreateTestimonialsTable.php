<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTestimonialsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'company' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'position' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'avatar' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'testimonial' => [
                'type' => 'TEXT',
            ],
            'rating' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 5,
                'comment' => 'Rating 1-5 stars'
            ],
            'service_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Related service'
            ],
            'device_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Laptop ASUS ROG, HP Pavilion, dll'
            ],
            'source' => [
                'type' => 'ENUM',
                'constraint' => ['form', 'google', 'facebook', 'whatsapp', 'manual'],
                'default' => 'manual',
            ],
            'is_featured' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'is_approved' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'is_public' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'approved_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('rating');
        $this->forge->addKey('service_id');
        $this->forge->addKey('is_featured');
        $this->forge->addKey('is_approved');
        $this->forge->addKey('is_public');
        $this->forge->addKey('source');

        // Foreign keys
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('approved_by', 'users', 'id', 'CASCADE', 'SET NULL');

        $this->forge->createTable('testimonials');
    }

    public function down()
    {
        $this->forge->dropTable('testimonials');
    }
}