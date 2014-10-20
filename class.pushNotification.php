<?php
/**
 * Onur Canalp - 13 Ekim 2014
 *
 * Mobil aygıtlara notification göndermek için..
 * GMC - Google Mobile Cloud Push Notification
 * APN - Apple Push Notification
 */

class pushNotification {

    private $gmcApiKey = 'xxxxxxxxxxxxx'; //GOOGLE
    private $apnPassPhrase = 'onur12345'; //Apple
    private $apnPemFile = 'APNS.pem';
    private $errorMessages = "";

    public function __construct() {
        $this->apnPemFile = dirname(__FILE__).'/'.$this->apnPemFile;

        if (!file_exists($this->apnPemFile)) {
            echo "PEM Not Found! "; exit();
        }

	}

    //Hazırlayalım ve gönderelim
    public function prepareAndSend($message = "", $devices =  array()){

        $data['d'] = $message['d'];
        $data['m'] = $message['m'];

        if(isset($message['i'])){
            $data['i'] = $message['i'];
        }

        //ayıklayalım aygıtları ios - android
        foreach($devices as $key => $device){
            if($device['type'] == 'ios'){
                $iosIDS[] = $device['id'];
            }
            else{
                $androidIDS[] = $device['id'];
            }
        }

        //android cihazlara gönderelim
        if (count($androidIDS) > 0){
            $return['android'] = $this->sendGoogleCloudMessage($data, $androidIDS);
        }

        //ios cihazlara gönderelim
        if (count($iosIDS) > 0){
            //sadece ios da badge kavramı var..
            if(isset($message['badge'])){
                $data['badge'] = $message['badge'];
            }

            $return['ios'] = $this->sendAPNS($data, $iosIDS);
        }

        return $return;
    }

    public function sendGoogleCloudMessage( $message, $ids )
    {

        $url = 'https://android.googleapis.com/gcm/send';

        $post = array(
            'registration_ids'  => $ids,
            'data'              => $message,
        );

        $headers = array(
            'Authorization: key=' . $this->gmcApiKey,
            'Content-Type: application/json'
        );

        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $post ) );

        $result = curl_exec( $ch );

        if ( curl_errno( $ch ) )
        {
            $result['result'] = array('result' => false, 'hata' => 'GCM error: ' . curl_error( $ch ));
        }

        curl_close( $ch );

        return json_decode($result);
    }

    public function sendAPNS($message = "", $ids = array()){

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $this->apnPemFile);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $this->apnPassPhrase);

        // Open a connection to the APNS server
        $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp){
            $this->errorMessages = "Failed to connect: $err $errstr" . PHP_EOL;
            return false;
        }


        //Body
        $body['aps']['alert'] = $message['m'];
        $body['aps']['d'] = $message['d'];
        if(isset($message['badge'])){
            $body['aps']['badge'] = $message['badge'];
        }
        if(isset($message['i'])){
            $body['aps']['i'] = $message['i'];
        }

        $apnsBodyContent = json_encode($body);
        $errorNum = 0;
        foreach($ids as $deviceToken){
            $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($apnsBodyContent)) . $apnsBodyContent;

            // Send it to the server
            $result = fwrite($fp, $msg, strlen($msg));

            if (!$result){
                $errorNum++;
                $errors[] = 'Message not delivered'. $deviceToken . PHP_EOL;
            }
        }

        // Close the connection to the server
        fclose($fp);

        if($errorNum > 0){
            //detaylı bilgi istenirse
            $this->errorMessages = $errors;

            $object = new stdClass();
            $object->success = 0;
            $object->failure = $errorNum;
            $object->results = $errors;
        }
        else{
            $object = new stdClass();
            $object->success = 1;
            $object->failure = 0;
        }

        return $object;
    }

    public function getAllDevices(){
        global $db;

        $devices = $db->fetchAll("SELECT device_id, device_type FROM mobile_devices GROUP BY device_id");
        foreach($devices as $device){
            $ret[] = array('id' => $device['device_id'], 'type' => $device['device_type']);
        }

        unset($devices);

        return $ret;
    }
}
