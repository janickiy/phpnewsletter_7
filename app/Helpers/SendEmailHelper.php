<?php

namespace App\Helpers;

use App\Models\Attach;
use App\Models\CustomHeaders;
use App\Services\SmtpConfigurationResolver;
use Illuminate\Support\Facades\Storage;
use PHPMailer\PHPMailer;
use URL;

class SendEmailHelper
{
    public function __construct(
        private readonly SmtpConfigurationResolver $smtpConfigurationResolver,
    ) {}

    public string $subject;

    public string $body;

    public string $email;

    public int $prior = 0;

    public ?string $name = 'USERNAME';

    public int $templateId = 0;

    public int $subscriberId = 0;

    public string $token = '';

    public bool $tracking = true;

    public bool $unsub = true;

    /**
     * @return array
     *
     * @throws PHPMailer\Exception
     */
    public function sendEmail(?int $attach = null)
    {
        $subject = $this->subject;
        $body = $this->body;
        $email = $this->email;
        $prior = $this->prior;
        $name = (string) ($this->name ?? '');
        $templateId = $this->templateId;
        $subscriberId = $this->subscriberId;
        $token = $this->token;

        $m = new PHPMailer\PHPMailer;

        if (SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') === 'smtp') {
            $m->IsSMTP();
            $m->SMTPAuth = true;
            $m->SMTPKeepAlive = true;

            $smtp = $this->smtpConfigurationResolver->resolve();

            if (! $smtp) {
                return [
                    'result' => false,
                    'error' => 'No active SMTP server is configured.',
                ];
            }

            $m->Host = $smtp->host;
            $m->Port = $smtp->port;
            $m->From = $smtp->email;
            $m->Username = $smtp->username;
            $m->Password = $smtp->password;

            if ($smtp->secure === 'ssl') {
                $m->SMTPSecure = 'ssl';
            } elseif ($smtp->secure === 'tls') {
                $m->SMTPSecure = 'tls';
            }

            if ($authType = $smtp->phpMailerAuthType()) {
                $m->AuthType = $authType;
            }

            $m->Timeout = $smtp->timeout;
        } elseif (
            SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') === 'sendmail'
            && SettingsHelper::getInstance()->getValueForKey('SENDMAIL_PATH') !== ''
        ) {
            $m->IsSendmail();
            $m->Sendmail = SettingsHelper::getInstance()->getValueForKey('SENDMAIL_PATH');
        } else {
            $m->IsMail();
        }

        $m->CharSet = SettingsHelper::getInstance()->getValueForKey('CHARSET');

        if ($prior == 1) {
            $m->Priority = 1;
        } elseif ($prior == 2) {
            $m->Priority = 5;
        } else {
            $m->Priority = 3;
        }

        if (SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') !== 'smtp') {
            $m->From = SettingsHelper::getInstance()->getValueForKey('EMAIL');
        }

        $m->FromName = SettingsHelper::getInstance()->getValueForKey('FROM');

        if (SettingsHelper::getInstance()->getValueForKey('LIST_OWNER') !== '') {
            $m->addCustomHeader('List-Owner: <'.SettingsHelper::getInstance()->getValueForKey('LIST_OWNER').'>');
        }

        if (SettingsHelper::getInstance()->getValueForKey('RETURN_PATH') !== '') {
            $m->addCustomHeader('Return-Path: <'.SettingsHelper::getInstance()->getValueForKey('RETURN_PATH').'>');
        }

        if (SettingsHelper::getInstance()->getValueForKey('CONTENT_TYPE') === 'html') {
            $m->isHTML();
        } else {
            $m->isHTML(false);
        }

        $subject = str_replace('%NAME%', $name, $subject);
        $subject = (int) SettingsHelper::getInstance()->getValueForKey('RENDOM_REPLACEMENT_SUBJECT') === 1
            ? StringHelper::encodeString($subject)
            : $subject;

        if (SettingsHelper::getInstance()->getValueForKey('CHARSET') !== 'utf-8') {
            $subject = iconv('utf-8', SettingsHelper::getInstance()->getValueForKey('CHARSET'), $subject);
        }

        $m->Subject = $subject;

        if ((int) SettingsHelper::getInstance()->getValueForKey('SLEEP') > 0) {
            sleep((int) SettingsHelper::getInstance()->getValueForKey('SLEEP'));
        }

        if (SettingsHelper::getInstance()->getValueForKey('ORGANIZATION') !== '') {
            $m->addCustomHeader('Organization: '.SettingsHelper::getInstance()->getValueForKey('ORGANIZATION'));
        }

        $m->AddAddress($email);

        if (
            (int) SettingsHelper::getInstance()->getValueForKey('REQUEST_REPLY') === 1
            && SettingsHelper::getInstance()->getValueForKey('EMAIL') !== ''
        ) {
            $m->addCustomHeader('Disposition-Notification-To: '.SettingsHelper::getInstance()->getValueForKey('EMAIL'));
            $m->ConfirmReadingTo = SettingsHelper::getInstance()->getValueForKey('EMAIL');
        }

        if (SettingsHelper::getInstance()->getValueForKey('PRECEDENCE') === 'bulk') {
            $m->addCustomHeader('Precedence: bulk');
        } elseif (SettingsHelper::getInstance()->getValueForKey('PRECEDENCE') === 'junk') {
            $m->addCustomHeader('Precedence: junk');
        } elseif (SettingsHelper::getInstance()->getValueForKey('PRECEDENCE') === 'list') {
            $m->addCustomHeader('Precedence: list');
        }

        $UNSUB = URL::route('frontend.unsubscribe', ['subscriber' => $subscriberId, 'token' => $token]);
        $unsublink = str_replace('%UNSUB%', $UNSUB, SettingsHelper::getInstance()->getValueForKey('UNSUBLINK'));

        if ($this->unsub) {
            if (
                (int) SettingsHelper::getInstance()->getValueForKey('SHOW_UNSUBSCRIBE_LINK') === 1
                && SettingsHelper::getInstance()->getValueForKey('UNSUBLINK') !== ''
            ) {
                $body .= '<br><br>'.$unsublink;
            }

            $m->addCustomHeader('List-Unsubscribe: '.$UNSUB);
        }

        foreach (CustomHeaders::get() ?? [] as $customheader) {
            $m->addCustomHeader($customheader->name.': '.$customheader->value);
        }

        $msg = $body;
        $url_info = parse_url(SettingsHelper::getInstance()->getValueForKey('URL'));

        $msg = preg_replace_callback("/%REFERRAL\:(.+)%/isU", function ($matches) {
            return '%URL_PATH%/referral/'.base64_encode($matches[1]).'/%USERID%';
        }, $msg);

        $msg = str_replace('%NAME%', $name, $msg);
        $msg = str_replace('%UNSUB%', $UNSUB, $msg);
        $msg = str_replace('%SERVER_NAME%', $url_info['host'], $msg);
        $msg = str_replace('%USERID%', $subscriberId, $msg);
        $msg = str_replace('%URL_PATH%', URL::to('/'), $msg);
        $msg = (int) SettingsHelper::getInstance()->getValueForKey('RANDOM_REPLACEMENT_BODY') === 1
            ? StringHelper::encodeString($msg)
            : $msg;
        $msg = StringHelper::macrosReplacement($msg);

        if ($attach) {
            foreach (Attach::where('template_id', $attach)->get() ?? [] as $f) {
                $path = Attach::DIRECTORY.'/'.$f->file_name;

                if (Storage::exists($path)) {
                    $storagePath = Storage::disk('local')->path($path);

                    if (SettingsHelper::getInstance()->getValueForKey('CHARSET') !== 'utf-8') {
                        $f->name = iconv('utf-8', SettingsHelper::getInstance()->getValueForKey('CHARSET'), $f->name);
                    }

                    $ext = pathinfo($f->file_name, PATHINFO_EXTENSION);
                    $mime_type = StringHelper::getMimeType($ext);
                    $m->AddAttachment($storagePath, $f->name, 'base64', $mime_type);
                }
            }
        }

        if (SettingsHelper::getInstance()->getValueForKey('CHARSET') !== 'utf-8') {
            $msg = iconv('utf-8', SettingsHelper::getInstance()->getValueForKey('CHARSET'), $msg);
        }

        if (SettingsHelper::getInstance()->getValueForKey('CONTENT_TYPE') === 'html') {
            if ($this->tracking) {
                $imageUrl = URL::route('frontend.pic', ['subscriber' => $subscriberId, 'template' => $templateId]);
                $IMG = '<img alt="" border="0" src="'.$imageUrl.'" width="1" height="1">';
                $msg .= $IMG;
            }
        } else {
            $msg = preg_replace('/<br(\s\/)?>/i', "\n", $msg);
            $msg = StringHelper::removeHtmlTags($msg);
        }

        $m->Body = $msg;

        if (! $m->Send()) {
            $result = ['result' => false, 'error' => $m->ErrorInfo];
        } else {
            $result = ['result' => true, 'error' => null];
        }

        $m->ClearCustomHeaders();
        $m->ClearAllRecipients();
        $m->ClearAttachments();

        if (SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') === 'smtp') {
            $m->SmtpClose();
        }

        return $result;
    }

    /**
     * @throws PHPMailer\Exception
     */
    public static function checkConnection(
        string $host,
        string $email,
        string $username,
        ?string $password,
        int $port,
        string $authentication,
        string $secure,
        int $timeout = 5
    ): bool {
        $m = new PHPMailer\PHPMailer;
        $m->isSMTP();
        $m->Host = $host;
        $m->Port = $port;

        if ($password) {
            $m->SMTPAuth = true;
        } else {
            $m->SMTPAuth = false;
        }

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
