<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Entity\Layout\Page;
use App\Entity\Module\Form\Calendar;
use App\Entity\Module\Form\Configuration;
use App\Entity\Module\Form\ContactForm;
use App\Entity\Module\Form\ContactStepForm;
use App\Entity\Module\Form\Form;
use App\Entity\Seo\Url;
use App\Form\Manager\Front\FormCalendarManager;
use App\Form\Manager\Front\FormManager;
use App\Form\Type\Module\Form\FrontCalendarType;
use App\Form\Type\Module\Form\FrontType;
use App\Model\ViewModel;
use App\Repository\Module\Form\ContactFormRepository;
use App\Repository\Module\Form\ContactStepFormRepository;
use App\Repository\Module\Form\FormRepository;
use App\Repository\Module\Form\StepFormRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * FormController.
 *
 * Front Form renders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FormController extends FrontController
{
    /**
     * View Form.
     *
     * @throws NonUniqueResultException|\Exception|InvalidArgumentException|ExceptionInterface|ORMException
     */
    #[Route([
        'fr' => '/front/form/view-fr/{url}/{filter}/{_locale}',
        'en' => '/front/form/view-en/{url}/{filter}/{_locale}',
    ], name: 'front_form_view', options: ['isMainRequest' => false], methods: 'GET|POST', schemes: '%protocol%')]
    public function view(
        Request $request,
        FormRepository $formRepository,
        FormManager $formManager,
        Url $url,
        ?Block $block = null,
        int|string|null $filter = null): JsonResponse|RedirectResponse|bool|string|Response|null
    {
        if (!$filter) {
            return new Response();
        }

        $website = $this->getWebsite();
        $entity = $formRepository->findOneByFilter($website->entity, $request->getLocale(), $filter);
        $contact = $formManager->getContact();

        if (!$entity) {
            return new Response();
        }

        $this->coreLocator->em()->refresh($entity);
        $template = $website->configuration->template;
        $form = $this->createForm(FrontType::class, null, ['form_data' => $entity]);
        $form->handleRequest($request);
        $formConfiguration = $entity->getConfiguration();
        $pageRedirection = $formConfiguration->getPageRedirection()
            ? ViewModel::fromEntity($formConfiguration->getPageRedirection(), $this->coreLocator, ['disabledIntl' => true, 'disabledMedias' => true, 'disabledCategory' => true, 'disabledLayout' => true]) : null;

        return $this->getRender($form, $formManager, [
            'request' => $request,
            'websiteTemplate' => $template,
            'website' => $website,
            'interface' => $this->getInterface(Form::class),
            'configuration' => $website->configuration,
            'contact' => $contact,
            'url' => $url,
            'filter' => $filter,
            'block' => $block,
            'entity' => $entity,
            'formConfiguration' => $entity->getConfiguration(),
            'pageRedirectionUrl' => $pageRedirection instanceof ViewModel ? $pageRedirection->url : null,
        ]);
    }

    /**
     * Thanks.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    #[Route([
        'fr' => '/formulaire/merci/{code}',
        'en' => '/form/thanks/{code}',
    ], name: 'front_form_thanks', methods: 'GET', schemes: '%protocol%')]
    #[Cache(expires: 'tomorrow', public: true)]
    public function thanks(
        Request $request,
        FormRepository $formRepository,
        StepFormRepository $stepFormRepository,
        ContactFormRepository $contactFormRepository,
        ContactStepFormRepository $contactStepFormRepository,
        string $code): JsonResponse|Response|null
    {
        $token = $request->get('token');
        $website = $this->getWebsite();
        $form = $formRepository->findOneBy(['website' => $website->entity, 'slug' => $code]);
        if (!$form) {
            $form = $stepFormRepository->findOneBy(['website' => $website->entity, 'slug' => $code]);
        }

        //        $validForm = $form instanceof Form || $form instanceof StepForm;
        //        if (!$validForm) {
        //            return $this->redirectToRoute('front_index');
        //        }

        $session = new Session();
        $contact = $form instanceof Form ? $contactFormRepository->findOneBy(['form' => $form, 'token' => $token])
            : $contactStepFormRepository->findOneBy(['stepform' => $form, 'token' => $token]);
        $validContact = $contact instanceof ContactForm || $contact instanceof ContactStepForm || $token === $session->get('form_success');
        if (!$validContact) {
            $url = $request->headers->get('referer') ? $request->headers->get('referer') : $this->coreLocator->schemeAndHttpHost();
            return $this->redirect($url);
        }

        if ($contact) {
            $contact->setToken(null);
            $this->coreLocator->em()->persist($contact);
            $this->coreLocator->em()->flush();
        }
        $session->remove('form_success');

        $websiteTemplate = $website->configuration->template;

        return $this->render('front/'.$websiteTemplate.'/actions/form/thanks.html.twig', array_merge([
            'templateName' => 'cms',
            'entity' => ViewModel::fromEntity($form, $this->coreLocator),
            'interface' => $this->getInterface(Form::class),
            'websiteTemplate' => $websiteTemplate,
        ], $this->defaultArgs($website)));
    }

    /**
     * View Form.
     *
     * @throws NonUniqueResultException|\Exception|InvalidArgumentException|ExceptionInterface
     */
    #[Route('/front/form/steps/view/{url}/{filter}', name: 'front_formstep_view', options: ['isMainRequest' => false], methods: 'GET|POST', schemes: '%protocol%')]
    public function step(
        Request $request,
        StepFormRepository $stepFormRepository,
        FormManager $formManager,
        Url $url,
        ?Block $block = null,
        int|string|null $filter = null): JsonResponse|RedirectResponse|bool|string|Response|null
    {
        if (!$filter) {
            return new Response();
        }

        $website = $this->getWebsite();
        $entity = $stepFormRepository->findOneByFilter($website->entity, $request->getLocale(), $filter);
        $contact = $formManager->getContact();

        if (!$entity) {
            return new Response();
        }

        $websiteTemplate = $website->configuration->template;
        $form = $this->createForm(FrontType::class, null, ['form_data' => $entity]);
        $form->handleRequest($request);

        $formConfiguration = $entity->getConfiguration();
        $pageRedirection = $formConfiguration->getPageRedirection()
            ? ViewModel::fromEntity($formConfiguration->getPageRedirection(), $this->coreLocator, ['disabledIntl' => true, 'disabledMedias' => true, 'disabledCategory' => true, 'disabledLayout' => true]) : null;
        return $this->getRender($form, $formManager, [
            'request' => $request,
            'websiteTemplate' => $websiteTemplate,
            'website' => $website,
            'configuration' => $website->configuration,
            'contact' => $contact,
            'url' => $url,
            'filter' => $filter,
            'block' => $block,
            'entity' => $entity,
            'formConfiguration' => $entity->getConfiguration(),
            'pageRedirectionUrl' => $pageRedirection instanceof ViewModel ? $pageRedirection->url : null,
        ]);
    }

    /**
     * View calendar.
     *
     * @throws \Exception
     */
    #[Route('/front/form/calendar/view/{block}', name: 'front_form_calendar_view', options: ['isMainRequest' => false], methods: 'GET|POST', schemes: '%protocol%')]
    public function calendar(Request $request, FormCalendarManager $calendarManager, FormManager $formManager, ?Block $block = null): JsonResponse|Response
    {
        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;
        $contact = $formManager->getContact();
        $calendar = $calendarManager->setCalendar($website->entity, $contact);
        $form = $contact instanceof ContactForm ? $contact->getForm() : $calendar->getForm();
        $dates = $calendarManager->getDates($contact);
        $formCalendar = $this->createForm(FrontCalendarType::class, null, ['dates' => $dates]);
        $formCalendar->handleRequest($request);
        $register = $calendarManager->register($formCalendar, $contact);
        $calendars = !$contact ? $this->coreLocator->em()->getRepository(Calendar::class)->findBy(['form' => $form], ['position' => 'ASC']) : null;

        $template = 'front/'.$websiteTemplate.'/actions/form/calendar/calendar.html.twig';
        if ('success' === $register) {
            $template = 'front/'.$websiteTemplate.'/actions/form/calendar/calendar-success.html.twig';
        }

        $arguments = [
            'websiteTemplate' => $websiteTemplate,
            'website' => $website,
            'configuration' => $website->configuration,
            'block' => $block,
            'register' => $register,
            'calendar' => $calendar,
            'calendars' => $calendars,
            'token' => !empty($_GET['token']) ? $_GET['token'] : null,
            'startDate' => $request->get('startDate'),
            'dates' => $dates,
            'formCalendar' => $formCalendar->createView(),
            'form' => $form,
            'contact' => $contact,
        ];

        if ($request->get('ajax')) {
            return new JsonResponse(['html' => $this->renderView($template, $arguments), 'slotDate' => $register, 'calendar' => $calendar->getId()]);
        }

        return $this->render($template, $arguments);
    }

    /**
     * Success view.
     *
     * @throws \Exception
     */
    public function success(FormManager $formManager): Response
    {
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $template = $configuration->template;
        $contact = $formManager->getContact();

        if (!$contact) {
            return new Response();
        }

        $form = $contact instanceof ContactStepForm ? $contact->getStepform() : ($contact instanceof ContactForm ? $contact->getForm() : null);

        $fields = [];
        if (method_exists($contact, 'getContactValues')) {
            foreach ($contact->getContactValues() as $value) {
                if ($value->getConfiguration()->getSlug()) {
                    $fields[$value->getConfiguration()->getSlug()] = $value;
                }
            }
        }

        return $this->render('front/'.$template.'/actions/form/success.html.twig', [
            'websiteTemplate' => $template,
            'website' => $website,
            'configuration' => $website->configuration,
            'fields' => $fields,
            'form' => $form,
            'contact' => $contact,
        ]);
    }

    /**
     * Get render view.
     *
     * @throws \Exception|InvalidArgumentException|ExceptionInterface
     */
    private function getRender(FormInterface $form, FormManager $formManager, array $arguments): RedirectResponse|JsonResponse|bool|string|Response|null
    {
        /** @var Form $entity */
        $entity = $arguments['entity'];
        $request = $arguments['request'];
        $configuration = $arguments['formConfiguration'];
        $session = new Session();

        /** @var Url $url */
        $url = $arguments['url'];
        $template = $arguments['entity'] instanceof Form ? 'view' : 'step-form';

        if (!$configuration->isAjax() && $form->isSubmitted()) {
            if (!$form->isValid()) {
                $formManager->errors($form);
                return $this->redirectToRoute('front_index', ['url' => $url->getCode()]);
            } else {
                $contact = $formManager->success($entity, $form);
                $arguments['contact'] = $contact;
                if ($configuration->isThanksModal()) {
                    $session->set('form_success', 'form-thanks-modal-'.$arguments['entity']->getId());
                }
                return $this->getRedirection($arguments['request'], $url, $configuration, $contact);
            }
        } elseif ($configuration->isAjax() && $form->isSubmitted()) {

            $arguments['form'] = $form->createView();
            $contact = null;
            $asSuccess = 'finished' === $request->get('advancement') || !$request->get('advancement');

            if ($form->isValid() && $asSuccess && !$request->get('refresh')) {
                $contact = $formManager->success($entity, $form);
                $arguments['contact'] = $contact;
            } elseif (!$request->get('refresh')) {
                $formManager->errors($form);
            }

            $formType = $entity instanceof Form ? 'form' : 'stepform';
            $formTypeGetter = 'get'.ucfirst($formType);
            $asContact = $contact instanceof ContactForm || $contact instanceof ContactStepForm;

            if (!$configuration->$formTypeGetter()) {
                $formTypeSetter = 'set'.ucfirst($formType);
                $configuration->$formTypeSetter($entity);
            }

            return new JsonResponse([
                'success' => $asContact || $request->get('refresh'),
                'showModal' => $configuration->isThanksModal(),
                'dataId' => $asContact ? $contact->getId() : null,
                'token' => $asContact && !$configuration->isThanksPage() ? $contact->getToken() : null,
                'redirection' => $this->getRedirection($arguments['request'], $url, $configuration, $contact),
                'html' => $this->renderView('front/'.$arguments['websiteTemplate'].'/actions/form/'.$template.'.html.twig', $arguments),
            ]);
        }

        $arguments['form'] = $form->createView();

        return $this->render('front/'.$arguments['websiteTemplate'].'/actions/form/'.$template.'.html.twig', $arguments);
    }

    /**
     * Get redirection.
     */
    private function getRedirection(
        Request $request,
        Url $url,
        Configuration $configuration,
        $contact = null
    ): RedirectResponse|bool|string|null {

        if (!$contact) {
            return false;
        }

        if ($configuration->isThanksPage()) {
            $session = new Session();
            $session->set('form_success', $contact->getToken());
            $form = $configuration->getForm() ?: $configuration->getStepform();
            return $this->generateUrl('front_form_thanks', ['code' => $form->getSlug(), 'token' => $contact->getToken()]);
        }

        $asAjax = $configuration->isAjax();
        $urlCode = $asAjax ? null : $url->getCode();
        $pageRedirection = $configuration->getPageRedirection();

        if ($pageRedirection instanceof Page) {
            foreach ($pageRedirection->getUrls() as $url) {
                if ($url->getLocale() === $request->getLocale() && $url->isOnline() && $url->getCode()) {
                    $urlCode = $url->getCode();
                    break;
                }
            }
        }

        if ($asAjax) {
            return $urlCode ? $this->generateUrl('front_index', ['url' => $urlCode, 'token' => $contact->getToken()]) : null;
        }

        return $this->redirectToRoute('front_index', ['url' => $urlCode, 'token' => $contact->getToken()]);
    }
}
