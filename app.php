<?php

require_once __DIR__.'/bootstrap.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

$app = new Application();

//configuration
$app->register(new Silex\Provider\SessionServiceProvider());


//actions
$app->get('/users/{id}', function ($id) use ($app, $em) {
    if ($id == null) {
        $q = $em->createQuery("select u from User\Model\User u");
        $users = $q->getArrayResult();
        return $app->json($users, 200);
    }

    $q = $em->createQuery("select u from User\Model\User u WHERE u.id = :id");
    $q->setParameters(
        array('id' => $id)
    );
    $user = $q->getArrayResult();
    if (!$user) {
        return new Response('Usuário não encontrado', 404); 
    }
    
    return $app->json($user, 200);
})->value('id', null);

$app->post('/users', function(Request $request) use ($app, $em) {
    //return new Response($request->request->get('name'), 200);
    $name = $request->request->get('name');
    $login = $request->request->get('login');
    $email = $request->request->get('email');
    
    $user = $em->getRepository('User\Model\User')->findOneBy(array('login' => $login));
    
    if (!$user) {
        $user = new User\Model\User();
        $user->setName($name);
        $user->setLogin($login);
        $user->setEmail($email);

        $em->persist($user);
        $em->flush();

        return new Response('Usuário cadastrado', 200);
    }
    return new Response('Usuário já existe', 200);
});

$app->put('/users/{id}', function (Request $request, $id) use ($app, $em) {
    $name = $request->request->get('name');
    $login = $request->request->get('login');
    $email = $request->request->get('email');
    
    $user = $em->getRepository('User\Model\User')->findOneBy(array('id' => $id));
    
    if (!$user) {
    	return new Response('Usuário não encontrado', 200);
    }
    
    $user_exists = $em->getRepository('User\Model\User')->findOneBy(array('login' => $login));
    if ($user_exists) {
    	return new Response('Já existe um usuário com este login', 200);
    }
    
    $user->setName($name);
    $user->setLogin($login);
    $user->setEmail($email);
    
    $em->persist($user);
    $em->flush();
    
    return new Response('Usuário alterado', 200);
});

$app->delete('/users/{id}', function ($id) use ($app, $em) {
    $user = $em->getRepository('User\Model\User')->findOneBy(array('id' => $id));
    
    if (!$user) {
    	return new Response('Usuário não encontrado', 200);
    }
    
    $em->remove($user);
    $em->flush();
    
    return new Response('Usuário apagado', 200);
});

$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});

