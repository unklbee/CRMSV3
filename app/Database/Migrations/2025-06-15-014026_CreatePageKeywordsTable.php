<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePageKeywordsTable extends Migration
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
            'page_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'keyword_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'keyword_type' => [
                'type' => 'ENUM',
                'constraint' => ['primary', 'secondary', 'long_tail'],
                'default' => 'secondary',
            ],
            'density' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'comment' => 'Keyword density percentage'
            ],
            'position_h1' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => 'Is keyword in H1?'
            ],
            'position_title' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => 'Is keyword in title?'
            ],
            'position_meta' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => 'Is keyword in meta description?'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['page_id', 'keyword_id']);
        $this->forge->addKey('keyword_type');

        // Foreign keys
        $this->forge->addForeignKey('page_id', 'pages', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('keyword_id', 'keywords', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('page_keywords');
    }

    public function down()
    {
        $this->forge->dropTable('page_keywords');
    }
}
