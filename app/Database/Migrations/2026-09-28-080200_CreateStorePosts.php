<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStorePosts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'store_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            'excerpt' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'body' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'cover_image_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'media_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'Optional video/audio URL, e.g. artisan documentary.',
            ],
            'post_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'article',
                'comment'    => 'article | story | documentary',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'draft',
                'comment'    => 'draft | published',
            ],
            'published_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('store_id');
        $this->forge->addKey(['store_id', 'status', 'published_at']);
        $this->forge->addUniqueKey(['store_id', 'slug']);
        $this->forge->addForeignKey('store_id', 'stores', 'id', 'RESTRICT', 'CASCADE');

        $this->forge->createTable('store_posts', true);
    }

    public function down()
    {
        $this->forge->dropTable('store_posts', true);
    }
}
