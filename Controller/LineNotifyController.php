<?php
class LineNotifyController
{

    private $tokenLine;
    public function __construct()
    {
        $this->tokenLine = $_ENV["TOKEN_NOTIFY"];
    }
    public function alertNotify($psthPartNo,$date,$time,$inspector,$mold,$lotNo)
    {
        
        $status = 'NG';

        $token = $this->tokenLine; // LINE Token
        //Message
        $mymessage = "🔔 แจ้งเตือนงาน NG \n"; //Set new line with '\n'
        $mymessage .= "PSTH Part No : `" . $psthPartNo . " #".$mold."` \n";
        $mymessage .= "Lot No : `" . $lotNo ."` \n";
        $mymessage .= "วันที่ : " .  $date  . " \n";
        $mymessage .= "รอบเวลา : " . $time . " น." . " \n";
        $mymessage .= "สถานะ : " . $status . " \n";
        $mymessage .= "Inspector : " . $inspector . "\n";
        $mymessage .= "ตรวจสอบคลิก -> : " . "http://localhost:3030" . "\n";

        $data = array(
            'message' => $mymessage,
        );
        $chOne = curl_init();
        curl_setopt($chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify");
        curl_setopt($chOne, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($chOne, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($chOne, CURLOPT_POST, 1);
        curl_setopt($chOne, CURLOPT_POSTFIELDS, $data);
        curl_setopt($chOne, CURLOPT_FOLLOWLOCATION, 1);
        $headers = array('Method: POST', 'Content-type: multipart/form-data', 'Authorization: Bearer ' . $token,);
        curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($chOne, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($chOne);
        //Check error
        if (curl_error($chOne)) {
            echo 'error:' . curl_error($chOne);
        } else {
            $result_ = json_decode($result, true);
            // echo json_encode(["err" => false, "status" => $result_['status'], "message" => $result_['message']]);
        }
        //Close connection
        curl_close($chOne);
    }
}
