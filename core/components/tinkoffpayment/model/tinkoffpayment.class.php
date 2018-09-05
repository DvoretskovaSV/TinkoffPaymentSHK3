<?php

class TinkoffPayment
{
    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    public $modx;

    /**
     * The namespace
     * @var string $namespace
     */
    public $namespace = 'tinkoffpayment';

    /**
     * The version
     * @var string $version
     */
    public $version = '1.0';

    /**
     * The class config
     * @var array $config
     */
    public $config = array();

    /**
     *  Payment options
     * @var array $params
     */

    public $params = array();

    /**
     *  response from the server Tinkoff
     * @var array $response
     */

    public $response = array();

    /**
     * TinkoffPayment1 constructor
     *
     * @param modX $modx A reference to the modX instance.
     * @param array $options An array of options. Optional.
     */

    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = [])
    {
        $this->modx =& $modx;
        $corePath = MODX_CORE_PATH . 'components/tinkoffpayment/';
        $assetsUrl = MODX_ASSETS_URL . 'components/tinkoffpayment/';

        $this->config = array_merge([
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',

            'connectorUrl' => $assetsUrl . 'connector.php',
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
        ], $config);

        $this->modx->lexicon->load('tinkoffpayment:default');
        $this->_getParams();
    }

    function _getParams()
    {
        $this->params['api_url'] = 'https://securepay.tinkoff.ru/v2/Init/';
        $this->params['terminal_key'] = $this->modx->getOption($this->namespace . '_terminal_key');
        $this->params['secret_key'] = $this->modx->getOption($this->namespace . '_secret_key');
        $this->params['notification_url'] = $this->modx->getOption($this->namespace . '_notification_url');
    }

    /**
     * Send Request
     *
     * @param array $args
     *
     * @return boolean $out
     */
    function sendRequest($args)
    {
        if (is_array($args)) {
            $args['TerminalKey'] = $this->params['terminal_key'];
            $args['Token'] = $this->_getToken(
                array(
                    'Description' => (isset ($args['Description'])) ? $args['Description'] : '',
                    'Amount' => (isset ($args['Amount'])) ? $args['Amount'] : '0',
                    'OrderId' => (isset ($args['OrderId'])) ? $args['OrderId'] : '0',
                    'TerminalKey' => $this->params['terminal_key'],
                    'Password' => $this->params['secret_key']
                )
            );
        }

        if (is_array($args)) {
            $args = $this->modx->toJSON($args);
        }


        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $this->params['api_url']);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            ));

            $out = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            $response = $this->modx->fromJSON($out);
            if ($response && $httpcode == '200') {
                $errorCode = $response['ErrorCode'];
                if ($response['ErrorCode'] !== '0') {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, 'Ошибка оплаты Тинькофф(' . $errorCode . '): ' . $response['Message']);
                    $this->response['error'] = '(' . $errorCode . ') ' . $response['Details'];
                    $this->log($this->response['error'], true);
                } else {
                    $this->response['paymentUrl'] = $response['PaymentURL'];
                    $this->response['paymentId'] = $response['PaymentId'];
                    $this->response['status'] = $response['Status'];
                }
            }
            return $out;
        }
    }

    /**
     * Notification Request
     *
     * @param string $paramsResponce , array $dataResponce
     *
     * @return boolean
     */
    public function notification($tokenResponce, $dataResponce)
    {
        unset($dataResponce["Token"]);

        $dataResponce['Success'] = (int)$dataResponce['Success'];
        $dataResponce['Success'] = ($dataResponce['Success'] > 0) : (string)'true' ? (string)'false'? 
            
        $dataResponce['Password'] = $this->params['secret_key'];

        $token = $this->_getToken($dataResponce);

        if ($tokenResponce == $token) {
            return true;
        }

        return false;
    }

    private function _getToken($args)
    {
        $token = '';
        ksort($args);

        foreach ($args as $arg) {
            if (!is_array($arg)) {
                $token .= $arg;
            }
        }
        $token = hash('sha256', $token);

        return $token;
    }

    public function log($data, $error = false)
    {
        $log_file = MODX_CORE_PATH . 'components/tinkoffpayment/log.txt';
        $current = date('d.m.Y H:i:s ');

        if(empty($error)) {
            $current .= "Details payment: " ;
            $data = json_encode($data);
        }
        else{
            $current .= "Error payment: ";
        }

        file_put_contents($log_file, $current . $data. PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
