<?php
namespace Jippi\Vault\Services\Auth;

use Jippi\Vault\Client;
use Jippi\Vault\OptionsResolver;

/**
 * This service class handle all Vault HTTP API endpoints starting in /auth/token
 *
 */
class Token
{
    /**
     * Client instance
     *
     * @var Client
     */
    private $client;

    /**
     * Create a new Sys service with an optional Client
     *
     * @param Client|null $client
     */
    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    /**
     * Creates a new token.
     *
     * Certain options are only available when called by a root token.
     *
     * If used via the /auth/token/create-orphan endpoint, a root token is not required to create an orphan token
     * (otherwise set with the no_parent option). If used with a role name in the path, the token will be created
     * against the specified role name; this may override options set during this call.
     *
     * @see    https://www.vaultproject.io/docs/auth/token.html
     * @param  array  $body
     * @return mixed
     */
    public function create(array $body = [])
    {
        $body = OptionsResolver::resolve($body, [
            'id', 'policies', 'meta', 'no_parent', 'no_default_policy',
            'renewable', 'ttl', 'explicit_max_ttl', 'display_name', 'num_uses'
        ]);

        $params = [
            'body' => json_encode($body)
        ];

        return $this->client->post('/v1/auth/token/create', $params)->json();
    }

    /**
     * Returns information about the current client token.
     *
     * @see    https://www.vaultproject.io/docs/auth/token.html
     * @return mixed
     */
    public function lookupSelf()
    {
        return $this->client->get('/v1/auth/token/lookup-self');
    }

    /**
     * Returns information about the client token provided in the request path
     *
     * @see    https://www.vaultproject.io/docs/auth/token.html
     * @param  string $token
     * @return mixed
     */
    public function lookup($token)
    {
        return $this->client->get('/v1/auth/token/lookup/' . $token);
    }

    /**
     * Renews a lease associated with the calling token.
     *
     * This is used to prevent the expiration of a token, and the automatic revocation of it.
     *
     * Token renewal is possible only if there is a lease associated with it.
     *
     * @see    https://www.vaultproject.io/docs/auth/token.html
     * @param  array  $body
     * @return mixed
     */
    public function renewSelf(array $body = [])
    {
        $body = OptionsResolver::resolve($body, ['increment']);
        $params = ['body' => json_encode($body)];
        return $this->client->post('/v1/auth/token/renew-self', $params);
    }

    /**
     * Renews a lease associated with a token.
     *
     * This is used to prevent the expiration of a token, and the automatic revocation of it.
     *
     * Token renewal is possible only if there is a lease associated with it.
     *
     * @see    https://www.vaultproject.io/docs/auth/token.html
     * @param  array  $body
     * @return mixed
     */
    public function renew(array $body = [])
    {
        $body = OptionsResolver::resolve($body, ['token', 'increment']);
        $params = ['body' => json_encode($body)];
        return $this->client->post('/v1/auth/token/renew', $params);
    }

    /**
     * Revokes a token and all child tokens.
     *
     * When the token is revoked, all secrets generated with it are also revoked.
     *
     * @see    https://www.vaultproject.io/docs/auth/token.html
     * @param  array  $body
     * @return mixed
     */
    public function revoke(array $body = [])
    {
        $body = OptionsResolver::resolve($body, ['token']);
        $params = ['body' => json_encode($body)];
        return $this->client->post('/v1/auth/token/revoke', $params);
    }

    /**
     * Revokes a token and all child tokens.
     *
     * When the token is revoked, all secrets generated with it are also revoked.
     *
     * @see    https://www.vaultproject.io/docs/auth/token.html
     * @param  array  $body
     * @return mixed
     */
    public function revokeSelf(array $body = [])
    {
        return $this->client->post('/v1/auth/token/revoke-self');
    }

    /**
     * Revokes a token but not its child tokens.
     *
     * When the token is revoked, all secrets generated with it are also revoked.
     *
     * All child tokens are orphaned, but can be revoked sub-sequently using /auth/token/revoke/.
     *
     * This is a root-protected endpoint.
     *
     * @see    https://www.vaultproject.io/docs/auth/token.html
     * @param  array  $body
     * @return mixed
     */
    public function revokeOrphan(array $body = [])
    {
        return $this->client->post('/v1/auth/token/revoke-orphan');
    }

    /**
     * Deletes the named role.
     *
     * @see    https://www.vaultproject.io/docs/auth/token.html
     * @param  string $role
     * @return mixed
     */
    public function deleteRole(string $role)
    {
        return $this->client->delete('/v1/auth/token/roles/' . $role);
    }

    /**
     * Fetches the named role configuration
     *
     * @see    https://www.vaultproject.io/docs/auth/token.html
     * @param  string $role
     * @return mixed
     */
    public function getRole(string $role)
    {
        return $this->client->get('/v1/auth/token/roles/' . $role);
    }

    /**
     * Lists available roles.
     *
     * @see    https://www.vaultproject.io/docs/auth/token.html
     * @return mixed
     */
    public function listRoles()
    {
        return $this->client->get('/v1/token/roles?list=true');
    }

    /**
     * Creates (or replaces) the named role.
     *
     * Roles enforce specific behavior when creating tokens that allow token functionality that is otherwise not
     * available or would require sudo/root privileges to access.
     *
     * Role parameters, when set, override any provided options to the create endpoints.
     *
     * The role name is also included in the token path, allowing all tokens created against a role to be revoked
     * using the sys/revoke-prefix endpoint.
     *
     * @see    https://www.vaultproject.io/docs/auth/token.html
     * @return mixed
     */
    public function createRole(string $role, array $body = [])
    {
        $body = OptionsResolver::resolve($body, ['allowed_policies', 'orphan', 'period', 'renewable', 'path_suffix', 'explicit_max_ttl']);
        $params = ['body' => json_encode($body)];
        return $this->client->post('/v1/auth/token/roles/' . $role, $params);
    }

}
