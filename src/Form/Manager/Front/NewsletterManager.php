<?php

declare(strict_types=1);

namespace App\Form\Manager\Front;

use App\Entity\Core\Website;
use App\Entity\Module\Newsletter\Campaign;
use App\Entity\Module\Newsletter\Email;
use App\Form\Manager\Module\CampaignManager;
use App\Model\EntityModel;
use App\Service\Content\RecaptchaService;
use App\Service\Core\MailerService;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use Random\RandomException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * NewsletterManager.
 *
 * Manage front Newsletter Action
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class NewsletterManager
{
    private const string SENDING_BLUE_API_KEY = '';

    private ?Request $request;
    private ?string $secretKey = null;
    private ?string $publicKey = null;

    /**
     * NewsletterManager constructor.
     */
    public function __construct(
        private readonly CampaignManager $campaignManager,
        private readonly CoreLocatorInterface $coreLocator,
        private readonly RecaptchaService $recaptcha,
        private readonly MailerService $mailer,
    ) {
        $this->request = $this->coreLocator->request();
    }

    /**
     * Execute request.
     *
     * @throws Exception|InvalidArgumentException
     */
    public function execute(FormInterface $form, Campaign $campaign, Email $email): bool|Email
    {
        if (!$form->isValid() || !$this->recaptcha->execute($campaign->getWebsite(), $campaign, $form, $email->getEmail())) {
            return false;
        }

        $this->setToken($email);

        if ($campaign->isInternalRegistration()) {
            $this->addEmail($campaign, $email);
        }

        $this->sendEmailConfirmation($campaign, $email);
        $this->sendWebmasterEmail($campaign, $email);

        $session = new Session();
        $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans('Merci pour votre inscription !!', [], 'front'));

        return $email;
    }

    /**
     * To manage confirmation.
     *
     * @throws Exception
     */
    public function confirmation(Email $email, ?string $status = null): ?string
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $tokenDate = $email->getTokenDate();
        $interval = $now->diff($tokenDate);
        $isExpired = ($now > $tokenDate) && ($interval->days >= 1 || $interval->h >= 24);
        $status = $isExpired ? 'expired' : $status;

        if ('expired' === $status || 'decline' === $status) {
            $this->coreLocator->em()->remove($email);
            $this->coreLocator->em()->flush();
        } elseif ('accept' === $status) {
            $email->setAccept(true);
            $email->setAcceptDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $email->setToken(null);
            $email->setTokenDate(null);
            $this->coreLocator->em()->persist($email);
            $this->coreLocator->em()->flush();
            $campaign = $email->getCampaign();
            $this->executeMailChimpRequest($campaign, $email);
            $this->executeSendingBlue($campaign, $email);
            $this->executeMailjet($campaign, $email);
        }

