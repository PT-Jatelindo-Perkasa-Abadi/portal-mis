<?php
class App_Service_Api
{
    protected $baseUrl;
    protected $apiKey;
    protected $clientSecret;
    protected $accessToken;
    protected $userAuth;
    protected $userPassword;
    protected $logger;
    protected $activityId;

    public function __construct()
    {
        $config = Zend_Registry::get('config');
        $this->baseUrl = rtrim($config->api->baseUrl, '/');
        $this->apiKey = rtrim($config->api->apiKey, '/');
        $this->clientSecret = rtrim($config->api->clientSecret, '/');
        $this->userAuth = rtrim($config->api->userAuth);
        $this->userPassword = rtrim($config->api->userPassword);
        $this->logger = Zend_Registry::get('logger');
        $this->activityId = Zend_Registry::get('activity_id');
    }

    private function getBasicAuthHeader()
    {
        return 'Basic ' . base64_encode($this->userAuth . ':' . $this->userPassword);
    }

    public function setToken($token)
    {
        $this->accessToken = $token;
    }

    public function request($method, $uri, $payload = [], $isLoggedIn = true)
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($uri, '/');
        $client = new Zend_Http_Client($url);
        $signature = $this->accessToken . $this->clientSecret . json_encode($payload);
        $headers = [
            'Authorization' => "Bearer $this->accessToken",
            'X-API-KEY' => $this->apiKey,
            'X-SIGNATURE' => hash("sha256", $signature),
            'Content-Type' => 'application/json',
        ];

        // if ($isLoggedIn) {
        //     $headers['X-SIGNATURE'] = hash("sha256", $signature);
        // }

        $client->setHeaders($headers);

        if (!empty($payload)) {
            $client->setRawData(json_encode($payload));
        }

        $ip = App_Log_Context::getIp();
        $maskedBody = App_Log_Context::mask($payload);
        $maskedHeaders = App_Log_Context::mask($headers);
        $jsonHeader = json_encode($maskedHeaders);
        $jsonBody = json_encode($maskedBody);
        $this->logger->info("IP: $ip | ACTIVITY_ID: $this->activityId | REQUEST TO $url METHOD=$method BODY=$jsonBody HEADER=$jsonHeader");

        try {
            $response = $client->request($method);
            $responseJson = $response->getBody();
            $responseArray = json_decode($responseJson, true);
            $httpCode = $responseArray['responseCode'] ?? 'unknown';

            $maskedResponse = App_Log_Context::mask($responseArray);
            $this->logger->info("IP: $ip | ACTIVITY_ID: $this->activityId | RESPONSE FROM $url METHOD=$method STATUS=$httpCode BODY=" . json_encode($maskedResponse));

            return json_decode($responseJson, true);

        } catch (Exception $e) {
            $httpCode = $e->getCode();
            $errorMessage = $e->getMessage();
            $this->logger->info("IP: $ip | ACTIVITY_ID: $this->activityId | RESPONSE FROM $url METHOD=$method STATUS=$httpCode MESSAGE=$errorMessage");

            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

    public function sp($spName, $params = [])
    {
        return $this->request('POST', '/service/store-procedure', [
            'SpName' => "$spName",
            'SpParams' => $params
        ]);
    }

    // public function inquiryBulk($payload = [])
    // {
    //     return $this->request('POST', '/service/inquiry-bulk', $payload);
    // }

    public function authorization()
    {
        $path = '/auth/login';
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
        $client = new Zend_Http_Client($url);
        $payload = [
            "Email" => "kanto@jatelindo.co.id",
            "Password" => "123456"
        ];
        $headers = [
            'X-API-KEY' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->getBasicAuthHeader())
        ];
        $client->setAuth($this->userAuth, $this->userPassword);
        $client->setHeaders($headers);

        if (!empty($payload)) {
            $client->setRawData(json_encode($payload), true);
        }

        $ip = App_Log_Context::getIp();
        $jsonHeader = json_encode($headers);
        $jsonBody = json_encode($payload);
        $this->logger->info("IP: $ip | ACTIVITY_ID: $this->activityId | REQUEST TO $url METHOD=POST BODY=$jsonBody HEADER=$jsonHeader");

        try {
            $response = $client->request('POST');
            $responseJson = $response->getBody();
            $responseArray = json_decode($responseJson, true);
            $httpCode = $responseArray['code'] ?? 'unknown';

            $maskedResponse = App_Log_Context::mask($responseArray);
            $this->logger->info("IP: $ip | ACTIVITY_ID: $this->activityId | RESPONSE FROM $url METHOD=GET STATUS=$httpCode BODY=" . json_encode($maskedResponse));

            if ($httpCode == '200') {
                $this->setToken($responseArray['msg']['access_token']);
            } else {
                throw new Exception($responseArray['responseMessage']);
            }

            return json_decode($responseJson, true);

        } catch (Exception $e) {
            $httpCode = $e->getCode();
            $errorMessage = $e->getMessage();
            $this->logger->info("IP: $ip | ACTIVITY_ID: $this->activityId | RESPONSE FROM $url METHOD=GET STATUS=$httpCode MESSAGE=$errorMessage");

            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }
}