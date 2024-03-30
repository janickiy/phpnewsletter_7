<?php

namespace App\Helpers;

use PHPMailer\PHPMailer;
use App\Models\{Attach, Smtp, Customheaders};
use Illuminate\Support\Facades\Storage;

class SendEmailHelper
{
    private static $subject;

    private static $body;

    private static $email;

    private static $prior;

    private static $name = 'USERNAME';

    private static $templateId = 0;

    private static $subscriberId = 0;

    private static $token = '';

    private static $tracking = true;

    private static $unsub = true;

    /**
     * @return mixed
     */
    public static function getSubject()
    {
        return self::$subject;
    }

    public static function getToken()
    {
        return self::$token;
    }

    /**
     * @return mixed
     */
    public static function getBody()
    {
        return self::$body;
    }

    /**
     * @return mixed
     */
    public static function getEmail()
    {
        return self::$email;
    }

    /**
     * @return mixed
     */
    public static function getPrior()
    {
        return self::$prior;
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return self::$name;
    }

    /**
     * @return int
     */
    public static function getTemplateId()
    {
        return self::$templateId;
    }

    /**
     * @return int
     */
    public static function getSubscriberId()
    {
        return self::$subscriberId;
    }

    /**
     * @param string $subject
     * @return string
     */
    public static function setSubject(string $subject)
    {
        return self::$subject = $subject;
    }

    /**
     * @param string $body
     * @return string
     */
    public static function setBody(string $body)
    {
        return self::$body = $body;
    }

    /**
     * @param string $token
     * @return string
     */
    public static function setToken(string $token)
    {
        return self::$token = $token;
    }

    /**
     * @param string $email
     * @return string
     */
    public static function setEmail(string $email)
    {
        return self::$email = $email;
    }

    /**
     * @param int $prior
     * @return int
     */
    public static function setPrior(int $prior)
    {
        return self::$prior = $prior;
    }

    /**
     * @param string $name
     * @return string
     */
    public static function setName(string $name)
    {
        return self::$name = $name;
    }

    /**
     * @param int $templateId
     * @return int
     */
    public static function setTemplateId(int $templateId)
    {
        return self::$templateId = $templateId;
    }

    /**
     * @param int $subscriberId
     * @return int
     */
    public static function setSubscriberId(int $subscriberId)
    {
        return self::$subscriberId = $subscriberId;
    }

    /**
     * @param string $tracking
     * @return string
     */
    public static function setTracking(string $tracking)
    {
        return self::$tracking = $tracking;
    }

    /**
     * @param string $unsub
     * @return string
     */
    public static function setUnsub(string $unsub)
    {
        return self::$unsub = $unsub;
    }

