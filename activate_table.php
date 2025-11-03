<?php
/**
 * Manual table creation for Hetzner module
 * Visit: yourdomain.com/modules/servers/hetzner/activate_table.php
 * DELETE THIS FILE AFTER USE!
 */

require_once '../../../init.php';
use WHMCS\Database\Capsule;

try {
    if (!Capsule::schema()->hasTable('mod_hetzner_data')) {
        Capsule::schema()->create('mod_hetzner_data', function ($table) {
            $table->increments('id');
            $table->integer('service_id');
            $table->string('data_key');
            $table->text('data_value');
            $table->timestamp('created_at');
            $table->index('service_id');
        });
        echo '<h2 style="color: green;">✅ Table created successfully!</h2>';
        echo '<p>Database: ' . Capsule::connection()->getDatabaseName() . '</p>';
        echo '<p>Table: mod_hetzner_data</p>';
        echo '<p><strong>You can now accept orders!</strong></p>';
        echo '<p style="color: red;"><strong>⚠️ DELETE THIS FILE NOW for security!</strong></p>';
    } else {
        echo '<h2 style="color: blue;">ℹ️ Table already exists!</h2>';
        echo '<p>The mod_hetzner_data table is already in your database.</p>';
    }
} catch (Exception $e) {
    echo '<h2 style="color: red;">❌ Error:</h2>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
?>