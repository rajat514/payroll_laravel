<?php

namespace App\Helper;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private $mail;

    public function __construct()
    {
        // return response()->json(['errorMsg' => 'User not found with this email address.', [$this->mail]], 404);
        $this->mail             = new PHPMailer(true);
        $this->mail->SMTPDebug  = 0;
        $this->mail->Host       = env('MAIL_HOST');             //  smtp host
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = env('MAIL_USERNAME'); //'nrise@nioh.org.in';   //  sender username
        $this->mail->Password   = env('MAIL_PASSWORD'); //'RHYfm!als48t';       // sender password
        $this->mail->SMTPSecure = env('MAIL_SMTPSECURE');                  // encryption - ssl/tls
        $this->mail->Port       = env('MAIL_PORT');
        $this->mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        $this->mail->isSMTP();
        $this->mail->isHTML(true);

        // Add timeout to prevent hanging
        $this->mail->Timeout = 30; // 30 seconds timeout
        $this->mail->SMTPKeepAlive = false;
    }

    // function sendMail($data)
    // {
    //     try {
    //         return response()->json(['errorMsg' => 'User not found with this email address.', [$data]], 404);
    //         $this->mail->addAddress($data['to']);
    //         $this->mail->Subject = $data['subject'];
    //         $this->mail->Body    = $data['body'];

    //         return $this->mail->send();
    //     } catch (Exception $e) {
    //         // \Log::error('Mailer sendMail failed: ' . $e->getMessage());
    //         return false;
    //     }
    // }
    function sendMail($data)
    {
        // return response()->json(['errorMsg' => 'User not found with this email address.', [$data]], 404);
        $this->mail->addAddress($data['to']);
        $this->mail->Subject = $data['subject'];
        $this->mail->Body    = $data['body'];


        return $this->mail->send();
    }
}
