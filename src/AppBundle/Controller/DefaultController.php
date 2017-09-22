<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Task;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
//use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use SC\DatetimepickerBundle\Form\Type\DatetimeType;

class DefaultController extends Controller
{

    public function loginAction()
    {
        $user = $this->getUser();
        if($user){
            return $this->redirectToRoute('tasks');
        }
        return $this->redirectToRoute('fos_user_security_login');
        //return $this->render('default/home-login.html.twig', []);
    }

    public function showAction()
    {
        $user = $this->getUser();
        $user_id = $user->getId();

        // GET TASKS
        $repository = $this->getDoctrine()->getRepository(Task::class);
        $tasks = $repository->findByUserId($user_id);

        $query_tasks_done = $repository->createQueryBuilder('t')
            ->where("t.done = 1 AND t.userId = ".$user_id)
            ->getQuery();
        $tasks_done = $query_tasks_done->getResult();

        return $this->render('default/index.html.twig', [
            'tasks' => $tasks,
            'nb_tasks_non_done' => (count($tasks)-count($tasks_done))
        ]);
    }

    public function editAction(Request $request, $task_id = null)
    {
        if(is_null($task_id)){
            $this->addFlash(
                'error',
                'Veuillez sélectionner une tâche à modifier'
            );
            return $this->redirectToRoute('tasks');
        }
        $user = $this->getUser();
        $user_id = $user->getId();

        // GET TASKS
        $repository = $this->getDoctrine()->getRepository(Task::class);
        $tasks = $repository->findByUserId($user_id);

        // GET LA TACHE
        $em = $this->getDoctrine()->getEntityManager();
        $task_form = $em->getRepository('AppBundle:Task')->find($task_id);

        $form = $this->createFormBuilder($task_form)
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, array('attr' => array('rows'=>'4')))
            ->add('due_date', DatetimeType::class, array( 'pickerOptions' =>
                array('format' => 'dd/mm/yyyy hh:ii',
                    'weekStart' => 0,
                    'startDate' => date('m/d/Y H:i'),
                    'autoclose' => true,
                    'startView' => 'month',
                    'minView' => 'hour',
                    'maxView' => 'decade',
                    'todayBtn' => true,
                    'todayHighlight' => true,
                    'keyboardNavigation' => true,
                    'language' => 'fr',
                    'forceParse' => true,
                    'minuteStep' => 5,
                    'pickerReferer ' => 'default', //deprecated
                    'pickerPosition' => 'bottom-right',
                    'viewSelect' => 'hour',
                    'showMeridian' => false,
                )))
            ->add('save', SubmitType::class, array('label' => 'Enregistrer'))
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // perform some action, such as save the object to the database
            $em->flush();
            $this->addFlash(
                'success',
                'La tâche a bien été modifiée.'
            );
            return $this->redirect($this->generateUrl('tasks'));
        }

        return $this->render('default/edit.html.twig', [
            'tasks' => $tasks,
            'task_id' => $task_id,
            'form' => $form->createView()
        ]);
    }

    public function addAction(Request $request)
    {
        $user = $this->getUser();
        $task = new Task();
        $task->setUserId($user->getId());

        $form = $this->createFormBuilder($task)
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, array('attr' => array('rows'=>'4')))
            ->add('due_date', DatetimeType::class, array( 'pickerOptions' =>
                array('format' => 'dd/mm/yyyy hh:ii',
                    'weekStart' => 0,
                    'startDate' => date('m/d/Y H:i'),
                    'autoclose' => true,
                    'startView' => 'month',
                    'minView' => 'hour',
                    'maxView' => 'decade',
                    'todayBtn' => true,
                    'todayHighlight' => true,
                    'keyboardNavigation' => true,
                    'language' => 'fr',
                    'forceParse' => true,
                    'minuteStep' => 5,
                    'pickerReferer ' => 'default', //deprecated
                    'pickerPosition' => 'bottom-right',
                    'viewSelect' => 'hour',
                    'showMeridian' => false,
                    'initialDate' => date('d/m/Y'),
                )))
        ->add('save', SubmitType::class, array('label' => 'Enregistrer'))
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();
            $this->addFlash(
                'success',
                'La tâche a bien été ajoutée.'
            );
            return $this->redirectToRoute('tasks');
        }

        $repository = $this->getDoctrine()->getRepository(Task::class);
        $tasks = $repository->findByUserId($user->getId());

        return $this->render('default/add.html.twig', array(
            'form' => $form->createView(),
            'tasks' => $tasks
        ));
    }

    public function deleteAction(Request $request, $task_id = null)
    {
        if(is_null($task_id)){
            $this->addFlash(
                'error',
                'Veuillez sélectionner une tâche à supprimer'
            );
            return $this->redirectToRoute('tasks');
        }

        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('AppBundle:Task')->findOneById($task_id);

        $form = $this->createFormBuilder($task)
            ->add('delete', SubmitType::class, array('label' => 'Supprimer'))
            ->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em->remove($task);
            $em->flush();
            $this->addFlash(
                'success',
                'La tâche a bien été supprimée.'
            );
            //return new Response('News deleted successfully');
            return $this->redirectToRoute('tasks');
        }

        $user = $this->getUser();
        $repository = $this->getDoctrine()->getRepository(Task::class);
        $tasks = $repository->findByUserId($user->getId());

        return $this->render('default/edit.html.twig', [
            'form' => $form->createView(),
            'task_id' => $task_id,
            'tasks' => $tasks
        ]);
    }

    public function ajaxTaskDoneAction(Request $request)
    {
        if ($request->isXmlHttpRequest())
        {
            $task_id = $request->get('task_id');
            $is_done = $request->get('is_done');

            if(is_null($task_id) || is_null($is_done)){
                return false;
            }

            $em = $this->getDoctrine()->getEntityManager();
            $task = $em->getRepository('AppBundle:Task')->find($task_id);
            $task->setDone($is_done);
            $em->flush();
            return new Response('Ok');
        }
    }
}
