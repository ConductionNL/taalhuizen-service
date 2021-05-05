<?php


namespace App\Service;


use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BcService
{
    private CommonGroundService $commonGroundService;
    private ParameterBagInterface $parameterBag;

    public function __construct(CommonGroundService $commonGroundService, ParameterBagInterface $parameterBag)
    {
        $this->commonGroundService = $commonGroundService;
        $this->parameterBag = $parameterBag;
    }

    public function sendPasswordResetMail(string $email, string $token)
    {
        $link = "{$this->parameterBag->get('app_domain')}/auth/resetpassword/$token";
        $message = [
            'content'   => "Beste $email,\n\nU heeft een wachtwoord-reset link aangevraagd voor de Taalhuizen applicatie.\n\nKlik op de volgende link om het wachtwoord te resetten:\n $link\n\nMet vriendelijke groet,\n\nBiSC Taalhuizen",
            'subject'   => 'Wachtwoord reset op Taalhuizen',
            'sender'    => 'info@taalhuizen-bisc.commonground.nu',
            'reciever'  => $email,
            'status'    => 'queued',
            'service'      => '/services/30a1ccce-6ed5-4647-af04-d319b292e232',

        ];

        $this->commonGroundService->createResource($message, ['component' => 'bs', 'type' => 'messages']);
    }

    public function sendPasswordChangedEmail(string $username, array $contact)
    {
        $link = "{$this->parameterBag->get('app_domain')}/auth/forgotpassword";
        $message = [
            'content' => "Beste {$contact['givenName']},\n\nUw wachtwoord is succesvol gewijzigd.\n\nAls u dit niet zelf gedaan hebt, vraag dan een nieuw wachtwoord aan op de volgende link: $link",
            'subject'   => 'Uw wachtwoord op Taalhuizen werd veranderd',
            'sender'    => 'info@taalhuizen-bisc.commonground.nu',
            'reciever'  => $username,
            'status'    => 'queued',
            'service'      => '/services/30a1ccce-6ed5-4647-af04-d319b292e232',

        ];

        $this->commonGroundService->createResource($message, ['component' => 'bs', 'type' => 'messages']);
    }

    public function sendInvitation(string $email, string $token, array $contact)
    {
        $link = "{$this->parameterBag->get('app_domain')}/auth/resetpassword/$token";
        $message = [
            'content'   => "Beste {$contact['givenName']},\n\nU bent uitgenodigd als vrijwilliger voor de taalhuizen applicatie.\n\nKlik op de volgende link om het wachtwoord in te stellen:\n $link\n\nMet vriendelijke groet,\n\nBiSC Taalhuizen",
            'subject'   => 'Wachtwoord reset op Taalhuizen',
            'sender'    => 'info@taalhuizen-bisc.commonground.nu',
            'reciever'  => $email,
            'status'    => 'queued',
            'service'      => '/services/30a1ccce-6ed5-4647-af04-d319b292e232',

        ];

        $this->commonGroundService->createResource($message, ['component' => 'bs', 'type' => 'messages']);
    }
}
