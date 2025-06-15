<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServicesTable extends Migration
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
                'constraint' => 255,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'detailed_description' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'icon' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Font Awesome icon class'
            ],
            'image' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'comment' => 'laptop, komputer, jaringan, cctv'
            ],
            'price_min' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'price_max' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'price_display' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Display format: "Mulai Rp 150.000"'
            ],
            'duration' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => '1-3 hari, 1 minggu, dll'
            ],
            'features' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Array of service features'
            ],
            'requirements' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Service requirements'
            ],
            'warranty' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '30 hari, 6 bulan, dll'
            ],
            'seo_title' => [
                'type' => 'VARCHAR',
                'constraint' => 70,
                'null' => true,
            ],
            'seo_description' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'seo_keywords' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_featured' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
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
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('category');
        $this->forge->addKey('is_featured');
        $this->forge->addKey('is_active');
        $this->forge->addKey('sort_order');
        $this->forge->createTable('services');
    }

    public function down()
    {
        $this->forge->dropTable('services');
    }
}