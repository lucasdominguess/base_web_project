<?php

namespace App\Services;

use App\Interfaces\LdapInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use RuntimeException;

class LdapService implements LdapInterface
{
    public function loginLdap(string $username, string $password): array
    {
        $host = config('ldap.host');
        $domain = config('ldap.domain');
        $baseDn = config('ldap.base');

        $connection = @ldap_connect($host);

        if (!$connection) {
            Log::error('Falha na comunicação com o servidor LDAP.');
            return ['message' => 'Falha na comunicação com o servidor LDAP.', 'statusCode' => 500];
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        $ldap = @ldap_bind($connection, "{$domain}\\{$username}", $password);

        if (!$ldap) {
            return ['message' => 'Usuário ou senha inválidos.', 'statusCode' => 401];
        }

        $search = ldap_search($connection, $baseDn, "sAMAccountName={$username}");
        $results = ldap_get_entries($connection, $search);

        if (empty($results[0]['mail'][0])) {
            return ['message' => 'Usuário ou senha inválidos.', 'statusCode' => 401];
        }

        $ldap_mail = $results[0]['mail'][0]; //email
        $ldap_name = $results[0]['cn'][0]; //nome do usuário
        $ldap_login = strtoupper($results[0]['samaccountname'][0]); //login de rede
        return ['email'=>$ldap_mail, 'name'=>$ldap_name, 'login'=>$ldap_login];
    }
}
