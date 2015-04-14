<?php

/**
 * SMS Gateway handler class
 *
 * @author satosms
 */
class SatSMS_SMS_Gateways {

    private static $_instance;

    public static function init() {
        if ( !self::$_instance ) {
            self::$_instance = new SatSMS_SMS_Gateways();
        }

        return self::$_instance;
    }

    function talkwithtext( $sms_data ) {
        $username = satosms_get_option( 'talkwithtext_username', 'satosms_gateway', '' ); 
        $password = satosms_get_option( 'talkwithtext_password', 'satosms_gateway', '' ); 
        $originator = satosms_get_option( 'talkwithtext_originator', 'satosms_gateway', '' ); 
        $admin_phone = str_replace( '+', '', $sms_data['number'] );

        if( empty( $username ) || empty( $password ) ) {
            return;
        }

        require_once dirname( __FILE__ ) . '/../lib/sms.php';
        $sol4mob_sms=   new sms();     
        $sol4mob_sms->username= $username;
        $sol4mob_sms->password= $password;
        $sol4mob_sms->originator= $originator;
        $sol4mob_sms->msgtext= $sms_data['sms_body']; 
        $sol4mob_sms->phone= $admin_phone;
        $response = $sol4mob_sms->send();  
        
        if( $response == 'OK' ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sends SMS via SMSGlobal api
     *
     * @param array $sms_data
     * @return boolean
     */
    function smsglobal( $sms_data ) {
        $response = false;

        $username = satosms_get_option( 'smsglobal_name', 'satosms_gateway' );
        $password = satosms_get_option( 'smsglobal_password', 'satosms_gateway' );
        $from = satosms_get_option( 'smsglobal_sender_no', 'satosms_gateway' );
        $phone = str_replace( '+', '', $sms_data['number'] );
        //bail out if no username or password given
        if ( empty( $username ) || empty( $password ) ) {
            return $response;
        }

        $content = 'action=sendsms' .
                '&user=' . rawurlencode( $username ) .
                '&password=' . rawurlencode( $password ) .
                '&to=' . rawurlencode( $phone ) .
                '&from=' . rawurlencode( $from ) .
                '&text=' . rawurlencode( $sms_data['sms_body'] );

        $smsglobal_response = file_get_contents( 'http://www.smsglobal.com.au/http-api.php?' . $content );

        $explode_response = explode( 'SMSGlobalMsgID:', $smsglobal_response );

        if ( count( $explode_response ) == 2 ) {
            $response = true;
        }

        return $response;
    }

    /**
     * Sends SMS via Clickatell api
     *
     * @param type $sms_data
     * @return boolean
     */
    function clickatell( $sms_data ) {

        $response = false;
        $username = satosms_get_option( 'clickatell_name', 'satosms_gateway' );
        $password = satosms_get_option( 'clickatell_password', 'satosms_gateway' );
        $api_key = satosms_get_option( 'clickatell_api', 'satosms_gateway' );
        $phone = str_replace( '+', '', $sms_data['number'] );
        $text = urlencode( $sms_data['sms_body'] );

        //bail out if nothing provided
        if ( empty( $username ) || empty( $password ) || empty( $api_key ) ) {
            return $response;
        }

        // auth call
        $baseurl = "http://api.clickatell.com";
        $url = sprintf( '%s/http/auth?user=%s&password=%s&api_id=%s', $baseurl, $username, $password, $api_key );

        // do auth call
        $ret = file( $url );

        // explode our response. return string is on first line of the data returned
        $sess = explode( ":", $ret[0] );
        if ( $sess[0] == "OK" ) {

            $sess_id = trim( $sess[1] ); // remove any whitespace
            $url = sprintf( '%s/http/sendmsg?session_id=%s&to=%s&text=%s', $baseurl, $sess_id, $phone, $text );

            // do sendmsg call
            $ret = file( $url );
            $send = explode( ":", $ret[0] );

            if ( $send[0] == "ID" ) {
                $response = true;
            }
        }

        return $response;
    }

    /**
     * Sends SMS via Nexmo api
     *
     * @param type $sms_data
     * @return boolean
     */
    function nexmo( $sms_data ) {

        $response =  false;

        $api_key = satosms_get_option( 'nexmo_api', 'satosms_gateway' );
        $api_secret = satosms_get_option( 'nexmo_api_secret', 'satosms_gateway' );
        $from = satosms_get_option( 'nexmo_from_name', 'satosms_gateway' );

        require_once dirname( __FILE__ ) . '/../lib/NexmoMessage.php';

        $nexmo_sms = new NexmoMessage( $api_key, $api_secret);
        $info = $nexmo_sms->sendText( $sms_data['number'], $from, $sms_data['sms_body'] );

        if ( $info->messages[0]->status == '0' ) {
            $response = true;
        }

        return $response;
    }

    /**
     * Sends SMS via Twillo api
     *
     * @param type $sms_data
     * @return boolean
     */
    function twilio( $sms_data ) {
 
        $sid = satosms_get_option( 'twilio_sid', 'satosms_gateway' );
        $token = satosms_get_option( 'twilio_token', 'satosms_gateway' );
        $from = satosms_get_option( 'twilio_from_number', 'satosms_gateway' );
        
        require_once dirname( __FILE__ ) . '/../lib/twilio/Twilio.php';

        $client = new Services_Twilio( $sid, $token );

        try {
            $message = $client->account->sms_messages->create(
                    $from, '+' . $sms_data['number'], $sms_data['sms_body']
            );
            
            if ( $message->status != 'failed' ) {
                return true;
            }
        } catch (Exception $exc) {
            return false;
        }
    }

}
