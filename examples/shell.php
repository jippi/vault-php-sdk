<?php
namespace App\Shell;

use Cake\Utility\Hash;
use Jippi\Vault\ServiceFactory as VaultFactory;

/**
 * Vault shell
 *
 */
class VaultShell extends AppShell
{
    /**
     * List of tasks needed by the shell
     *
     * @var array
     */
    public $tasks = ['Vault']; // see task.php

    /**
     * Nice CLI validation and help output
     *
     * @return mixed
     */
    public function getOptionParser() {
        $requireProject = [
            'arguments' => [
                'project' => ['help' => 'Project name (folder)', 'required' => true]
            ]
        ];

        return parent::getOptionParser()
            ->addSubCommand('all', [
                'help' => 'Same as "start" + "create" + "unseal" + "mounts"'
            ])
            ->addSubCommand('start', [
                'help' => 'Start vault'
            ])
            ->addSubCommand('stop', [
                'help' => 'Stop vault'
            ])
            ->addSubCommand('restart', [
                'help' => 'Stop vault'
            ])
            ->addSubCommand('reset', [
                'help' => 'Wipe Vault and all its configuration'
            ])
            ->addSubCommand('status', [
                'help' => 'Get the status of the Vault instance'
            ])
            ->addSubCommand('create', [
                'help' => 'Initialize Vault and store the secrets safely'
            ])
            ->addSubCommand('env', [
                'help' => join(PHP_EOL, [
                    'Export the ENV variables for using the vault CLI tool directly.',
                    'Please run: eval $(sudo bownty-cli vault env)'
                ])
            ])
            ->addSubCommand('mounts', [
                'help' => 'Create the mounts required for global operation'
            ])
            ->addSubCommand('seal', [
                'help' => 'Seal the Vault'
            ])
            ->addSubCommand('unseal', [
                'help' => 'Unseal the Vault'
            ])
            ->addSubCommand('sync_vault_key', [
                'help' => 'Sync the Vault key into Consul (used by consul-template)'
            ])
            ->addSubCommand('wait_for', [
                'help' => 'Wait for something'
            ]);
    }

    /**
     * Same as "start" + "create" + "unseal" + "mounts"
     *
     * @return void
     */
    public function all()
    {
        $this->hr();
        $this->out('Ensure Vault is running');
        $this->hr();
        $this->start();
        $this->out('');

        $this->hr();
        $this->out('Ensure Vault is initialized');
        $this->hr();
        $this->create();
        $this->out('');

        $this->Vault->waitFor(['initialized' => true]);

        $this->hr();
        $this->out('Ensure Vault is unsealed');
        $this->hr();
        $this->unseal();
        $this->out('');

        $this->Vault->waitFor(['sealed' => false, 'standby' => false]);
        $this->Vault->clear();

        $this->hr();
        $this->out('Writing mount-points');
        $this->hr();
        $this->mounts();

        $this->hr();
        $this->syncVaultKey(true);
    }

    public function syncVaultKey($hr = false)
    {
        $this->out('Sync Vault Key');
        if ($hr) {
            $this->hr();
        }

        $file = $this->Vault->getVaultKeyFile();
        if (!file_exists($file)) {
            return $this->abort('Missing ~/.bownty_vault_keys file');
        }

        $token = json_decode(file_get_contents($file), true)['root_token'];
        $this->Consul->kv()->put('consul/template/VAULT_TOKEN', (string)$token);
        $this->success('OK');
    }

    /**
     * Start Vault
     *
     * @return void
     */
    public function start()
    {
        $this->Vault->ensureRunning();
    }

    /**
     * Stop Vault
     *
     * @return void
     */
    public function stop()
    {
        $this->Vault->ensureStopped();
    }

    /**
     * Restart Vault
     *
     * @return void
     */
    public function restart()
    {
        $this->stop();
        $this->start();
    }

    /**
     * Reset Vault
     *
     * - Stop Vault
     * - Delete the vault/ keyprefix from Consul
     * - Start Vault
     *
     * @return void
     */
    public function reset()
    {
        $this->Consul->ensureRunning();
        $this->Vault->ensureStopped();

        $this->Consul->kv()->delete('vault/', ['recurse' => true])->json();

        $file = $this->Vault->getVaultKeyFile();
        if (file_exists($file)) {
            unlink($file);
        }

        $this->Vault->ensureRunning();
    }

