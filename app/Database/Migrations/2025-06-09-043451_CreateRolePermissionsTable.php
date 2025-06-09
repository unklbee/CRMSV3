<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolePermissionsTable extends Migration
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
            'role_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'permission_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'granted' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => '1=granted, 0=denied (for explicit deny)'
            ],
            'conditions' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Additional conditions for permission (time, IP, etc.)'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('role_id');
        $this->forge->addKey('permission_id');
        $this->forge->addUniqueKey(['role_id', 'permission_id']);

        // Foreign key constraints
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('role_permissions');
    }

    public function down()
    {
        $this->forge->dropTable('role_permissions');
    }
}