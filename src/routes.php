<?php

use Slim\Http\Request;
use Slim\Http\Response;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;



// Routes
$app->post('/routes', function (Request $request, Response $response, array $args) {

    $input = $request->getParsedBody();//get body of request

    //check if empty
    if(!$input['paths']){
      return $response->withJSON(['error' => 'Path cannot be empty']);
    }

    //check if has more than 2 arrays in request
    if(count($input['paths']) < 2){
      return $response->withJSON(['error' => 'Path needs to have more arrays']);
    }


    $uuid1 = Uuid::uuid1(); //generate token

    $sql = "INSERT INTO paths (`lat`, `long`, token) VALUES (:lat, :long, :token)";

    $stmt = $this->db->prepare($sql);

    foreach($input['paths'] as $path){
      $data_insert = array(
        'lat' => $path[0],
        'long' => $path[1],
        'token' => $uuid1->toString()
        );

      $stmt->execute($data_insert);
    }

    return $response->withJSON(['token' => $uuid1]);

});

$app->get('/routes/{token}', function (Request $request, Response $response, array $args) {

  $token = $args['token'];

  if(!$token){
    return $response->withJSON([
            "status" => "failure",
            "error" => "Please provide a token"
          ]);
  }

  $data = array();
  $hold_array = array();

  $sql = "SELECT `lat`, `long` FROM paths WHERE token=:token";
  $stmt = $this->db->prepare($sql);
  $stmt->bindParam(":token", $token);
  $stmt->execute();

  //Getting the student result array
  $stmt->setFetchMode(PDO::FETCH_ASSOC);

  if($stmt->rowCount() > 0){
    while($db_data = $stmt->fetch()){
        $data[] = $db_data;
    }
  }else{
    return $response->withJSON([
            "status" => "failure",
            "error" => "Token not Found"
          ]);
  }

  foreach($data as $a){
    $lat_long = array();
    array_push($lat_long, $a['lat'], $a['long']);
    array_push($hold_array, $lat_long);
  }

  // return json_encode($hold_array);
  $url  = 'https://maps.googleapis.com/maps/api/directions/json?';
  $url .= 'origin='.$data[0]['lat'].','.$data[0]['long']; //origin from form
	$url .= '&destination='.$data[count($data) - 1]['lat'].','.$data[count($data) - 1]['long'];
  $url .= '&waypoints=';

  //remove first and last elements
  array_shift($data);
  array_pop($data);

  foreach($data as $a){
    $url .= 'via:'.$a['lat'].','.$a['long'];
  }

  $url .= '&key=AIzaSyAs8PekeZpkKNW9R4tYE04lTNuDzXYpB5s'; //destination from


  $resp_data = get_data($url);
  $json_data = (object)json_decode($resp_data);

  $total_distance = $json_data->routes[0]->legs[0]->distance->value;
  $total_duration = $json_data->routes[0]->legs[0]->duration->value;


  if($json_data->status === 'OK'){
    return $response->withJSON([
            "status" => "success",
            "path" => $hold_array,
            "total_distance" => $total_distance,
            "total_time" => $total_duration
  ]);
  }else{
    return $response->withJSON([
            "status" => "failure",
            "error" => $json_data->error_message
  ]);
  }

});

function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}



$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");
    return phpinfo();
    // Render index view
    // return $this->renderer->render($response, 'index.phtml', $args);
});
