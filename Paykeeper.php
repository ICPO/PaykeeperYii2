<?php

namespace app\models;


use yii\web\ServerErrorHttpException;

/**
 * Класс для paykeeper :D
 * Если есть время, можно зарефакторить curl запросы или обработчик ошибок сделать, а не throw
 * Class Paykeeper
 * @package app\models
 *
 *
 * Как перевести в тестовый режим ?
 * - Писать в поддержку Paykeeper. По другому у них никак. Точно также и для боевого режима..
 *
 */
class Paykeeper
{
    private $login = '';
    private $password = '';
    private $server = '';
    private $headers = false;
    private $secure_token;

    const GET_TOKEN_URI = '/info/settings/token/';
    const GET_INVOICE_URI = '/change/invoice/preview/';


    public function __construct($lk_login, $lk_password, $server)
    {
        if (strpos($server, 'demo')) throw new ServerErrorHttpException('Демо режим не поддерживается в этом классе');
        $this->login = $lk_login;
        $this->password = $lk_password;
        $this->server = $server;
    }


    private function validateLoginPassword()
    {
        if (empty($this->login) || empty($this->password) || empty($this->server)) {
            throw new ServerErrorHttpException('Логин, сервер или пароль от ЛК PayKeeper не заполнены');
        }
        return true;
    }

    public function makeHeaders()
    {
        if ($this->validateLoginPassword()) {
            $this->headers = [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode("{$this->login}:{$this->password}")
            ];
        }
    }


    public function setSecureToken()
    {
        if (!$this->headers) throw  new ServerErrorHttpException('Headers пуст у PayKeeper');

        # Для сетевых запросов в этом примере используется cURL
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $this->server . self::GET_TOKEN_URI);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curl, CURLOPT_HEADER, false);

        # Инициируем запрос к API
        $response = curl_exec($curl);
        $php_array = json_decode($response, true);
        # В ответе должно быть заполнено поле token, иначе - ошибка
        if (isset($php_array['token'])) $token = $php_array['token']; else throw new ServerErrorHttpException('Ошибка получения Secure Token PayKeeper. Ошибка - ' . $response['msg']);

        $this->secure_token = $token;

    }

    public function getSecureToken()
    {
        return $this->secure_token;
    }

    public function getInvoiceLink($payment_data = [])
    {
        if (!$this->secure_token) throw new ServerErrorHttpException('Сначала нужно сформировать защитный токен Secure Token PayKeeper');
        if (!$this->headers) throw  new ServerErrorHttpException('Сначала нужно сформировать Headers PayKeeper');

        $request = http_build_query(array_merge($payment_data, ['token' => $this->secure_token]));

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $this->server . self::GET_INVOICE_URI);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

        $response = json_decode(curl_exec($curl), true);

        # В ответе должно быть поле invoice_id, иначе - ошибка
        if (isset($response['invoice_id'])) $invoice_id = $response['invoice_id']; else throw new ServerErrorHttpException('Ошибка получения invoice id PayKeeper. Ошибка - ' . $response['msg']);

        return "https://{$this->server}/bill/{$invoice_id}/";
    }

}
