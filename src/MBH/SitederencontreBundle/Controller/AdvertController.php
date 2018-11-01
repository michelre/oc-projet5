<?php

namespace MBH\SitederencontreBundle\Controller;

use MBH\SitederencontreBundle\Entity\Images;
use MBH\SitederencontreBundle\Entity\Members;
use MBH\SitederencontreBundle\Entity\Thread;
use MBH\SitederencontreBundle\Form\ImagesType;
use MBH\SitederencontreBundle\Form\MembersType;
use MBH\SitederencontreBundle\Service\FileUploader;
use MBH\SitederencontreBundle\Service\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Form\Createview;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use MBH\SitederencontreBundle\Service\NotificationService;

class AdvertController extends Controller
{

    public function indexAction(Request $request)
    {
        if($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            $user = $this->getUser();
            return $this->redirectToRoute('mbh_sitederencontre_monprofil', array('id' => $user->getId()));
        }
        return $this->render('MBHSitederencontreBundle:Advert:index.html.twig');
    }

    public function subscribeAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        $members = $em->getRepository(Members::class)->find(2);
        $userManager = $this->get('fos_user.user_manager');
        /**
         * @var Members $user
         */
        $user = $userManager->createUser();
        $user
            ->setPseudo($request->request->get('pseudo'))
            ->setUsername($request->request->get('pseudo'))
            ->setBirthday(date_create_from_format('Y-m-d', $request->request->get('birthday')))
            ->setCity($request->request->get('city'))
            ->setGender($request->request->get('gender'))
            ->setEmail($request->request->get('email'))
            ->setPlainPassword($request->request->get('password'));
        $userManager->updateUser($user);
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);
        $this->get('session')->set('_security_main', serialize($token));
        $event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
        return $this->redirectToRoute('mbh_sitederencontre_profilcompleted', array('id' => $user->getId()));

    }

    public function loginFormAction()
    {
        return $this->render('MBHSitederencontreBundle:Advert:login.html.twig');
    }

    public function loginAction(Request $request)
    {
        $factory = $this->get('security.encoder_factory');
        $user_manager = $this->get('fos_user.user_manager');
        /**
         * @var Members $user
         */
        $user = $user_manager->findUserByUsername($request->request->get('pseudo'));

        // Check if the user exists !
        if(!$user){
            return $this->redirectToRoute('mbh_sitederencontre_login_form', array('id' => $user->getId()));
        }
        $encoder = $factory->getEncoder($user);
        $salt = $user->getSalt();

        if(!$encoder->isPasswordValid($user->getPassword(), $request->request->get('password'), $salt)) {
            return $this->redirectToRoute('mbh_sitederencontre_login_form', array('id' => $user->getId()));
        }
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);
        $this->get('session')->set('_security_main', serialize($token));
        $event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

        return $this->redirectToRoute('mbh_sitederencontre_monprofil', array('id' => $user->getId()));
    }

    public function logoutAction()
    {
        $this->get('security.context')->setToken(null);
        $this->get('request')->getSession()->invalidate();
    }

    public function viewAction(Request $request)
    {
        return $this->render('MBHSitederencontreBundle:Advert:view.html.twig');
    }

    public function profilcompletedAction($id, Request $request)
    {
        if(!$this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            return $this->redirectToRoute('mbh_sitederencontre_login_form');
        }
        $em = $this->getDoctrine()->getManager();
        $members = $em->getRepository('MBHSitederencontreBundle:Members')->find($id);

        $form = $this->get('form.factory')->create(MembersType::class, $members);

        $images = $em->getRepository('MBHSitederencontreBundle:Images')->find($id);

        $images = new Images();

        $im = $this->get('form.factory')->create(ImagesType::class, $images);
        if ($request->isMethod('POST') && $im->handleRequest($request)->isValid()) {
            $images->getImages()->upload();
            $em = $this->getDoctrine()->getManager();
            $em->persist($images);
            $em->flush();

            return $this->redirectToRoute('mbh_sitederencontre_profilcompleted', array('id' => $images->getId()));
        }

        return $this->render('MBHSitederencontreBundle:Advert:profilcompleted.html.twig', array(
            'form' => $form->createview(),
            'im' => $im->createview(),
            'members' => $members
        ));
    }

    public function monprofilAction($id, Request $request)
    {
        if(!$this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            return $this->redirectToRoute('mbh_sitederencontre_login_form');
        }

        $em = $this->getDoctrine()->getManager();
        $members = $em->getRepository('MBHSitederencontreBundle:Members')->find($id);
        $user = $this->getUser();

        if (null === $members) {
            throw new NotFoundHttpException("Le profil" . $id . "n'existe pas.");
        }

        $notificationService = $this->get(NotificationService::class);
        $notifications = $notificationService->hasNotifications($user);
        return $this->render('MBHSitederencontreBundle:Advert:monprofil.html.twig',
            array('members' => $members, 'user'=> $user,
                'hasNotification' => count($notifications) > 0,
                'notifications' => $notifications));
    }

    public function matchesAction(Request $request)
    {
        return $this->render('MBHSitederencontreBundle:Advert:matches.html.twig');
    }

   public function profileImageAction(Request $request, $id)
    {
        /** @var FileUploader $fileUploader */
        $em = $this->getDoctrine()->getManager();
        $fileUploader = $this->get(FileUploader::class);
        $file = $fileUploader->upload($request->files->get('profile'));
        /** @var Members $member */
        $member = $em->getRepository(Members::class)->findOneBy(array('id' => $id));
        $fileUploader->removeImage($member->getProfileImage());
        $member->setProfileImage($file);
        $em->flush();
        return $this->redirectToRoute('mbh_sitederencontre_profilcompleted', ['id' => $id], 301);
    }

    public function profileImagesAction(Request $request, $id)
    {
        /** @var FileUploader $fileUploader */
        $em = $this->getDoctrine()->getManager();
        $fileUploader = $this->get(FileUploader::class);
        /** @var Members $member */
        $member = $em->getRepository(Members::class)->findOneBy(array('id' => $id));
        $files = $request->files->all();
        $methods = ['image-1' => ['getImage1', 'setImage1'],
            'image-2' => ['getImage2', 'setImage2'],
            'image-3' => ['getImage3', 'setImage3']
        ];
        foreach ($files as $key => $file) {
            if ($file) {
                $fileName = $fileUploader->upload($file);
                $fileUploader->removeImage($member->{$methods[$key][0]}());
                $member->{$methods[$key][1]}($fileName);
            } else {
                $member->{$methods[$key][1]}(null);
            }
            $em->flush();
        }
        return $this->redirectToRoute('mbh_sitederencontre_profilcompleted', ['id' => $id], 301);
    }

    public function sendMessageAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $composer = $this->get('fos_message.composer');
        $sender = $this->get('fos_message.sender');
        $serializer = $this->get('jms_serializer');
        $userFrom = $this->getDoctrine()->getRepository(Members::class)->findOneBy(['id' => $data['from']]);
        $userTo = $this->getDoctrine()->getRepository(Members::class)->findOneBy(['id' => $data['to']]);
        $messageService = $this->get(MessageService::class);
        $threadId = $messageService->getThreadIdBySenderAndRecepient($data['from'], $data['to']);
        $messages = [];
        if (!is_null($threadId)) {
            $thread = $this->getDoctrine()->getRepository(Thread::class)->find($threadId);
            $message = $composer->reply($thread)->setSender($userFrom)->setBody($data['body'])->getMessage();
            $sender->send($message);

            foreach ($thread->getMessages() as $message) {
                $sender = $serializer->serialize($message->getSender(), 'json');
                $resData = array('body' => $message->getBody(), 'sender' => $sender);
                array_push($messages, $resData);
            }

            return new JsonResponse($messages);
        }

        $message = $composer->newThread()->setSender($userFrom)->addRecipient($userTo)->setSubject('')->setBody($data['body'])->getMessage();
        $sender->send($message);
        return new JsonResponse(['body' => $data['body'], 'sender' => $sender = $serializer->serialize($message->getSender(), 'json')]);
    }

    public function getMessagesAction($participant1, $participant2)
    {
        $messageService = $this->get(MessageService::class);
        $serializer = $this->get('jms_serializer');
        $threadId = $messageService->getThreadIdBySenderAndRecepient($participant1, $participant2);
        $messages = [];
        if (!is_null($threadId)) {
            $thread = $this->getDoctrine()->getRepository(Thread::class)->find($threadId);
            foreach ($thread->getMessages() as $message) {
                $sender = $serializer->serialize($message->getSender(), 'json');
                $resData = array('body' => $message->getBody(), 'sender' => $sender);
                array_push($messages, $resData);
            }
            return new JsonResponse($messages);
        }
        return new JsonResponse([]);
    }

    public function findUsersAction(Request $request)
    {
        $data = [];
        $users = $this->getDoctrine()->getRepository(Members::class)->createQueryBuilder('m')
            ->where('m.pseudo LIKE :query')
            ->setParameter('query', '%' . $request->query->get('q') . '%')
            ->getQuery()
            ->getResult();
        foreach ($users as $user) {
            array_push($data, ['id' => $user->getId(), 'pseudo' => $user->getPseudo()]);
        }
        return new JsonResponse($data);
    }

    public function listProfilesAction(){
        if(!$this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            return $this->redirectToRoute('mbh_sitederencontre_login_form');
        }
        $em = $this->getDoctrine()->getManager();
        $members = $em->getRepository(Members::class)->findAll();
        $members = array_filter($members, function($member) {
           return  $member->getId() !== $this->getUser()->getId();
        });
        return $this->render('MBHSitederencontreBundle:Advert:profiles.html.twig',
            array('members' => $members, 'currentUser' => $this->getUser()));

    }

    public function updateProfilAction($id, Request $request){
        /** @var Members $user */
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(Members::class)->find($id);
        $user
            ->setPseudo($request->request->get('pseudo'))
            ->setUsername($request->request->get('pseudo'))
            ->setBirthday(date_create_from_format('Y-m-d', $request->request->get('birthday')))
            ->setCity($request->request->get('city'))
            ->setGender($request->request->get('gender'))
            ->setEmail($request->request->get('email'))
            ->setDescription($request->request->get('description'))
            ->setJob($request->request->get('job'));
        $em->persist($user);
        $em->flush();
        return $this->redirectToRoute('mbh_sitederencontre_monprofil', array('id' => $user->getId()));
    }
}
