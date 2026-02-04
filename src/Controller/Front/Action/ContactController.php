<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Entity\Module\Contact\Contact;
use App\Model\ViewModel;
use App\Repository\Module\Contact\ContactRepository;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ContactController.
 *
 * Front Contact renders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ContactController extends FrontController
{
    /**
     * Contact view.
     *
     * @throws NonUniqueResultException|MappingException|QueryException|InvalidArgumentException
     */
    #[Route('/front/contact/view/{filter}', name: 'front_contact_view', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function contact(Request $request, ContactRepository $contactRepository, ?Block $block = null, mixed $filter = null): Response
    {
        $website = $this->getWebsite();
        $contact = $filter ? $contactRepository->findOneByFilter($website->entity, $request->getLocale(), $filter) : false;

        if (!$contact) {
            return new Response();
        }

        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;
        $entity = $block instanceof Block ? $block : $contact;
        $entity->setUpdatedAt($contact->getUpdatedAt());

        return $this->render('front/'.$websiteTemplate.'/actions/contact/view.html.twig', [
            'configuration' => $configuration,
            'websiteTemplate' => $websiteTemplate,
            'website' => $website,
            'thumbConfiguration' => $this->thumbConfiguration($website, Contact::class, 'view'),
            'contact' => ViewModel::fromEntity($contact, $this->coreLocator),
        ]);
    }
}
