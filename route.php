<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_COOKIE['user_key']) == false) {
        // Handle user not logged in
  //      $response = array('status' => 'ERROR', 'message' => 'Not logged in!');
 //       header('Content-Type: application/json');
//        echo json_encode($response);
//        return;
    }

    $postData = file_get_contents("php://input");
    //    echo $postData; echo "\n";

    // Check if data was received
    if ($postData) {

        try{

            // Decode the JSON data
            $routeData = json_decode($postData, true);

            // Check if decoding was successful
            if ($routeData === null && json_last_error() !== JSON_ERROR_NONE) {
                // Handle JSON decoding error
                $response = array('status' => 'error', 'message' => 'Error decoding JSON data');
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {

                // Access individual fields
                $source_lon = $routeData['source']['lon']; 
                $source_lat = $routeData['source']['lat']; 
                $dest_lon = $routeData['destination']['lon']; 
                $dest_lat = $routeData['destination']['lat']; 

                // Initialize cURL session
                $ch = curl_init();

                // Set the URL
                $url = "http://194.113.75.165:8989/route?point=". $source_lat .",". $source_lon ."&point=". $dest_lat .",". $dest_lon ."&profile=car&points_encoded=false";
              //  echo "URL:\n";
               // echo $url;
                // Set options
                curl_setopt($ch, CURLOPT_URL, $url);
                // // Set the content type header
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // Execute the request
                $response = curl_exec($ch);
                // Check for errors
                if($response === false) {
                    $response = array('status' => 'curl error', 'message' => 'curl error');
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    return;
                }
                // Close cURL session
                curl_close($ch);

                // Decode the JSON response
                $jsonobj = json_decode($response, true);

                $ans = []; // we will return this to the client, along with status

                // Check if decoding was successful
                if ($jsonobj === null && json_last_error() !== JSON_ERROR_NONE) {
                    // Handle JSON decoding error
                    $response = array('status' => 'error', 'message' => 'Error decoding JSON data');
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    return;
                } else {
                    // Access the instructions array
                    $instructions = $jsonobj['paths'][0]['instructions'];
                    // Get the points array
                    $points = $jsonobj['paths'][0]['points']['coordinates'];

                    // Loop through each instruction and echo it
                    foreach ($instructions as $instruction) {
                        // Extract start and end indexes from the 'interval' array
                        $start_index = $instruction['interval'][0];
                        $end_index = $instruction['interval'][1];
                        
                        // Echo the instruction text along with its interval
                  //      echo $instruction['text'] . " [" . $start_index . "-" . $end_index . "]\n";
                        // Get the coordinates corresponding to the start index
                        $coordinate = $points[$start_index];
                        // Echo the coordinates
                    //    echo "Coordinates: " . $coordinate[1] . ", " . $coordinate[0] . "\n";

                        $new_json["description"] = $instruction['text'];
                        $new_json["coordinates"] = array("lat" => $coordinate[0], "lon" => $coordinate[1]);
                        $new_json["distance"] = $instruction['distance'];
                        array_push($ans, $new_json);
                    }
                }

                // Check if form is submitted with POST method
                header('Content-Type: application/json');
                http_response_code(200);
                echo json_encode($ans);
            }
            
        }
        catch (Exception $e){
            echo "Exception" . $e;
        }

} else {
    // Handle case where no data was received
    $response = array('status' => 'ERROR', 'message' => 'No data received');
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode($response);
    }

    } else {
    // Return an error if the request method is not POST
    $data = array("html" => "<h1>Not a post request</h1>");
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode($data);
}
?>