<?php
/**
 * Hetzner Cloud WHMCS Provisioning Module
 *
 * @copyright Copyright (c) 2025
 * @license MIT License
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Module metadata
 */
function hetzner_MetaData()
{
    return array(
        'DisplayName' => 'Hetzner Cloud',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '80',
        'DefaultSSLPort' => '443',
    );
}

/**
 * Module configuration options
 */
function hetzner_ConfigOptions()
{
    return array(
        'server_type' => array(
            'FriendlyName' => 'Server Type',
            'Type' => 'dropdown',
            'Options' => array(
                'cx11' => 'CX11 - 1 vCPU, 2GB RAM, 20GB SSD',
                'cx21' => 'CX21 - 2 vCPU, 4GB RAM, 40GB SSD',
                'cx31' => 'CX31 - 2 vCPU, 8GB RAM, 80GB SSD',
                'cx41' => 'CX41 - 4 vCPU, 16GB RAM, 160GB SSD',
                'cx51' => 'CX51 - 8 vCPU, 32GB RAM, 240GB SSD',
                'cpx11' => 'CPX11 - 2 vCPU, 2GB RAM, 40GB SSD',
                'cpx21' => 'CPX21 - 3 vCPU, 4GB RAM, 80GB SSD',
                'cpx31' => 'CPX31 - 4 vCPU, 8GB RAM, 160GB SSD',
                'cpx41' => 'CPX41 - 8 vCPU, 16GB RAM, 240GB SSD',
                'cpx51' => 'CPX51 - 16 vCPU, 32GB RAM, 360GB SSD',
            ),
            'Description' => 'Select the server type',
            'Default' => 'cx11',
        ),
        'location' => array(
            'FriendlyName' => 'Location',
            'Type' => 'dropdown',
            'Options' => array(
                'nbg1' => 'Nuremberg, Germany (nbg1)',
                'fsn1' => 'Falkenstein, Germany (fsn1)',
                'hel1' => 'Helsinki, Finland (hel1)',
                'ash' => 'Ashburn, USA (ash)',
                'hil' => 'Hillsboro, USA (hil)',
            ),
            'Description' => 'Server location',
            'Default' => 'nbg1',
        ),
        'image' => array(
            'FriendlyName' => 'OS Image',
            'Type' => 'dropdown',
            'Options' => array(
                'ubuntu-22.04' => 'Ubuntu 22.04',
                'ubuntu-20.04' => 'Ubuntu 20.04',
                'debian-11' => 'Debian 11',
                'debian-12' => 'Debian 12',
                'centos-stream-9' => 'CentOS Stream 9',
                'rocky-9' => 'Rocky Linux 9',
                'fedora-38' => 'Fedora 38',
            ),
            'Description' => 'Operating system image',
            'Default' => 'ubuntu-22.04',
        ),
        'enable_backups' => array(
            'FriendlyName' => 'Enable Backups',
            'Type' => 'yesno',
            'Description' => 'Tick to enable automatic backups',
            'Default' => 'no',
        ),
    );
}

/**
 * Provision a new server instance
 */