    /**
     * @param int|null $attach
     * @return array
     * @throws PHPMailer\Exception
     */
    public static function sendEmail(int $attach = null)
    {
        $subject = self::getSubject();
        $body = self::getBody();
        $email = self::getEmail();
        $prior = self::getPrior();
        $name = self::getName();
        $templateId = self::getTemplateId();
        $subscriberId = self::getSubscriberId();
        $token = self::getToken();

        $m = new PHPMailer\PHPMailer();

        if (SettingsHelper::getInstance()->getValueForKey('ADD_DKIM') == 1) {
            $m->DKIM_domain = SettingsHelper::getInstance()->getValueForKey('DKIM_DOMAIN');
            $m->DKIM_private_string = SettingsHelper::getInstance()->getValueForKey('DKIM_PRIVATE');
            $m->DKIM_selector = SettingsHelper::getInstance()->getValueForKey('DKIM_SELECTOR');
            $m->DKIM_passphrase = SettingsHelper::getInstance()->getValueForKey('DKIM_PASSPHRASE');
            $m->DKIM_identity = SettingsHelper::getInstance()->getValueForKey('DKIM_IDENTITY');
        }

        if (SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') == 'smtp') {
            $m->IsSMTP();
            $m->SMTPAuth = true;
            $m->SMTPKeepAlive = true;

            $smtp_q = Smtp::query();

            if ($smtp_q->count() > 1) {
                $smtp_r = $smtp_q->inRandomOrder()->limit(1)->get();
            } else {
                $smtp_r = $smtp_q->limit(1)->get();
            }

            if ($smtp_r) $smtp = $smtp_r->toArray();

            if (isset($smtp[0]['host']) && isset($smtp[0]['port']) && isset($smtp[0]['port']) && isset($smtp[0]['username']) && isset($smtp[0]['password'])) {
                $m->Host = $smtp[0]['host'];
                $m->Port = $smtp[0]['port'];
                $m->From = $smtp[0]['email'];
                $m->Username = $smtp[0]['username'];
                $m->Password = $smtp[0]['password'];

                if ($smtp[0]['secure'] == 'ssl')
                    $m->SMTPSecure = 'ssl';
                elseif ($smtp[0]['secure'] == 'tls')
                    $m->SMTPSecure = 'tls';

                if ($smtp[0]['authentication'] == 'plain')
                    $m->AuthType = 'PLAIN';
                elseif ($smtp[0]['authentication'] == 'cram-md5')
                    $m->AuthType = 'CRAM-MD5';

                $m->Timeout = $smtp[0]['timeout'];
            }
        } elseif (SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') == 'sendmail' && SettingsHelper::getInstance()->getValueForKey('SENDMAIL_PATH') != '') {
            $m->IsSendmail();
            $m->Sendmail = SettingsHelper::getInstance()->getValueForKey('SENDMAIL_PATH');
        } else {
            $m->IsMail();
        }

        $m->CharSet = SettingsHelper::getInstance()->getValueForKey('CHARSET');

        if ($prior == 1)
            $m->Priority = 1;
        elseif ($prior == 2)
            $m->Priority = 5;
        else $m->Priority = 3;

        if (SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') != 'smtp') $m->From = SettingsHelper::getInstance()->getValueForKey('EMAIL');
        $m->FromName = SettingsHelper::getInstance()->getValueForKey('FROM');

        if (SettingsHelper::getInstance()->getValueForKey('LIST_OWNER') != '') $m->addCustomHeader("List-Owner: <" . SettingsHelper::getInstance()->getValueForKey('LIST_OWNER') . ">");
        if (SettingsHelper::getInstance()->getValueForKey('RETURN_PATH') != '') $m->addCustomHeader("Return-Path: <" . SettingsHelper::getInstance()->getValueForKey('RETURN_PATH') . ">");
        if (SettingsHelper::getInstance()->getValueForKey('CONTENT_TYPE') == 'html')
            $m->isHTML(true);
        else
            $m->isHTML(false);

        $subject = str_replace('%NAME%', $name, $subject);
        $subject = SettingsHelper::getInstance()->getValueForKey('RENDOM_REPLACEMENT_SUBJECT') == 1 ? StringHelper::encodeString($subject) : $subject;

        if (SettingsHelper::getInstance()->getValueForKey('CHARSET') != 'utf-8') {
            $subject = iconv('utf-8', SettingsHelper::getInstance()->getValueForKey('CHARSET'), $subject);
        }

        $m->Subject = $subject;

        if (SettingsHelper::getInstance()->getValueForKey('SLEEP') > 0) sleep(SettingsHelper::getInstance()->getValueForKey('SLEEP'));
        if (SettingsHelper::getInstance()->getValueForKey('ORGANIZATION') != '') $m->addCustomHeader("Organization: " . SettingsHelper::getInstance()->getValueForKey('ORGANIZATION'));

        $m->AddAddress($email);

        if (SettingsHelper::getInstance()->getValueForKey('REQUEST_REPLY') == 1 && SettingsHelper::getInstance()->getValueForKey('EMAIL') != '') {
            $m->addCustomHeader("Disposition-Notification-To: " . SettingsHelper::getInstance()->getValueForKey('EMAIL'));
            $m->ConfirmReadingTo = SettingsHelper::getInstance()->getValueForKey('EMAIL');
        }

        if (SettingsHelper::getInstance()->getValueForKey('PRECEDENCE') == 'bulk')
            $m->addCustomHeader("Precedence: bulk");
        elseif (SettingsHelper::getInstance()->getValueForKey('PRECEDENCE') == 'junk')
            $m->addCustomHeader("Precedence: junk");
        elseif (SettingsHelper::getInstance()->getValueForKey('PRECEDENCE') == 'list')
            $m->addCustomHeader("Precedence: list");

        $UNSUB = SettingsHelper::getInstance()->getValueForKey('URL') . "unsubscribe/" . $subscriberId . "/" . $token;
        $unsublink = str_replace('%UNSUB%', $UNSUB, SettingsHelper::getInstance()->getValueForKey('UNSUBLINK'));

        if (self::$unsub) {
            if (SettingsHelper::getInstance()->getValueForKey('SHOW_UNSUBSCRIBE_LINK') == 1 && SettingsHelper::getInstance()->getValueForKey('UNSUBLINK') != '') $body .= "<br><br>" . $unsublink;
            $m->addCustomHeader("List-Unsubscribe: " . $UNSUB);
        }

        foreach (Customheaders::get() as $customheader) {
            $m->addCustomHeader($customheader->name . ": " . $customheader->value);
        }

        $msg = $body;

        $url_info = parse_url(SettingsHelper::getInstance()->getValueForKey('URL'));

        $msg = preg_replace_callback("/%REFERRAL\:(.+)%/isU", function ($matches) {
            return "%URL_PATH%referral/" . base64_encode($matches[1]) . "/%USERID%";
        }, $msg);
        $msg = str_replace('%NAME%', $name, $msg);
        $msg = str_replace('%UNSUB%', $UNSUB, $msg);
        $msg = str_replace('%SERVER_NAME%', $url_info['host'], $msg);
        $msg = str_replace('%USERID%', $subscriberId, $msg);
        $msg = str_replace('%URL_PATH%', SettingsHelper::getInstance()->getValueForKey('URL'), $msg);
        $msg = SettingsHelper::getInstance()->getValueForKey('RANDOM_REPLACEMENT_BODY') == 1 ? StringHelper::encodeString($msg) : $msg;

        if ($attach) {
            foreach (Attach::where('templateId', $attach)->get() as $f) {
                $path = Attach::DIRECTORY . '/' . $f->file_name;

                if (Storage::exists($path)) {
                    $storagePath = Storage::disk('local')->path($path);

                    if (SettingsHelper::getInstance()->getValueForKey('CHARSET') != 'utf-8') $f->name = iconv('utf-8', SettingsHelper::getInstance()->getValueForKey('CHARSET'), $f->name);

                    $ext = pathinfo($f->file_name, PATHINFO_EXTENSION);;
                    $mime_type = StringHelper::getMimeType($ext);

                    $m->AddAttachment($storagePath, $f->name, 'base64', $mime_type);
                }
            }
        }

        if (SettingsHelper::getInstance()->getValueForKey('CHARSET') != 'utf-8') $msg = iconv('utf-8', SettingsHelper::getInstance()->getValueForKey('CHARSET'), $msg);
        if (SettingsHelper::getInstance()->getValueForKey('CONTENT_TYPE') == 'html') {
            if (self::$tracking) {
                $IMG = '<img alt="" border="0" src="' . SettingsHelper::getInstance()->getValueForKey('URL') . 'pic/' . $subscriberId . '_' . $templateId . '" width="1" height="1">';
                $msg .= $IMG;
            }
        } else {
            $msg = preg_replace('/<br(\s\/)?>/i', "\n", $msg);
            $msg = StringHelper::removeHtmlTags($msg);
        }

        $m->Body = $msg;

        if (!$m->Send()) {
            $result = ['result' => false, 'error' => $m->ErrorInfo];
        } else {
            $result = ['result' => true, 'error' => null];
        }

        $m->ClearCustomHeaders();
        $m->ClearAllRecipients();
        $m->ClearAttachments();

        if (SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') == 'smtp') $m->SmtpClose();

        return $result;
    }

    /**
     * @param string $host
     * @param string $email
     * @param string $username
     * @param string $password
     * @param int $port
     * @param string $authentication
     * @param string $secure
     * @param int $timeout
     * @return bool
     * @throws PHPMailer\Exception
     */
    public static function checkConnection(string $host, string $email, string $username, string $password, int $port, string $authentication, string $secure, int $timeout = 5): bool
    {
        $m = new PHPMailer\PHPMailer();
        $m->isSMTP();
        $m->Host = $host;
        $m->Port = $port;

        if ($password)
            $m->SMTPAuth = true;
        else
            $m->SMTPAuth = false;

        $m->SMTPKeepAlive = true;
        $m->SMTPSecure = $secure;
        $m->AuthType = $authentication;
        $m->Username = $username;
        $m->Password = $password;
        $m->Timeout = $timeout;
        $m->From = $email;
        $m->FromName = $email;

        if ($m->smtpConnect()) {
            $m->smtpClose();
            return true;
        } else {
            return false;
        }
    }
}