    /**
     * Check the status of Vault
     *
     * @return boolean
     */
    public function status()
    {
        $status = $this->Vault->sys()->status()->json();
        if ($status['initialized'] !== true) {
            $this->warn('Vault is not initialized. Please run the "create" command');
            return false;
        }

        if ($this->Vault->sys()->sealed()) {
            $this->warn('Vault is not unsealed. Please run the "unseal" command');
            return true;
        }

        $this->success('Vault is initialized and unsealed');
        return true;
    }

    /**
     * Create a new Vault instance
     *
     * - initialize vault and save the response in ~/.bownty_vault_keys
     *
     * @see https://www.vaultproject.io/docs/http/sys-init.html
     * @return void
     */
    public function create()
    {
        if ($this->status()) {
            $this->success('Vault already initialized');
            return false;
        }

        $response = $this->Vault->sys()->init([
            'secret_shares' => 5,
            'secret_threshold' => 3
        ])->json();

        $file = env('HOME') . '/.bownty_vault_keys';
        file_put_contents($file, json_encode($response, JSON_PRETTY_PRINT));

        $this->success('Vault is initialized, keys have been written to ' . $file);
        $this->success('Please run "unseal" to start working with Vault');
        return true;
    }

    /**
     * Seal the vault
     *
     * @return void
     */
    public function seal()
    {
        $this->Vault->seal();
        $this->success('Vault has been sealed. Call "unseal" to unseal it again.');
    }

    /**
     * Unseal the vault using the keys provided
     *
     * @return void
     */
    public function unseal()
    {
        $this->Vault->unseal();
    }

    /**
     * Export ENV variables for Vault CLI usage
     *
     * @return void
     */
    public function env()
    {
        $token = $this->Vault->getVaultToken();
        if (empty($token)) {
            return $this->abort('Token file does not exist - call "create" first');
        }

        $this->out('export VAULT_ADDR="http://127.0.0.1:8200" VAULT_TOKEN="' . $token . '";');
        $this->out('echo "You can now execute Vault commands using the \"vault\" CLI tool";');
    }

    /**
     * Create the required mount points
     *
     * @return void
     */
    public function mounts()
    {
        $config = yaml_parse(file_get_contents(CONFIG . 'vault.yml'));
        $mounts = $this->Vault->sys()->mounts()->json();

        foreach ($config['secret_backends'] as $backend) {
            $this->out('Backend: ' . $backend['path']);

            if (!array_key_exists($backend['path'] . DS, $mounts)) {
                $this->info('  Creating ...');
                $resp = $this->Vault->sys()->createMount($backend['path'], [
                    'type' => $backend['type'],
                    'description' => isset($backend['description']) ? $backend['description'] : ''
                ]);
            } else {
                $this->success('  Already exist');
            }

            switch ($backend['type']) {
                case 'mysql':
                    $this->_mysqlBackend($backend);
                    break;

                default:
                    $this->_secretBackend($backend);
                    break;
            }

            $this->out('');
        }
    }

    /**
     * Specific handler for "secret" secret backends
     *
     * @param  array $backend
     * @return void
     */
    protected function _secretBackend($backend)
    {
        $data = $this->Vault->data();
        foreach ($backend['secrets'] as $secret) {
            $this->info('  ' . $secret['path']);
            $data->write('secret/' . $secret['path'], $secret['payload']);
        }
    }

    /**
     * Specific handler for MySQL secret backends
     *
     * @param  array $backend
     * @return void
     */
    protected function _mysqlBackend(array $backend)
    {
        if (empty($backend['config']['ini_file'])) {
            return $this->abort('Missing config.ini_file key for mysql backend');
        }
        if (!is_file($backend['config']['ini_file'])) {
            return $this->abort('ini file do not exist (' . $backend['config']['ini_file'] . ')');
        }

        $ini = parse_ini_file($backend['config']['ini_file']);
        $connection_url = sprintf('%s:%s@unix(/var/run/mysqld/mysqld.sock)/', $ini['user'], $ini['password']);

        $data = $this->Vault->data();
        $this->info('  Writing connection string');
        $max_open_connections = 10;
        $data->write('mysql/config/connection', compact('connection_url', 'max_open_connections'));

        $this->info('  Writing lease configuration');
        $data->write('mysql/config/lease', ['lease' => '1h', 'lease_max' => '24h']);

        $this->info('  Writing role configuration(s)');
        foreach ($backend['roles'] as $role) {
            $this->info('    ' . $role['name']);
            $data->write('mysql/roles/' . $role['name'], ['sql' => join(';', $role['sql'])]);
        }

    }
}
