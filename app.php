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
    	$response = array('statuscode' => '404', 'message' => 'Usuário não encontrado');
        return $app->json($response, 404);
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

    	$response = array('statuscode' => '200', 'message' => 'Usuário cadastrado');
        return $app->json($response, 200);
    }
   	$response = array('statuscode' => '200', 'message' => 'Usuário já existe');
	return $app->json($response, 200);
});

$app->put('/users/{id}', function (Request $request, $id) use ($app, $em) {
    $name = $request->request->get('name');
    $login = $request->request->get('login');
    $email = $request->request->get('email');
    
    $user = $em->getRepository('User\Model\User')->findOneBy(array('id' => $id));
    
    if (!$user) {
    	$response = array('statuscode' => '404', 'message' => 'Usuário não encontrado');
        return $app->json($response, 404);
    }
    
    $user_exists = $em->getRepository('User\Model\User')->findOneBy(array('login' => $login));
    if ($user_exists) {
    	$response = array('statuscode' => '200', 'message' => 'Já existe um usuário com este login');
        return $app->json($response, 200);
    }
    
    $user->setName($name);
    $user->setLogin($login);
    $user->setEmail($email);
    
    $em->persist($user);
    $em->flush();
    
   	$response = array('statuscode' => '200', 'message' => 'Usuário alterado');
	return $app->json($response, 200);
});

$app->delete('/users/{id}', function ($id) use ($app, $em) {
    $user = $em->getRepository('User\Model\User')->findOneBy(array('id' => $id));
    
    if (!$user) {
    	$response = array('statuscode' => '404', 'message' => 'Usuário não encontrado');
        return $app->json($response, 404);
    }
    
    $em->remove($user);
    $em->flush();
    
   	$response = array('statuscode' => '200', 'message' => 'Usuário apagado');
	return $app->json($response, 200);
});

$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});

