<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSeoSettingsTable extends Migration
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
            'setting_key' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'setting_value' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'setting_type' => [
                'type' => 'ENUM',
                'constraint' => ['text', 'textarea', 'json', 'boolean', 'number'],
                'default' => 'text',
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'general, social, analytics, etc.'
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_editable' => [
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
        $this->forge->addUniqueKey('setting_key');
        $this->forge->addKey('category');
        $this->forge->createTable('seo_settings');
    }

    public function down()
    {
        $this->forge->dropTable('seo_settings');
    }
}
