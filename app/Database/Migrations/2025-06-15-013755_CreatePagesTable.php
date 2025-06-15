<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePagesTable extends Migration
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
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'meta_title' => [
                'type' => 'VARCHAR',
                'constraint' => 70,
                'comment' => 'SEO optimized title (max 70 chars)'
            ],
            'meta_description' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'comment' => 'SEO meta description (max 160 chars)'
            ],
            'meta_keywords' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'SEO keywords, comma separated'
            ],
            'canonical_url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Canonical URL for SEO'
            ],
            'og_title' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
                'comment' => 'Open Graph title'
            ],
            'og_description' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
                'comment' => 'Open Graph description'
            ],
            'og_image' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Open Graph image URL'
            ],
            'content' => [
                'type' => 'LONGTEXT',
                'comment' => 'Page content in HTML'
            ],
            'excerpt' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Short description/excerpt'
            ],
            'template' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'default' => 'default',
                'comment' => 'Template file to use'
            ],
            'page_type' => [
                'type' => 'ENUM',
                'constraint' => ['page', 'post', 'service', 'landing'],
                'default' => 'page',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'published', 'scheduled', 'archived'],
                'default' => 'draft',
            ],
            'visibility' => [
                'type' => 'ENUM',
                'constraint' => ['public', 'private', 'password'],
                'default' => 'public',
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'featured' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => 'Featured content flag'
            ],
            'featured_image' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'parent_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'For hierarchical pages'
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'view_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'schema_markup' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Structured data schema'
            ],
            'seo_settings' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Additional SEO settings'
            ],
            'published_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'author_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'editor_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Last editor'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'visibility']);
        $this->forge->addKey('page_type');
        $this->forge->addKey('featured');
        $this->forge->addKey('published_at');
        $this->forge->addKey('author_id');
        $this->forge->addKey('parent_id');
        $this->forge->addKey('deleted_at');

        // Foreign keys
        $this->forge->addForeignKey('author_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('editor_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('parent_id', 'pages', 'id', 'CASCADE', 'SET NULL');

        $this->forge->createTable('pages');
    }

    public function down()
    {
        $this->forge->dropTable('pages');
    }
}
