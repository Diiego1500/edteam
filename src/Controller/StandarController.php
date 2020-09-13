<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\UserType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class StandarController extends AbstractController
{

    /**
     * @Route("/", name="index")
     */
    public function index(PaginatorInterface  $paginator, Request  $request){
        $em = $this->getDoctrine()->getManager();
        $query = $em->getRepository(Post::class)->getAllPost();
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            6 /*limit per page*/
        );
        return $this->render('standar/index.html.twig',[
            'pagination'=>$pagination
        ]);
    }


    /**
     * @Route("/register", name="register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $em = $this->getDoctrine()->getManager();
        $user = new User();
        $register_form = $this->createForm(UserType::class, $user);
        $register_form->handleRequest($request);
        if($register_form->isSubmitted() && $register_form->isValid() ){
            $password_raw = $register_form->get('password')->getData();
            $user->setPassword($passwordEncoder->encodePassword($user, $password_raw));
            $user->setRoles(['ROLE_USER']);
            $em->persist($user);
            $em->flush();
            return $this->redirectToRoute('app_login');
        }
        return $this->render('standar/register.html.twig', [
            'register_form' => $register_form->createView()
        ]);
    }
}
