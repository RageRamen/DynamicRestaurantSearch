<?php
  // Bryan Ho 1001705601
  // Since list of favorites is a database, it just stays there, no delete button.
  // And the ordering of the list is ordered by the database, not the code.

  session_start();

  $API_KEY = 'S-kHGugXcHnZ-G52kPto1QPIDgW1SqrnPCYmTY3wTBwwnEaWFBiqAFpC_T3Eu4X4Y5tvlFGKGio1eqrCnCCnFewIVa-lY0-xMu4ARuOIK4JQrYAvlPFMDM_oNPMaZ3Yx';

  if (!isset($_SESSION["city"])) {
    $_SESSION["city"] = "";
  }

  if (!isset($_SESSION["search"])) {
    $_SESSION["search"] = array();
  }

  if (!isset($_SESSION["favorites"])) {
    $_SESSION["favorites"] = array();
  }

  if (isset($_GET["reset"])) {
    $_SESSION["city"] = "";
    $_SESSION["search"] = array();
    header("Location: yelp.php");
    exit();
  }

  if (isset($_GET["city"]) && isset($_GET["keywords"])) {
    $_SESSION["city"] = $_GET["city"];
    $_SESSION["search"] = array();
    $terms = $_GET["keywords"];
    $city = $_SESSION["city"];
    
    $url = "https://api.yelp.com/v3/businesses/search?location=" . urlencode($city) . "&term=" . urlencode($terms) . "&limit=10";
    $response = yelpAPI($url);
    
    $data = json_decode($response, true);

    foreach ($data["businesses"] as $business) {
      $_SESSION["search"][$business["id"]] = $business;
    }
    
  }

  if (isset($_GET["store"])) {
    $businessID = $_GET["store"];
    if (isset($_SESSION["search"][$businessID]) && !isset($_SESSION["favorites"][$businessID])) {
      $_SESSION["favorites"][$businessID] = $_SESSION["search"][$businessID];
    }
    
    $dbh = new PDO("mysql:host=127.0.0.1:3306;dbname=yelp", "root", "", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    
    $stmt = $dbh->prepare("SELECT * FROM favorites WHERE id = :id");
    $stmt->bindParam(':id', $businessID);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
      $stmt = $dbh->prepare("DELETE FROM favorites WHERE id = :id");
      $stmt->bindParam(':id', $businessID);
      $stmt->execute();
    }

    $dbh->beginTransaction();
    $stmt = $dbh->prepare("INSERT INTO favorites (id, name, image_url, yelp_page_url, 
    categories, price, rating, address, phone) VALUES (:id, :name, :image_url, :yelp_url, :categories, :price, :rating, :address, :phone)");
    $business = $_SESSION["favorites"][$businessID];
    
    $name = $business["name"];
    $image_url = $business["image_url"];
    $yelp_url = $business["url"];
    $categories = "";
    foreach ($business["categories"] as $category) {
      $categories .= $category["title"] . ", ";
    }
    $price = $business["price"] ?? "N/A";
    $rating = $business["rating"] ?? "N/A";
    $address = $business["location"]["address1"] . " " . $business["location"]["city"] . " " . $business["location"]["zip_code"] . " " . $business["location"]["state"] . " " . $business["location"]["country"];
    $phone = $business["display_phone"] ?? "N/A";

    $stmt->bindParam(':id', $businessID);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':image_url', $image_url);
    $stmt->bindParam(':yelp_url', $yelp_url);
    $stmt->bindParam(':categories', $categories);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':rating', $rating);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':phone', $phone);
    $stmt->execute();
    $dbh->commit();

    $dbh = null;
    $stmt = null;

  }
  
  function yelpAPI($url) {
    $curl = curl_init();
    if (FALSE === $curl)
       throw new Exception('Failed to initialize');
    curl_setopt_array($curl, array(
              CURLOPT_URL => $url,
              CURLOPT_RETURNTRANSFER => true, 
              CURLOPT_ENCODING => "", 
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "GET",
              CURLOPT_HTTPHEADER => array(
                  "authorization: Bearer " . $GLOBALS['API_KEY'],
                  "cache-control: no-cache",
              ),
          ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
  }

  $dbh = new PDO("mysql:host=127.0.0.1:3306;dbname=yelp", "root", "", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
  $stmt = $dbh->prepare("SELECT * FROM favorites");
  $stmt->execute();
  $_SESSION["favorites"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
</head>
<body>
  <form method="GET" action="yelp.php">
    <label>City: <input type="text" name="city" value="<?php echo $_SESSION["city"]; ?>"/></label>
    <label>Search Term/Keywords: <input type="text" name="keywords" value=""/></label>
    <input type="submit" value="Find"/>
    <input type="submit" value="Reset" name="reset"/>
  </form>

  <div>
    <table>
      <tr>
        <th>Results: </th>
        <th>Favorite: </th>
      </tr>
      <tr>
        <td>
          <?php 
          //echo $_SESSION["city"];
          $counter = 1;
          foreach ($_SESSION["search"] as $id => $business) {
            echo "<br>";
            echo "<a href='yelp.php?store=" . $business["id"] . "'>" . "<img src=" . $business["image_url"] . ' width="100", height="100">' . "</a>";
            echo $counter++ . ".";
            echo " | " . "<a href=" . $business["url"] . " target='_blank'>" . $business["name"]  . "</a>" . " | ";
            foreach ($business["categories"] as $category) {
              echo $category["title"] . ", ";
              
            }
            echo isset($business["price"]) ? " | " . $business["price"] : " | " . "N/A";
            echo isset($business["rating"]) ? " | " . $business["rating"] : " | " . "N/A";
            echo isset($business["location"]["address1"]) ? " | " . $business["location"]["address1"] : "N/A";
            echo isset($business["location"]["city"]) ? " | " . $business["location"]["city"] : "N/A";
            echo isset($business["location"]["zip_code"]) ? " | " . $business["location"]["zip_code"] : "N/A";
            echo isset($business["location"]["state"]) ? " | " . $business["location"]["state"] : "N/A";
            echo isset($business["location"]["country"]) ? " | " . $business["location"]["country"] : "N/A";
            echo isset($business["phone"]) ? " | " . $business["display_phone"] : "N/A";

          }
          ?>
        </td>

        <td style ="vertical-align: top;">
          <?php
          $counter = 1;
          foreach ($_SESSION["favorites"] as $business) {
            $name = $business["name"];
            $image_url = $business["image_url"];
            $yelp_url = $business["yelp_page_url"];
            $categories = $business["categories"];
            $price = $business["price"];
            $rating = $business["rating"];
            $address = $business["address"];
            $phone = $business["phone"];

            echo "<br>";
            echo "<img src=" . $image_url . ' width="100", height="100">';
            echo $counter++ . ".";
            echo  "<a href=" . $yelp_url . " target='_blank'>" . $name . "</a>" . " | ";
            echo $categories . " | ";
            echo $price . " | ";
            echo $rating . " | ";
            echo $address . " | ";
            echo $phone;
          }
          ?>
        </td>
      </tr>
    </table>
    
    
  </div>
  
</body>
</html>








