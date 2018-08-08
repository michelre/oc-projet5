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
use Symfony\Component\Form\Createview;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdvertController extends Controller
{
    public function indexAction(Request $request)
    {
    	$members = new Members();

    	$form = $this->get('form.factory')->createBuilder(FormType::class, $members)

    	->add('pseudo',   TextType::class)
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
    			$em = $this -> getDoctrine() -> getManager();
    			$em -> persist($members);
    			$em -> flush();

    			$request -> getSession() ->getFlashBag() -> add('notice', 'Merci pour votre inscription, nous allons vous rediriger vers votre profil');

    			return $this -> redirectToRoute('mbh_sitederencontre_profilcompleted', array ('id' => $members -> getId()));
    		}
    	}

        return $this->render('MBHSitederencontreBundle:Advert:index.html.twig',array('form'=> $form->createview()));
    }

    public function viewAction(Request $request)
    {
        return $this->render('MBHSitederencontreBundle:Advert:view.html.twig');
    }

    public function profilcompletedAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $members = $em->getRepository('MBHSitederencontreBundle:Members')->find($id);
       
    	//$members = new Members();

    	$form = $this->get('form.factory')->create(MembersType::class, $members);

    	if ($request->isMethod('POST') && $form->handleRequest($request)->isValid())
    		 {
    			$em->persist($members);
    			$em->flush();

    			return $this -> redirectToRoute('mbh_sitederencontre_monprofil', array ('id' => $members -> getId()));
    		}

        $images = $em->getRepository('MBHSitederencontreBundle:Images')->find($id);

        $images = new Images();

        $im = $this->get('form.factory')->create(ImagesType::class, $images);
        if ($request->isMethod('POST') && $im->handleRequest($request)->isValid())
             {
                $images->getImages()->upload();
                $em = $this->getDoctrine()->getManager();
                $em->persist($images);
                $em->flush();

                return $this -> redirectToRoute('mbh_sitederencontre_monprofil', array ('id' => $images -> getId()));
            }
    	
        return $this->render('MBHSitederencontreBundle:Advert:profilcompleted.html.twig', array(
            'form'=> $form->createview(), 
            'im'=> $im->createview(),
            'members'=>$members
        ));
    }

    public function monprofilAction($id, Request $request)
    {
    	$em = $this->getDoctrine()->getManager();
    	$members = $em->getRepository('MBHSitederencontreBundle:Members')->find($id);
    	if (null === $members){
    		throw new NotFoundHttpException("Le profil".$id."n'existe pas.");	
    	}
    	return $this->render('MBHSitederencontreBundle:Advert:monprofil.html.twig', array('members'=>$members,));
    }

    public function matchesAction(Request $request)
    {
        return $this->render('MBHSitederencontreBundle:Advert:matches.html.twig');
    }
}
