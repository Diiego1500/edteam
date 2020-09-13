<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class PostController extends AbstractController
{
    /**
     * @Route("/post/new/post", name="new_post")
     */
    public function new_post(Request  $request, SluggerInterface $slugger)
    {
        $em = $this->getDoctrine()->getManager();
        $post = new Post();
        $form_post= $this->createForm(PostType::class, $post);
        $form_post->handleRequest($request);
        if($form_post->isSubmitted() && $form_post->isValid()){
            $image = $form_post->get('image_name')->getData();
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $image->move(
                        $this->getParameter('post_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new \Exception('Ups, something is wrong with your file');
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
               $post->setImageName($newFilename);
            }
            $user = $this->getUser();
            $post->setUser($user);
            $em->persist($post);
            $em->flush();
            return $this->redirectToRoute('new_post');
        }
        return $this->render('post/new_post.html.twig', [
            'form_post'=> $form_post->createView()
        ]);
    }
    /**
     * @Route("/post/view/post/{id}", name="view_post")
     */
    public function view_post(Post $post, Request $request){
        $em = $this->getDoctrine()->getManager();
        $comment = new Comment();
        $comments = $em->getRepository(Comment::class)->findBy(['post'=>$post]);
        $form_comment = $this->createForm(CommentType::class, $comment);
        $form_comment->handleRequest($request);
        if($form_comment->isSubmitted() && $form_comment->isValid()){
            $comment->setUser($this->getUser());
            $comment->setPost($post);
            $em->persist($comment);
            $em->flush();
            $this->addFlash('success', 'Tu comentario ha sido agregado Exitosamente');
            return  $this->redirectToRoute('view_post', ['id'=>$post->getId()]);
        }
        return $this->render('post/view_post.html.twig',[
            'post'=>$post,
            'form_comment'=>$form_comment->createView(),
            'comments'=>$comments
        ]);

    }
}
