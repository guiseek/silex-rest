<?php

require_once __DIR__.'/bootstrap.php';

require_once __DIR__.'/vendor/jdesrosiers/silex-cors-provider/src/JDesrosiers/Silex/Provider/CorsServiceProvider.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

$app = new Application();
	
//actions
$app->get('/users/{id}', function ($id) use ($app, $em) {
    if ($id == null) {
        $q = $em->createQuery("select u from User\Model\User u");
        $users = $q->getArrayResult();
        return $app->json($users, 200);
    }
    
    $user = $em->getRepository('User\Model\User')->findOneBy(array('id' => $id));
    
    if (!$user) {
    	$response = array('statuscode' => '404', 'message' => 'Usuário não encontrado');
        return $app->json($response, 404);
    }
    
    $response = array('statuscode' => '200', 'user' => $user->jsonSerialize());
    return $app->json($response, 200);
})->value('id', null);

$app->post('/users', function(Request $request) use ($app, $em) {
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
        
    	$response = array('statuscode' => '200', 'message' => 'Usuário cadastrado', 'user' => $user->jsonSerialize());
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
    
    $user->setName($name);
    $user->setLogin($login);
    $user->setEmail($email);
    
    $em->persist($user);
    $em->flush();
    
   	$response = array('statuscode' => '200', 'message' => 'Usuário alterado', 'user' => $user->jsonSerialize());
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

$app->error(function (Exception $e, $code) {
	switch ($code) {
		case 400:
			$message = array('statuscode' => $code, 'message' => 'Bad request');
			break;
		case 404:
			$message = array('statuscode' => $code, 'message' => 'Not found');
			break;
		case 500:
			$message = array('statuscode' => $code, 'message' => 'Internal server error');
			break;
	}
	return $app->json($message, 200);
});

$app->after(function (Request $request, Response $response) use ($app) {
	$response->headers->set('Access-Control-Allow-Origin', '*');
	$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
	$response->headers->set('Access-Control-Allow->Headers', 'Content-Type');
});

$app->match('/users/{id}', function ($id, Request $request) use ($app) {
	return new Response('', 200, array(
		'Access-Control-Allow-Origin' => '*',
		'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE',
		'Access-Control-Allow-Headers' => 'Content-Type',
	));
})->method('OPTIONS')->value('id', null);

$app->before(function (Request $request) use ($app) {
	$request->headers->set('Access-Control-Allow-Headers', 'Content-Type');
	if ($request->getMethod() == 'OPTIONS') {
		return;
	}
});
