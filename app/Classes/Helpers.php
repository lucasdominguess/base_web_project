<?php
namespace App\Classes;


use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

trait Helpers
{
    //Retorna os dados do usuário pelo jwt token
    public static function getJwtUserData() : array{
        try {
           $payload = JWTAuth::parseToken()->getPayload();

            $userData = $payload->get('user');
            return is_array($userData) ? $userData : [];
        } catch (\Exception $e) {
            Log::error('GetJwtUserData: '.$e->getMessage());
          throw new \Exception($e->getMessage());
        }
    }
    /**
     * Limpa os dados de paginaçao de um array.
     *
     * @param array $data Array com os dados de paginaçao.
     * @return array Array sem os dados de paginaçao.
     */
public function cleanPagination(array $data): array {

    unset($data['per_page'], $data['page'], $data['limit'], $data['offset'],$data['ext'],$data['links']);

    return $data;

}
}
