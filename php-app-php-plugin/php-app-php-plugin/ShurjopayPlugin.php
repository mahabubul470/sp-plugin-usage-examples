<?php
require_once 'configaration.php';

/**
 *
 * PHP Plug-in service to provide shurjoPay get way services.
 *
 * @author Md Wali Mosnad Ayshik
 * @since 2022-10-15
 */
class ShurjopayPlugin
{

    private $shurjopay_api = SHURJOPAY_API;
    private $return_url = SP_CALLBACK;
    private $prefix = PREFIX;
    private $SP_USER = SP_USERNAME;
    private $SP_PASS = SP_PASSWORD;
    private $log_location = LOG_LOCATION;

    public function __construct()
    {
        $this->domainName = $this->shurjopay_api;
        $this->auth_token_url = $this->domainName . "api/get_token";
        #auth_token_url
        $this->checkout = $this->domainName . "api/secret-pay";
        $this->verification_url = $this->domainName . "api/verification";
    }

    public function authenticate()
    {
        $header = array(
            ''
        );
        //Content-Type: application/json
        #Authinticate
        $postFields = array(
            'username' => $this->SP_USER,
            'password' => $this->SP_PASS,
        );
        if (empty($this->auth_token_url) || empty($postFields)) return null;

        try
        {
            $response = $this->prepareCurlRequest($this->auth_token_url, 'POST', $postFields, $header);
            $this->logInfo("ShurjoPay has been authenticated successfully !" . "\n" . "Authenticate Response:" . json_encode($response));

            # Got object as response from prepareCurlRequest in $response variable
            # and returning that object from here
            return $response;

        }
        catch(Exception $e)
        {
            $this->logInfo("Invalid User name or Password due to shurjoPay authentication.");
            return $e->getMessage();
        }
    }

    public function makePayment($payload)
    {
        $trxn_data = $this->prepareTransactionPayload($payload);
        $header = array(
            'Content-Type:application/json',
            'Authorization: Bearer ' . json_decode($trxn_data)->token
        );

        try
        {
            $response = $this->prepareCurlRequest($this->checkout, 'POST', $trxn_data, $header);
            if (!empty($response->checkout_url))
            {

                $this->logInfo("Payment URL has been generated by shurjoPay!" . "\n" . "MakePayment Response:" . json_encode($response));

                return header('Location: ' . $response->checkout_url);
            }
            else
            {
                return $response; //object
                
            }
        }
        catch(Exception $e)
        {
            return $e->getMessage();
        }

    }
    public function verifyOrder($shurjopay_order_id)
    {
        // echo $order_id;exit;
        $token = json_decode(json_encode($this->authenticate()) , true);
        $header = array(
            'Content-Type:application/json',
            'Authorization: Bearer ' . $token['token']
        );
        $postFields = json_encode(array(
            'order_id' => $shurjopay_order_id
        ));
        try
        {
            $response = $this->prepareCurlRequest($this->verification_url, 'POST', $postFields, $header);
            $this->logInfo("Payment verification is done successfully!" . "\n" . "Verify Order Response:" . json_encode($response));
            return $response; //object
            
        }
        catch(Exception $e)
        {
            return $e->getMessage();
        }
    }
    public function prepareTransactionPayload($payload)
    {
        $token = json_decode(json_encode($this->authenticate()) , true);
        $createpaybody = json_encode(array(
            // store information
            'token' => $token['token'],
            'store_id' => $token['store_id'],
            'prefix' => $this->prefix,
            'currency' => $payload['currency'],
            'return_url' => $this->return_url,
            'cancel_url' => $this->return_url,
            'amount' => $payload['amount'],
            // Order information
            'order_id' => $this->prefix . uniqid() ,
            'discsount_amount' => $payload['discsount_amount'],
            'disc_percent' => $payload['disc_percent'],
            // Customer information
            'client_ip' => $payload['client_ip'],
            'customer_name' => $payload['customer_name'],
            'customer_phone' => $payload['customer_phone'],
            'customer_email' => $payload['email'],
            'customer_address' => $payload['customer_address'],
            'customer_city' => $payload['customer_city'],
            'customer_state' => $payload['customer_state'],
            'customer_postcode' => $payload['customer_postcode'],
            'customer_country' => $payload['customer_country'],
            'value1' => $payload['value1'],
            'value2' => $payload['value2'],
            'value3' => $payload['value3'],
            'value4' => $payload['value4']
        ));

        return $createpaybody;
    }
    public function prepareCurlRequest($url, $method, $payload_data, $header)
    {
        try
        {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => 1,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => $payload_data,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                #If HTTPS not working in local project
                #Please Uncomment |CURLOPT_SSL_VERIFYPEER|
                #NOTE-Please Comment Again before going Live
                //CURLOPT_SSL_VERIFYPEER => 0,
                
            ));
        }
        catch(Exception $e)
        {
            logInfo("ShurjoPay has been failed for preparing Curl request !");
            return $e->getMessage();

        }
        finally
        {
            $response = curl_exec($curl);
            curl_close($curl);
            # here , returning object instead of Json to our core three method
            return (json_decode($response));

        }
    }

    public function logInfo($log_msg)
    {

        $this->wh_log("************** Time'" . gmdate('Y-m-d H:i:s.U \G\M\T') . "'**********");
        $this->wh_log($log_msg);
    }
    public function wh_log($log_msg)
    {
        $log_location = $this->log_location;
        //print_r($log_msg);exit;
        if (!file_exists($log_location . 'shurjopay-plugin-log')) mkdir($log_location . 'shurjopay-plugin-log', 0777, true);
        $log_file_data = $log_location . 'shurjopay-plugin-log' . '/shurjoPay-plugin' . '.log';
        file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
    }
}