//        $this->campaignManager->removeExpiredToken();

        return $status;
    }

    /**
     * Add Email to Campaign.
     */
    private function addEmail(Campaign $campaign, Email $email): void
    {
        $email->setLocale($this->request->getLocale());
        $campaign->addEmail($email);
        $this->coreLocator->em()->persist($campaign);
        $this->coreLocator->em()->flush();
    }

    /**
     * Send email confirmation.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private function sendEmailConfirmation(Campaign $campaign, Email $email): void
    {
        if ($campaign->isEmailConfirmation()) {
            $campaignModel = EntityModel::fromEntity($campaign, $this->coreLocator)->response;
            $website = $campaign->getWebsite();
            $this->mailer->setSubject($this->coreLocator->translator()->trans('Confirmez votre inscription à notre newsletter', [], 'front'));
            $this->mailer->setTo([$email->getEmail()]);
            $this->mailer->setName($this->getCompanyName($website));
            $this->mailer->setFrom($campaign->getSendingEmail());
            $this->mailer->setReplyTo('disabled');
            $this->mailer->setTemplate('front/' . $website->getConfiguration()->getTemplate() . '/actions/newsletter/email/confirmation.html.twig');
            $this->mailer->setArguments([
                'stringEmail' => $email->getEmail(),
                'confirmationLink' => $this->coreLocator->router()->generate('front_newsletter_confirmation', ['token' => $email->getToken()]),
                'message' => $campaignModel->intl->body,
            ]);
            $this->mailer->send();
        }
    }

    /**
     * Send email confirmation.
     *
     * @throws Exception
     */
    private function sendWebmasterEmail(Campaign $campaign, Email $email): void
    {
        if ($campaign->isEmailToWebmaster() && $campaign->getReceivingEmails()) {
            $website = $campaign->getWebsite();
            $this->mailer->setSubject($this->coreLocator->translator()->trans('Nouvel inscrit à la newsletter', [], 'front'));
            $this->mailer->setTo($campaign->getReceivingEmails());
            $this->mailer->setName($this->getCompanyName($website));
            if ($campaign->getSendingEmail()) {
                $this->mailer->setFrom($campaign->getSendingEmail());
            }
            $this->mailer->setReplyTo($email->getEmail());
            $this->mailer->setTemplate('front/'.$website->getConfiguration()->getTemplate().'/actions/newsletter/email/webmaster.html.twig');
            $this->mailer->setArguments(['stringEmail' => $email->getEmail()]);
            $this->mailer->send();
        }
    }

    /**
     * To execute curl MailChimp request.
     */
    private function executeMailChimpRequest(Campaign $campaign, Email $email): void
    {
        if ($campaign->getExternalFormAction() && $campaign->getExternalFormToken() && $email->getEmail()) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $campaign->getExternalFormAction().'&'.$campaign->getExternalFieldEmail().'='.$email->getEmail().'&'.$campaign->getExternalFormToken().'=');
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_MAXREDIRS, 2);
            curl_setopt($curl, CURLOPT_NOBODY, true);
            $response = curl_exec($curl);
            curl_close($curl);
        }
    }

    /**
     * To execute curl Mailjet service.
     */
    private function executeSendingBlue(Campaign $campaign, Email $email): void
    {
        if (self::SENDING_BLUE_API_KEY) {
            $logger = new Logger('sending_blue_registration');
            $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/newsletter-sending-blue.log', 10, Level::Info));
            $config = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', self::SENDING_BLUE_API_KEY);
            $apiInstance = new \Brevo\Client\Api\ContactsApi(
                new \GuzzleHttp\Client(),
                $config
            );
            $createContact = new \Brevo\Client\Model\CreateContact();
            $createContact['email'] = $email->getEmail();
            try {
                $result = $apiInstance->createContact($createContact);
                $logger->info('Sending Blue registration for '.$email->getEmail().' successfully executed !');
            } catch (Exception $e) {
                $logger->critical('Sending Blue registration error for '.$email->getEmail().' - [ERROR] '.$e->getMessage());
            }
        }
    }

    /**
     * To execute curl Mailjet service.
     */
    private function executeMailjet(Campaign $campaign, Email $email): void
    {
        $this->secretKey = $campaign->getMailjetSecretKey();
        $this->publicKey = $campaign->getMailjetPublicKey();

        if ($this->secretKey && $this->publicKey) {
            $this->setMailjetList($campaign);
            $createContact = $this->executeMailjetRequest('contact', [
                'IsExcludedFromCampaigns' => 'false',
                'Name' => $email->getEmail(),
                'Email' => $email->getEmail(),
            ]);
            $contactResponse = json_decode($createContact['output'], true);
            $errorMessage = $returnedMessage['ErrorMessage'] ?? false;
            $userId = $returnedMessage['Data'][0]['ID'] ?? false;

            if ($userId && 400 !== $contactResponse['httpcode'] && !$errorMessage) {
                $addedList = $this->executeMailjetRequest('listrecipient', [
                    'IsUnsubscribed' => 'false',
                    'ContactID' => strval($userId),
                    'ContactAlt' => $email->getEmail(),
                    'ListID' => $campaign->getMailjetListId(),
                    'ListAlt' => $campaign->getMailjetListName(),
                ]);
                $responseAddedListDecoded = json_decode($addedList['output'], true);
                $errorMessage = $responseAddedListDecoded['ErrorMessage'] ?? false;
            }
        }
    }

    /**
     * To set Mailjet List configuration.
     */
    private function setMailjetList(Campaign $campaign): void
    {
        $campaignName = 'Campagne site Internet '.$campaign->getAdminName();
        $listId = $campaign->getMailjetListId();
        $listName = $campaign->getMailjetListName();

        if (!$listId && !$listName) {
            /** To check if list already existing */
            $mailjetCampaignsCurl = $this->executeMailjetRequest('contactslist');
            $mailjetCampaigns = !empty($mailjetCampaignsCurl['output']) ? json_decode($mailjetCampaignsCurl['output'], true) : [];
            if (!empty($mailjetCampaigns['Data']) && is_iterable($mailjetCampaigns['Data'])) {
                foreach ($mailjetCampaigns['Data'] as $data) {
                    if (is_array($data) && !empty($data['Name']) && $data['Name'] === $campaignName) {
                        $listId = !empty($data['ID']) ? $data['ID'] : null;
                        $listName = !empty($data['Address']) ? $data['Address'] : null;
                        break;
                    }
                }
            }

            /* To create list if not existing */
            if (!$listId && !$listName) {
                $response = $this->executeMailjetRequest('contactslist', ['Name' => $campaignName]);
                $responseDecoded = json_decode($response['output'], true);
                $listId = $responseDecoded['Data'][0]['ID'] ?? null;
                $listName = $responseDecoded['Data'][0]['Address'] ?? null;
            }

            $campaign->setMailjetListId($listId);
            $campaign->setMailjetListName($listName);
            $this->coreLocator->em()->persist($campaign);
            $this->coreLocator->em()->flush();
        }
    }

    /**
     * To execute curl Mailjet request.
     */
    private function executeMailjetRequest(string $paramUrl, array $arrayOfData = []): array
    {
        $headers = ['Content-Type:application/json'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mailjet.com/v3/REST/'.$paramUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $this->publicKey.':'.$this->secretKey);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($arrayOfData) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrayOfData));
        }

        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        $errorMsg = curl_error($ch);
        $error = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'output' => $output,
            'info' => $info,
            'errorMsg' => $errorMsg,
            'error' => $error,
            'httpcode' => $httpCode,
        ];
    }

    /**
     * Get company name.
     */
    public function getCompanyName(Website $website): ?string
    {
        $companyName = null;
        foreach ($website->getInformation()->getIntls() as $intl) {
            if ($intl->getLocale() === $this->request->getLocale()) {
                $companyName = $intl->getTitle();
                break;
            }
        }

        return $companyName;
    }

    /**
     * To set token.
     *
     * @throws RandomException
     */
    private function setToken(Email $email): void
    {
        $raw = uniqid('', true) . $email->getEmail() . bin2hex(random_bytes(128));
        $token = base64_encode($raw);
        $token = preg_replace('/[^a-zA-Z0-9]/', '', $token);
        $token = substr(str_shuffle($token), 0, 300);
        $email->setToken($token);
    }
}