function hetzner_CreateAccount(array $params)
{
    try {
        $apiToken = $params['serverpassword'];
        $serverType = $params['configoption1'];
        $location = $params['configoption2'];
        $image = $params['configoption3'];
        $enableBackups = ($params['configoption4'] == 'on');
        
        $serverName = 'whmcs-' . $params['serviceid'] . '-' . substr(md5(time()), 0, 8);
        
        // Generate SSH key for server access
        $sshKeyData = hetzner_GenerateSSHKey($params);
        
        // Create SSH key in Hetzner
        $sshKeyId = hetzner_CreateSSHKey($apiToken, $serverName, $sshKeyData['public']);
        
        // Create server
        $postData = array(
            'name' => $serverName,
            'server_type' => $serverType,
            'location' => $location,
            'image' => $image,
            'ssh_keys' => array($sshKeyId),
            'start_after_create' => true,
        );
        
        if ($enableBackups) {
            $postData['automount'] = true;
        }
        
        $response = hetzner_ApiRequest($apiToken, 'POST', 'servers', $postData);
        
        if (isset($response['error'])) {
            return $response['error']['message'];
        }
        
        $serverId = $response['server']['id'];
        $rootPassword = $response['root_password'];
        $ipAddress = $response['server']['public_net']['ipv4']['ip'];
        
        // Store server details
        Capsule::table('tblhosting')->where('id', $params['serviceid'])->update([
            'dedicatedip' => $ipAddress,
            'username' => 'root',
            'password' => encrypt($rootPassword),
        ]);
        
        // Store additional data
        hetzner_StoreServerData($params['serviceid'], array(
            'server_id' => $serverId,
            'server_name' => $serverName,
            'ssh_key_id' => $sshKeyId,
            'ssh_private_key' => $sshKeyData['private'],
        ));
        
        return 'success';
        
    } catch (Exception $e) {
        logModuleCall('hetzner', 'CreateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

/**
 * Suspend a server instance
 */
function hetzner_SuspendAccount(array $params)
{
    try {
        $apiToken = $params['serverpassword'];
        $serverData = hetzner_GetServerData($params['serviceid']);
        
        if (!$serverData || !isset($serverData['server_id'])) {
            return 'Server not found';
        }
        
        $response = hetzner_ApiRequest($apiToken, 'POST', 'servers/' . $serverData['server_id'] . '/actions/shutdown');
        
        if (isset($response['error'])) {
            return $response['error']['message'];
        }
        
        return 'success';
        
    } catch (Exception $e) {
        logModuleCall('hetzner', 'SuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

/**
 * Unsuspend a server instance
 */
function hetzner_UnsuspendAccount(array $params)
{
    try {
        $apiToken = $params['serverpassword'];
        $serverData = hetzner_GetServerData($params['serviceid']);
        
        if (!$serverData || !isset($serverData['server_id'])) {
            return 'Server not found';
        }
        
        $response = hetzner_ApiRequest($apiToken, 'POST', 'servers/' . $serverData['server_id'] . '/actions/poweron');
        
        if (isset($response['error'])) {
            return $response['error']['message'];
        }
        
        return 'success';
        
    } catch (Exception $e) {
        logModuleCall('hetzner', 'UnsuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

/**
 * Terminate a server instance
 */
function hetzner_TerminateAccount(array $params)
{
    try {
        $apiToken = $params['serverpassword'];
        $serverData = hetzner_GetServerData($params['serviceid']);
        
        if (!$serverData || !isset($serverData['server_id'])) {
            return 'success'; // Already deleted
        }
        
        // Delete server
        $response = hetzner_ApiRequest($apiToken, 'DELETE', 'servers/' . $serverData['server_id']);
        
        // Delete SSH key
        if (isset($serverData['ssh_key_id'])) {
            hetzner_ApiRequest($apiToken, 'DELETE', 'ssh_keys/' . $serverData['ssh_key_id']);
        }
        
        // Clean up stored data
        hetzner_DeleteServerData($params['serviceid']);
        
        return 'success';
        
    } catch (Exception $e) {
        logModuleCall('hetzner', 'TerminateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

/**
 * Client area output
 */
function hetzner_ClientArea(array $params)
{
    try {
        $serverData = hetzner_GetServerData($params['serviceid']);
        
        if (!$serverData || !isset($serverData['server_id'])) {
            return array(
                'templatefile' => 'templates/error',
                'vars' => array('error' => 'Server information not available'),
            );
        }
        
        $apiToken = $params['serverpassword'];
        $response = hetzner_ApiRequest($apiToken, 'GET', 'servers/' . $serverData['server_id']);
        
        if (isset($response['error'])) {
            return array(
                'templatefile' => 'templates/error',
                'vars' => array('error' => $response['error']['message']),
            );
        }
        
        $server = $response['server'];
        
        return array(
            'templatefile' => 'templates/overview',
            'vars' => array(
                'server_id' => $server['id'],
                'server_name' => $server['name'],
                'status' => ucfirst($server['status']),
                'ip_address' => $server['public_net']['ipv4']['ip'],
                'ipv6_network' => $server['public_net']['ipv6']['network'] ?? 'N/A',
                'server_type' => $server['server_type']['name'],
                'location' => $server['datacenter']['location']['city'],
                'image' => $server['image']['description'],
                'created' => date('Y-m-d H:i:s', strtotime($server['created'])),
                'serviceid' => $params['serviceid'],
            ),
        );
        
    } catch (Exception $e) {
        logModuleCall('hetzner', 'ClientArea', $params, $e->getMessage(), $e->getTraceAsString());
        return array(
            'templatefile' => 'templates/error',
            'vars' => array('error' => 'Unable to load server information'),
        );
    }
}

/**
 * Admin area custom buttons
 */
function hetzner_AdminCustomButtonArray()
{
    return array(
        'Reboot Server' => 'RebootServer',
        'Reset Server' => 'ResetServer',
        'View Console' => 'ViewConsole',
    );
}

/**
 * Client area custom buttons
 */
function hetzner_ClientAreaCustomButtonArray()
{
    return array(
        'Reboot Server' => 'RebootServer',
        'Reset Password' => 'ResetPassword',
    );
}

/**
 * Reboot server action
 */
function hetzner_RebootServer(array $params)
{
    try {
        $apiToken = $params['serverpassword'];
        $serverData = hetzner_GetServerData($params['serviceid']);
        
        $response = hetzner_ApiRequest($apiToken, 'POST', 'servers/' . $serverData['server_id'] . '/actions/reboot');
        
        if (isset($response['error'])) {
            return $response['error']['message'];
        }
        
        return 'success';
        
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Reset server action
 */
function hetzner_ResetServer(array $params)
{
    try {
        $apiToken = $params['serverpassword'];
        $serverData = hetzner_GetServerData($params['serviceid']);
        
        $response = hetzner_ApiRequest($apiToken, 'POST', 'servers/' . $serverData['server_id'] . '/actions/reset');
        
        if (isset($response['error'])) {
            return $response['error']['message'];
        }
        
        return 'success';
        
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
 * View console action
 */
function hetzner_ViewConsole(array $params)
{
    $serverData = hetzner_GetServerData($params['serviceid']);
    $consoleUrl = 'https://console.hetzner.cloud/?server=' . $serverData['server_id'];
    
    return array(
        'success' => true,
        'redirect' => $consoleUrl,
    );
}

/**
 * Reset password action
 */
function hetzner_ResetPassword(array $params)
{
    try {
        $apiToken = $params['serverpassword'];
        $serverData = hetzner_GetServerData($params['serviceid']);
        
        $response = hetzner_ApiRequest($apiToken, 'POST', 'servers/' . $serverData['server_id'] . '/actions/reset_password');
        
        if (isset($response['error'])) {
            return $response['error']['message'];
        }
        
        $newPassword = $response['root_password'];
        
        Capsule::table('tblhosting')->where('id', $params['serviceid'])->update([
            'password' => encrypt($newPassword),
        ]);
        
        return 'success';
        
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Helper: Make API request to Hetzner Cloud API
 */
function hetzner_ApiRequest($token, $method, $endpoint, $data = null)
{
    $url = 'https://api.hetzner.cloud/v1/' . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ));
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    logModuleCall('hetzner', $method . ' ' . $endpoint, $data, $response, $result);
    
    return $result;
}

/**
 * Helper: Generate SSH key pair
 */
function hetzner_GenerateSSHKey($params)
{
    $privateKey = openssl_pkey_new(array(
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ));
    
    openssl_pkey_export($privateKey, $privateKeyPem);
    $publicKey = openssl_pkey_get_details($privateKey);
    $publicKeyPem = $publicKey['key'];
    
    return array(
        'private' => $privateKeyPem,
        'public' => $publicKeyPem,
    );
}

/**
 * Helper: Create SSH key in Hetzner
 */
function hetzner_CreateSSHKey($token, $name, $publicKey)
{
    $response = hetzner_ApiRequest($token, 'POST', 'ssh_keys', array(
        'name' => 'whmcs-' . $name,
        'public_key' => $publicKey,
    ));
    
    return $response['ssh_key']['id'];
}

/**
 * Helper: Store server data
 */
function hetzner_StoreServerData($serviceId, $data)
{
    foreach ($data as $key => $value) {
        $existing = Capsule::table('tblcustomfieldsvalues')
            ->where('relid', $serviceId)
            ->where('fieldid', function($query) use ($key) {
                $query->select('id')
                    ->from('tblcustomfields')
                    ->where('fieldname', $key)
                    ->limit(1);
            })
            ->first();
        
        if ($existing) {
            Capsule::table('tblcustomfieldsvalues')
                ->where('id', $existing->id)
                ->update(['value' => $value]);
        } else {
            Capsule::table('mod_hetzner_data')->insert([
                'service_id' => $serviceId,
                'data_key' => $key,
                'data_value' => $value,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}

/**
 * Helper: Get server data
 */
function hetzner_GetServerData($serviceId)
{
    $rows = Capsule::table('mod_hetzner_data')
        ->where('service_id', $serviceId)
        ->get();
    
    $data = array();
    foreach ($rows as $row) {
        $data[$row->data_key] = $row->data_value;
    }
    
    return $data;
}

/**
 * Helper: Delete server data
 */
function hetzner_DeleteServerData($serviceId)
{
    Capsule::table('mod_hetzner_data')
        ->where('service_id', $serviceId)
        ->delete();
}

/**
 * Create module database table on activation
 */
function hetzner_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_hetzner_data')) {
            Capsule::schema()->create('mod_hetzner_data', function ($table) {
                $table->increments('id');
                $table->integer('service_id');
                $table->string('data_key');
                $table->text('data_value');
                $table->timestamp('created_at');
            });
        }
        
        return array('status' => 'success', 'description' => 'Hetzner module activated successfully');
    } catch (Exception $e) {
        return array('status' => 'error', 'description' => 'Unable to create module table: ' . $e->getMessage());
    }
}

/**
 * Drop module database table on deactivation
 */
function hetzner_deactivate()
{
    try {
        Capsule::schema()->dropIfExists('mod_hetzner_data');
        return array('status' => 'success', 'description' => 'Hetzner module deactivated successfully');
    } catch (Exception $e) {
        return array('status' => 'error', 'description' => 'Unable to drop module table: ' . $e->getMessage());
    }
}
