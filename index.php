<!DOCTYPE html>
<html lang="en">

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin="">
</script>
<style>
    #map { height:360px; }
</style>

<head>
</head>

<body id="body">

    <br></br>
    <form id="routeForm">
        	<input type="text" name="source-longitude" placeholder="source longitude" value="-73.9712">
            <input type="text" name="source-latitude" placeholder="source latitude" value="40.7831">
            <input type="text" name="dest-longitude" placeholder="dest longitude" value="-72.9279">
            <input type="text" name="dest-latitude" placeholder="dest latitude" value="41.3082">
        	<button type="submit">Find Route</button>
    </form>
    <br></br>
    <script>
        async function findRoute(formData) {
            try {
                const source_longitude = formData.get('source-longitude');
                const source_latitude = formData.get('source-latitude');
                const dest_longitude = formData.get('dest-longitude');
                const dest_latitude = formData.get('dest-latitude');

                const response = await fetch('./api/route', {
                    method: 'POST',
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        source: {lat: source_latitude, lon: source_longitude},
                        destination: {lat: dest_latitude, lon: dest_longitude}
                    })
                })
                console.log(response)
                const data = await response.json()
                console.log(data)
                displayRoute(data)
            } catch (e) {
                console.log(e)
            }
            
        }
        const routeForm = document.getElementById('routeForm');
        if(routeForm){
            document.getElementById('routeForm').addEventListener('submit', function(event) {
                event.preventDefault();

                // Get form data
                const formData = new FormData(event.target);
                findRoute(formData);
            });
        }
         // Function to display search results
         function displayRoute(data) {
            const searchResultsDiv = document.getElementById('searchResults');
            // Clear previous search results
            searchResultsDiv.innerHTML = '';

            // Create a list of search results
            const directions = document.createElement('ol');
            data.forEach(turn => {

                const listItem = document.createElement('li');
                
                listItem.textContent = turn.description; // Adjust this based on your data structure
                listItem.addEventListener('click', function() {
                    // Parse latitude and longitude coordinates from the text content of the list item
                    const [lat, lon] = coordinates.split(',').map(coord => parseFloat(coord.trim()));
    
                    
                   map.setView([lat, lon], 12); // Adjust the zoom level as needed
                });
                directions.appendChild(listItem);
            });

            // Append the list to the search results div
            searchResultsDiv.appendChild(directions);
        }
    </script>


	<div>Map of New York</div>
	<form id="searchForm">
        	<input type="text" name="searchTerm" placeholder="Search...">
            <label for="onlyInBox">Only in Box:</label>
            <input type="checkbox" id="onlyInBox" name="onlyInBox">
        	<button type="submit">Search</button>
    </form>
    <br></br>
    <form id="convertForm">
        	<input type="text" name="longitude" placeholder="longitude">
            <input type="text" name="latitude" placeholder="latitude">
            <input type="text" name="zoom" placeholder="zoom">
        	<button type="submit">Convert</button>
    </form>
    <br></br>
    <div id="map"></div>

    <div id="searchResults"></div>

    <!-- Include Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
	<script>

        // Initialize the map
        var map = L.map('map').setView([40.7128, -74.0060], 12); // New York City coordinates
        var markers = []; // Array to keep track of all markers
        

        // Add OpenStreetMap tiles as the base layer
        L.tileLayer('http://194.113.72.32:8080/tile/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        async function search(formData) {
            try {

                // Get the bounds of the currently visible map area
                const bounds = map.getBounds();

                // Extract the coordinates of the bounding box
                const southWest = bounds.getSouthWest(); // Bottom-left corner
                const northEast = bounds.getNorthEast(); // Top-right corner

                // Extract latitude and longitude values
                const minLat = southWest.lat;
                const minLon = southWest.lng;
                const maxLat = northEast.lat;
                const maxLon = northEast.lng;

                // Now you have the min and max latitude and longitude values of the visible map area
                console.log("Min Latitude:", minLat);
                console.log("Min Longitude:", minLon);
                console.log("Max Latitude:", maxLat);
                console.log("Max Longitude:", maxLon);
                
                console.log("searching...")
                bbox = {
                    "minLat": minLat,
                    "minLon": minLon,
                    "maxLat": maxLat,
                    "maxLon": maxLon
                }
                const searchTerm = formData.get('searchTerm');
                const onlyInBox = formData.get('onlyInBox');
                boxChecked = false
                if (onlyInBox) {
                    boxChecked = true
                }
                console.log(formData)
                console.log(boxChecked)
                const response = await fetch('./api/search', {
                    method: 'POST',
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        searchTerm: searchTerm,
                        bbox: bbox,
                        onlyInBox: boxChecked
                    })
                })
                console.log("response: ", response)
                const data = await response.json()
                console.log(data)
                displaySearchResults(data); // Call the function to display search results

                
            }
            catch(e){
                console.log(e)
            }
        }

        

         // Function to display search results
         function displaySearchResults(data) {
            const searchResultsDiv = document.getElementById('searchResults');
            // Clear previous search results
            searchResultsDiv.innerHTML = '';
            // Create a list of search results
            const resultList = document.createElement('ol');

            removeMarkers() // remove existing markers

            data.forEach(result => {
                const coordinates = `${result.coordinates.lat}, ${result.coordinates.long}`; // Assuming you have coordinates in your result object
                // Parse latitude and longitude coordinates from the text content of the list item
                const [lat, lon] = coordinates.split(',').map(coord => parseFloat(coord.trim()));
                // Create a new marker and add it to the map
                const marker = L.marker([lat, lon]).addTo(map);
                markers.push(marker); // Store marker reference in the array
                
                const listItem = document.createElement('li');
                listItem.addEventListener('click', function() {
                    map.setView([lat, lon], 12); // Adjust the zoom level as needed
                });
                listItem.textContent = result.name; // Adjust this based on your data structure
                resultList.appendChild(listItem);
            });

            // Append the list to the search results div
            searchResultsDiv.appendChild(resultList);
        }
        // Function to remove all markers from the map
        function removeMarkers() {
            markers.forEach(marker => {
                map.removeLayer(marker);
            });
            markers.length = 0; // Clear the markers array
        }

        async function convert(formData){

            try{
                const longitude = formData.get('longitude');
                const latitude = formData.get('latitude');
                const zoom = formData.get('zoom');
                const response = await fetch('./convert', {
                    method: 'POST',
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        lat: latitude,
                        long: longitude,
                        zoom: zoom
                    })

                })
                console.log("response: ", response)
                const data = await response.json()
                console.log(data)
                

            }
            catch (e){
                console.log(e)
            }

        }

        document.getElementById('searchForm').addEventListener('submit', function(event) {
            event.preventDefault();

            // Get form data
            const formData = new FormData(event.target);
            console.log(formData.get('searchTerm'));
            search(formData)
            });
	</script>
</body>
</html>