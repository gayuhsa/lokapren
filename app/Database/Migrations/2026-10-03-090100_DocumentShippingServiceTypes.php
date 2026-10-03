<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The checkout design offers a same-day scheduled courier alongside the three
 * service types the delivery migration originally documented, so the column
 * comment is corrected to match the values shipping_methods actually seeds.
 *
 * Only the comment changes; no data or type is affected.
 */
class DocumentShippingServiceTypes extends Migration
{
    private const COMMENT = 'pesan_antar_terima | layanan_istimewa | ekspedisi_reguler | cargo';

    public function up()
    {
        $this->comment(self::COMMENT);
    }

    public function down()
    {
        $this->comment('pesan_antar_terima | ekspedisi_reguler | cargo');
    }

    private function comment(string $text): void
    {
        if (! in_array('service_type', $this->db->getFieldNames('order_deliveries'), true)) {
            return;
        }

        $escaped = $this->db->escape($text);

        $sql = match ($this->db->DBDriver) {
            'pgsql'  => 'COMMENT ON COLUMN order_deliveries.service_type IS ' . $escaped,
            'mysql'  => 'ALTER TABLE order_deliveries MODIFY service_type VARCHAR(30) NOT NULL COMMENT ' . $escaped,
            default  => 'SELECT 1',
        };

        $this->db->query($sql);
    }
}