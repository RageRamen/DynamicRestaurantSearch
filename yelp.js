let map;
let markers = [];
let curr_info_win;

async function initialize () {
   const pos = { lat: 32.75, lng: -97.13 };

   const { Map } = await google.maps.importLibrary("maps");
   const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");

   map = new Map(document.getElementById("map"), {
      zoom: 16,
      center: pos,
      mapId: "MAP",
   });

}

function clearMarkers () {
  for (let i = 0; i < markers.length; i++) {
    markers[i].setMap(null);
  }
  markers = [];
}

async function findRestaurants () {
   const { Map } = await google.maps.importLibrary("maps");
   const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");

   clearMarkers();

   var xhr = new XMLHttpRequest();
   //var city = document.getElementById("city").value;
   //var limit = document.getElementById("level").value;
   var search = document.getElementById("search").value;

   var bounds = map.getBounds();
   var ne = bounds.getNorthEast();
   var sw = bounds.getSouthWest();

   var lati = (ne.lat() + sw.lat()) / 2;
   var long = (ne.lng() + sw.lng()) / 2;
   var radius = Math.round((ne.lat() - sw.lat()) * 50000);
   //console.log("lat = " + lat);
   //console.log("lng = " + lng);
   //console.log("radius = " + radius);
   //console.log("bounds = " + bounds);
   //console.log("ne = " + ne);
   //console.log("sw = " + sw);


   //console.log("limit = " + limit);
   xhr.open("GET", "proxy.php?term=" + search + 
      "&latitude=" + lati + 
      "&longitude=" + long + 
      "&radius=" + radius + 
      "&limit=10");
   xhr.setRequestHeader("Accept","application/json");
   xhr.onreadystatechange = function () {
       if (this.readyState == 4) {
         var json = JSON.parse(this.responseText);

         json = json.businesses;

         //console.log(json);

         var count = 1;

         for (var i = 0; i < json.length; i++) {
            var image = '<img src=' + json[i].image_url + ' width="100", height="100">';
            var name = '<a href="' + json[i].url + '" target="_blank">' + json[i].name + '</a>';
            var categories = json[i].categories;
            var categorieslist = [];

            for (var j = 0; j < categories.length; j++) {
               categorieslist[j] = " "+ categories[j].title;
            }

            var price = json[i].price;
            var rating = json[i].rating;

            var address = json[i].location.address1 + " " + json[i].location.city + " " + json[i].location.state + " " + json[i].location.zip_code;
            var phone = json[i].display_phone;
            var position = json[i].coordinates;
            var pos_lati_long = new google.maps.LatLng(position.latitude, position.longitude);
            //console.log(position);
            var str = image + " " + count + ". " + name + " | Categories: " + categorieslist + " | Price: " + price + " | Rating: " + rating + " | Address: " + address + " | Phone: " + phone + "<br>";
            
            //console.log(json[i].name);

            const marker = new AdvancedMarkerElement({
               map: map,
               position: pos_lati_long,
               title: count + ". " + name,
             });

            const infoWindow = new google.maps.InfoWindow({
               content: str
            });

            marker.addListener("click", () => {
               if (curr_info_win) {
                  curr_info_win.close();
               }
               infoWindow.open(map, marker);
               curr_info_win = infoWindow;
            });
           
            markers.push(marker);

            count++;
         }
       }
   };

   xhr.send(null);
}