<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePermissionsTable extends Migration
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
                'unique' => true,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'unique' => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'module' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'Module/feature group (users, orders, reports, etc.)'
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'Action type (create, read, update, delete, manage)'
            ],
            'resource' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Specific resource (own, all, specific_id)'
            ],
            'is_active' => [
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
        $this->forge->addKey(['module', 'action']);
        $this->forge->addKey('is_active');
        $this->forge->createTable('permissions');
    }

    public function down()
    {
        $this->forge->dropTable('permissions');
    }
}