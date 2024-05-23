<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = file_get_contents("php://input");

    // Establish database connection
    $dbconn = pg_connect("host=194.113.72.32 port=5432 dbname=gis user=renderer password=renderer");
   // $pdo = new PDO("host=146.190.141.61 port=5432 dbname=gis user=renderer password=renderer");

    
    if (!$dbconn) {
        error_log("Cant connect\n");
    }
    if ($postData) {

        // Decode the JSON data
        $userData = json_decode($postData, true);

        // Check if decoding was successful
        if ($userData === null && json_last_error() !== JSON_ERROR_NONE) {
            // Handle JSON decoding error
            $response = array('status' => 'error', 'message' => 'Error decoding JSON data');
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            // Access individual fields
            $searchTerm = "%" . $userData['searchTerm'] . "%";
            $bbox = $userData['bbox'];
            $minLat = $bbox['minLat'];
            $maxLat = $bbox['maxLat'];
            $minLon = $bbox['minLon'];
            $maxLon = $bbox['maxLon'];
            $onlyInBox = $userData['onlyInBox'];

            // error_log("searchTerm: " . $searchTerm . "\n");
            // error_log("minLat: " . $minLat . "\n");
            // error_log("maxLat: " . $maxLat . "\n");
            // error_log("minLon: " . $minLon . "\n");
            // error_log("maxLon: " . $maxLon . "\n");
            // error_log("onlyInBox: " . $onlyInBox . "\n");

            $result = NULL;
            $sql_query =   "
            SELECT DISTINCT ON (name) name, 
            ST_AsText(ST_Transform(way, 4326)) AS way,
            ST_X(ST_Transform(way, 4326)) AS longitude,
            ST_Y(ST_Transform(way, 4326)) AS latitude
            FROM planet_osm_point
            WHERE name ILIKE $1
            ORDER BY name limit 50;
                    ";
            if ($onlyInBox) {
                $sql_query = "
                    SELECT DISTINCT ON (name) name, 
                    ST_AsText(ST_Transform(way, 4326)) AS way,
                    ST_X(ST_Transform(way, 4326)) AS longitude,
                    ST_Y(ST_Transform(way, 4326)) AS latitude
                    FROM planet_osm_point
                    WHERE name ILIKE $1
                    AND ST_Intersects(
                        ST_Transform(way, 4326), 
                        ST_MakeEnvelope($2, $3, $4, $5, 4326)
                    )
                    ORDER BY name
                    LIMIT 50;
                ";
			    error_log("Trying to find only In Box");		
    		    $result = pg_query_params($dbconn, $sql_query, array($searchTerm, $minLon, $minLat, $maxLon, $maxLat));
                if (!$result) {
                    echo "An error occurred.\n";
                    exit;
                }
                $ans = [];

                while ($row = pg_fetch_row($result)) {
                    $new_json = array();
                    $new_json["name"] = $row[0];
                    $new_json["coordinates"] = array("lat" => $row[3], "long" => $row[2]);
                    array_push($ans, $new_json);
                }
                header('Content-Type: application/json');
                http_response_code(200);
                echo json_encode($ans);
                die();
	    } else {
		    error_log("Trying to find NOT only in box");
                $result = pg_query_params($dbconn, $sql_query, array($searchTerm));
                if (!$result) {
                    echo "An error occurred.\n";
                    exit;
                }
                $ans = [];
                while ($row = pg_fetch_row($result)) {
                    $new_json = array();
                    // echo $row[0];
                    // echo "\n";
                    // echo $row[1];
                    // echo "\n";
                    // echo $row[2];
                    // echo "\n";
                    $new_json["name"] = $row[0];
                    $new_json["coordinates"] = array("lat" => $row[3], "long" => $row[2]);
                    //$new_json["bbox"] =  array("minLat" => $row[4], "minLon" => $row[3], "maxLat" => $row[6], "maxLon" => $row[5]);
                    array_push($ans, $new_json);
                }
                header('Content-Type: application/json');
                http_response_code(200);
                echo json_encode($ans);
                die();
            }
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
    $data = array("html" => "Not a post request");
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode($data);
}