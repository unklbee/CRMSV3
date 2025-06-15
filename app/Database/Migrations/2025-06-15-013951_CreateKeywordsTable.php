<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKeywordsTable extends Migration
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
            'keyword' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'search_volume' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'comment' => 'Monthly search volume'
            ],
            'difficulty' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'null' => true,
                'comment' => 'Keyword difficulty (1-100)'
            ],
            'cpc' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'comment' => 'Cost per click'
            ],
            'intent' => [
                'type' => 'ENUM',
                'constraint' => ['informational', 'commercial', 'transactional', 'navigational'],
                'null' => true,
            ],
            'priority' => [
                'type' => 'ENUM',
                'constraint' => ['low', 'medium', 'high', 'critical'],
                'default' => 'medium',
            ],
            'current_position' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'null' => true,
                'comment' => 'Current ranking position'
            ],
            'target_position' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'default' => 1,
                'comment' => 'Target ranking position'
            ],
            'competitor_analysis' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Competitor ranking data'
            ],
            'is_tracked' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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
        $this->forge->addUniqueKey('keyword');
        $this->forge->addKey('priority');
        $this->forge->addKey('current_position');
        $this->forge->addKey('is_tracked');
        $this->forge->createTable('keywords');
    }

    public function down()
    {
        $this->forge->dropTable('keywords');
    }
}