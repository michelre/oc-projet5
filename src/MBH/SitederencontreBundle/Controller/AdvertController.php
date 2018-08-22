<?php

namespace MBH\SitederencontreBundle\Controller;

use MBH\SitederencontreBundle\Entity\Members;
use MBH\SitederencontreBundle\Entity\Images;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use MBH\SitederencontreBundle\Form\MembersType;
use MBH\SitederencontreBundle\Form\ImagesType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use MBH\SitederencontreBundle\Service\FileUploader;
use Symfony\Component\Form\Createview;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdvertController extends Controller
{
    public function indexAction(Request $request)
    {
    	$members = new Members();

    	$form = $this->get('form.factory')->createBuilder(FormType::class, $members)

    	->add('username',   TextType::class)
    	->add('birthday', BirthdayType::class)
    	->add('city',     TextType::class)
    	->add('email',    EmailType::class)
    	->add('password', PasswordType::class)
    	->add('gender',   TextType::class)
    	->add('save',     SubmitType::class)
    	->getForm();

    	if ($request->isMethod('POST')) {
    		$form -> handleRequest($request);

    		if($form -> isValid()) {
                $members->setSalt('');
    			$em = $this -> getDoctrine() -> getManager();
    			$em -> persist($members);
    			$em -> flush();

    			$request -> getSession() ->getFlashBag() -> add('notice', 'Merci pour votre inscription, nous allons vous rediriger vers votre profil');

    			return $this -> redirectToRoute('mbh_sitederencontre_profilcompleted', array ('id' => $members -> getId()));
    		}
    	}

        return $this->render('MBHSitederencontreBundle:Advert:index.html.twig',array('form'=> $form->createview()));
    }


    public function profilcompletedAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $members = $em->getRepository('MBHSitederencontreBundle:Members')->find($id);

        //$members = new Members();

        $form = $this->get('form.factory')->create(MembersType::class, $members);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $em->persist($members);
            $em->flush();

            return $this->redirectToRoute('mbh_sitederencontre_monprofil', array('id' => $members->getId()));
        }

        $images = $em->getRepository('MBHSitederencontreBundle:Images')->find($id);

        $images = new Images();

        $im = $this->get('form.factory')->create(ImagesType::class, $images);
        if ($request->isMethod('POST') && $im->handleRequest($request)->isValid()) {
            $images->getImages()->upload();
            $em = $this->getDoctrine()->getManager();
            $em->persist($images);
            $em->flush();

            return $this->redirectToRoute('mbh_sitederencontre_monprofil', array('id' => $images->getId()));
        }

        return $this->render('MBHSitederencontreBundle:Advert:profilcompleted.html.twig', array(
            'form' => $form->createview(),
            'im' => $im->createview(),
            'members' => $members
        ));
    }

    public function monprofilAction($id, Request $request)
    {
    	$em = $this->getDoctrine()->getManager();
    	$members = $em->getRepository('MBHSitederencontreBundle:Members')->find($id);
    	if (null === $members){
    		throw new NotFoundHttpException("Le profil".$id."n'existe pas.");	
    	}
    	return $this->render('MBHSitederencontreBundle:Advert:monprofil.html.twig', array(
            'members'=>$members));
    }

    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $members = $em->getRepository('MBHSitederencontreBundle:Members')->find($id);
        if (null === $members){
            throw new NotFoundHttpException("Le profil".$id."n'existe pas.");   
        }

        $form = $this->get('form.factory')->create(MembersType::class, $members);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid())
             {
                $em->persist($members);
                $em->flush();

                return $this -> redirectToRoute('mbh_sitederencontre_monprofil', array ('id' => $members -> getId()));
            }

        $images = new Images();

        $images = $em->getRepository('MBHSitederencontreBundle:Images')->find($id);

        $im = $this->get('form.factory')->create(ImagesType::class, $images);
        if ($request->isMethod('POST') && $im->handleRequest($request)->isValid())
             {
                $images->getImages()->upload();
                $em = $this->getDoctrine()->getManager();
                $em->persist($images);
                $em->flush();

                return $this -> redirectToRoute('mbh_sitederencontre_monprofil', array ('id' => $images -> getId()));
            }
        return $this->render('MBHSitederencontreBundle:Advert:edit.html.twig', array(
            'members'=>$members,
            'im'=>$im->createview(),
            'form'=>$form->createview(),
            'images'=>$images
        ));
    }

    public function viewAction($page)
    {
        if ($page > 1){
            throw new NotFoundHttpException("page'".$page."' inexistant.");    
        }
        $members = new Members();

        $em = $this->getDoctrine()->getManager();
        $members = $em->getRepository('MBHSitederencontreBundle:Members')->findAll();
        
        
        return $this->render('MBHSitederencontreBundle:Advert:view.html.twig', array('members'=>$members));
    }

    public function matchesAction(Request $request)
    {
        return $this->render('MBHSitederencontreBundle:Advert:matches.html.twig');
    }

  public function loginAction(Request $request)
  {
    // Si le visiteur est déjà identifié, on le redirige vers l'accueil
    if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
      return $this->redirectToRoute('mbh_sitederencontre_monprofil');
    }

    // Le service authentication_utils permet de récupérer le nom d'utilisateur
    // et l'erreur dans le cas où le formulaire a déjà été soumis mais était invalide
    // (mauvais mot de passe par exemple)
    $authenticationUtils = $this->get('security.authentication_utils');

    return $this->render('MBHSitederencontreBundle:Advert:login.html.twig', array(
      'last_pseudo' => $authenticationUtils->getLastUsername(),
      'error'         => $authenticationUtils->getLastAuthenticationError(),
    ));
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
}
