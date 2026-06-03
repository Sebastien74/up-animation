<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Repository\Core\WebsiteRepository;
use App\Repository\Module\Newsletter\CampaignRepository;
use App\Repository\Module\Newsletter\EmailRepository;
use App\Service\Http\RequestParam;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * UniqEmailCampaignValidator.
 *
 * Check if Newsletter email already exist
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UniqEmailCampaignValidator extends ConstraintValidator
{
    private ?Request $request;

    /**
     * UniqEmailCampaignValidator constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly WebsiteRepository    $websiteRepository,
        private readonly CampaignRepository   $campaignRepository,
        private readonly EmailRepository      $emailRepository,
    )
    {
        $this->request = $this->coreLocator->requestStack()->getCurrentRequest();
    }

    /**
     * Validate.
     *
     * @throws NonUniqueResultException
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        $websiteRequest = RequestParam::get($this->request, 'website');
        $campaignRequest = RequestParam::get($this->request, 'filter');
        if ($websiteRequest && $campaignRequest) {
            $website = $this->websiteRepository->find($websiteRequest);
            $campaign = $this->campaignRepository->findOneByFilter($website, $this->request->getLocale(), $campaignRequest);
            $existingEmail = $this->emailRepository->findOneBy([
                'email' => $value,
                'campaign' => $campaign,
            ]);
            if ($existingEmail) {
                $message = $this->coreLocator->translator()->trans('Cet email existe déjà !', [], 'validators_cms');
                $this->context->buildViolation($message)
                    ->addViolation();
            }
        }
    }
}
