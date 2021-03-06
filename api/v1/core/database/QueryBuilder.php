<?php
class QueryBuilder{
  protected $pdo;
  /*
  PHP 7.1+ we can specify the visibility of class constants.
  private const BUS_TABLE_NAME = "bus";
  private const BUSLOCATION_TABLE_NAME = "bus_location";
  private const BUSINROUTE_TABLE_NAME = "bus_in_route";
  private const ROUTE_TABLE_NAME = "route";
  private const STATION_TABLE_NAME = "station";
  private const WAYPOINT_TABLE_NAME = "waypoint";
  */
  const BUS_TABLE_NAME = "bus";
  const BUSLOCATION_TABLE_NAME = "bus_location";
  const BUSINROUTE_TABLE_NAME = "bus_in_route";
  const ROUTE_TABLE_NAME = "route";
  const STATION_TABLE_NAME = "station";
  const WAYPOINT_TABLE_NAME = "waypoint";

  //Constructor
  public function __construct($pdo){
    $this->pdo = $pdo;
  }

  private function Query($querystring){
    try{
      $statement = $this->pdo->prepare($querystring);
      $result = $statement->execute();
      $statement = $statement->fetchAll(PDO::FETCH_OBJ);
    }
    catch(PDOException $e){
      return $e;
    }
    return $statement;
  }

  private function NonFetchQuery($querystring){
    try{
      $statement = $this->pdo->prepare($querystring);
      $result = $statement->execute();
    }
    catch(PDOException $e){
      return $e;
    }
    return $result;
  }

  private function SingleQuery($querystring){
    try{
      $statement = $this->pdo->prepare($querystring);
      $result = $statement->execute();
      $statement = $statement->fetch(PDO::FETCH_OBJ);
    }
    catch(PDOException $e){
      return $e;
    }
    return $statement;
  }

  private function GetRowCount($querystring){
    try{
      $statement = $this->pdo->prepare($querystring);
      $statement->execute();
      $rowCount = $statement->rowCount();
    }
    catch(Exception $e){
      return $e;
    }
    return $rowCount;
  }
  //Define new Database jobs here
  //Below is use for KUBUS Query.

  //Bus Section
  //Mode 1
  public function GetAllBus(){
    $querystring = "SELECT * FROM ".self::BUS_TABLE_NAME;
    $result = $this->Query($querystring);
    return $result; //Query PASS
  }

  //Mode 2
  public function GetBusInRoute($route_id){
    $querystring = "SELECT b.id AS busID, plate, status, details FROM ".self::BUS_TABLE_NAME." b
INNER JOIN ".self::BUSINROUTE_TABLE_NAME." bir ON b.id = bir.bus_id
INNER JOIN ".self::ROUTE_TABLE_NAME." r ON bir.route_id = r.id
WHERE bir.route_id = {$route_id}
ORDER BY b.id";
    $result = $this->Query($querystring);
    return $result; //Query PASS
  }

  //Bus Location
  //Mode 3
  public function GetAllRecentBusLocation(){
    $querystring = "SELECT bir.bus_id, bir.route_id, step, bl.course, r.name as route_name, r.description, b.plate, bl.is_active, bl.latitude, bl.longitude, bl.speed, bl.timestamp, r.color  FROM ".self::BUSINROUTE_TABLE_NAME." bir
INNER JOIN ".self::BUS_TABLE_NAME." b ON b.id = bir.bus_id
INNER JOIN ".self::ROUTE_TABLE_NAME." r ON r.id = bir.route_id
INNER JOIN ".self::BUSLOCATION_TABLE_NAME." bl ON b.id = bl.bus_id
WHERE status = 1 AND timestamp = (SELECT MAX(timestamp) FROM ".self::BUSLOCATION_TABLE_NAME." bl2 WHERE bl.bus_id = bl2.bus_id)
ORDER BY bir.bus_id,bir.route_id";
    $result = $this->Query($querystring);
    return $result; //Query PASS
  }
  //Mode 4
  public function GetRecentBusLocationInRoute($route_id){
    $querystring = "SELECT bir.bus_id, bir.route_id, step, bl.course, r.name as route_name, r.description, b.plate, bl.is_active, bl.latitude, bl.longitude, bl.speed, bl.timestamp, r.color  FROM ".self::BUSINROUTE_TABLE_NAME." bir
INNER JOIN ".self::BUS_TABLE_NAME." b ON b.id = bir.bus_id
INNER JOIN ".self::ROUTE_TABLE_NAME." r ON r.id = bir.route_id
INNER JOIN ".self::BUSLOCATION_TABLE_NAME." bl ON b.id = bl.bus_id
WHERE r.id = {$route_id} AND b.status = 1 AND timestamp = (SELECT MAX(timestamp) FROM ".self::BUSLOCATION_TABLE_NAME." bl2 WHERE bl.bus_id = bl2.bus_id)
ORDER BY bir.bus_id";
    $result = $this->Query($querystring);
    return $result; //Query Pass
  }
  //Mode 5
  public function GetSpecificBusLocation($bus_id){
    $querystring = "SELECT bus_id,step,latitude,longitude,timestamp FROM ".self::BUSLOCATION_TABLE_NAME." bl1 
                    WHERE bus_id = {$bus_id} AND timestamp = (SELECT MAX(timestamp) FROM ".self::BUSLOCATION_TABLE_NAME." bl2 WHERE bl1.bus_id = bl2.bus_id)
                    LIMIT 1";
    $result = $this->SingleQuery($querystring);
    return $result; //Query PASS
  }
  
  //Station
  //Mode 6
  public function GetAllStation(){
    $querystring = "SELECT id AS station_id, name AS station_name, latitude, longitude FROM ".self::STATION_TABLE_NAME;
    $result = $this->Query($querystring);
    return $result; //Query Pass (Mod Route Later)
  }
  
  //Mode 7
  public function GetStationInRoute($route_id){
    $querystring = "SELECT DISTINCT wp.station_id, wp.route_id, r.name as route_name, r.description as route_description, s.name as station_name, s.latitude, s.longitude FROM ".self::WAYPOINT_TABLE_NAME." as wp
    INNER JOIN ".self::ROUTE_TABLE_NAME." as r ON wp.route_id = r.id
    INNER JOIN ".self::STATION_TABLE_NAME." as s ON wp.station_id = s.id
    WHERE route_id = {$route_id} ORDER BY wp.station_id";
    $result = $this->Query($querystring);
    return $result; //Query Pass
  }

  //Route
  //Mode 8
  public function GetAllRouteInfo(){
    $querystring = "SELECT * FROM ".self::ROUTE_TABLE_NAME;
    $result = $this->Query($querystring);
    return $result; //Query Pass
  }
  
  //Mode 9
  public function GetSomeRouteInfoByID($route_id){
    $querystring = "SELECT id, name, description FROM ".self::ROUTE_TABLE_NAME." WHERE id = {$route_id}";
    $result = $this->SingleQuery($querystring);
    return $result; //Query Pass.
  }
  
  //Waypoint
  //Mode 10
  public function GetWaypointInRoute($route_id){
    $querystring = "SELECT step,station_id,wp.route_id,r.name as route_name,r.description as route_description,s.name as station_name,s.latitude,s.longitude,r.color FROM ".self::WAYPOINT_TABLE_NAME." as wp
    INNER JOIN ".self::ROUTE_TABLE_NAME." as r ON wp.route_id = r.id
    INNER JOIN ".self::STATION_TABLE_NAME." as s ON wp.station_id = s.id
    WHERE route_id = {$route_id} ORDER BY step";
    $result = $this->Query($querystring);
    return $result; //PASS
  }
  //Mode 11
  public function GetWaypointAll(){
    $querystring = "SELECT step,station_id,route_id,r.name as route_name,r.description as route_description,s.name as station_name,s.latitude,s.longitude,r.color FROM ".self::WAYPOINT_TABLE_NAME." as wp
    INNER JOIN ".self::ROUTE_TABLE_NAME." as r ON wp.route_id = r.id
    INNER JOIN ".self::STATION_TABLE_NAME." as s ON wp.station_id = s.id
    ORDER BY route_id, step";
    $result = $this->Query($querystring);
    return $result;
  }
  
  //Mode 12
  public function GetRouteInStation($station_id){
      $querystring = "SELECT DISTINCT r.id as route_id, r.name as route_name, r.description as route_description FROM ".self::WAYPOINT_TABLE_NAME." as wp
      INNER JOIN ".self::ROUTE_TABLE_NAME." as r ON wp.route_id = r.id
      INNER JOIN ".self::STATION_TABLE_NAME." as s ON wp.station_id = s.id
      WHERE s.id = {$station_id}
      ORDER by r.id";
      $result = $this->Query($querystring);
      return $result;
    }
    
    //Mode 13
    public function GetRouteAndStationDataForQRCode($station_id){
      $querystring = "SELECT DISTINCT r.id AS route_id, r.name AS route_name, r.description AS route_description, s.id AS station_id, s.name AS station_name, s.latitude, s.longitude 
      FROM ".self::WAYPOINT_TABLE_NAME." AS wp
      INNER JOIN ".self::ROUTE_TABLE_NAME." AS r ON wp.route_id = r.id
      INNER JOIN ".self::STATION_TABLE_NAME." AS s ON wp.station_id = s.id
      WHERE s.id = {$station_id}
      ORDER BY r.id";
      $result = $this->Query($querystring);
      return $result;
    }
    
    //POST: Insert/Update Car Function
    public function UpdateBusData($data){
        $isBusLocationExist = $this->CheckBusLocationExist($data->bus_id);
        if($isBusLocationExist){
          $result = $this->UpdateBusDataQuery($data); 
        }
        else{
          $result = $this->InsertBusDataQuery($data);
        }
        return $result;
    }
    
    private function InsertBusDataQuery($data){
      $querystring = "INSERT INTO ".self::BUSLOCATION_TABLE_NAME." (bus_id,latitude,longitude,step,is_active,course,speed)
      VALUES ({$data->bus_id},{$data->latitude},{$data->longitude},{$data->step},1,{$data->course},{$data->speed})";
      $result = $this->NonFetchQuery($querystring);
      return $result;
    }
    private function UpdateBusDataQuery($data){
      $querystring = "UPDATE ".self::BUSLOCATION_TABLE_NAME." 
      SET latitude = {$data->latitude}, longitude = {$data->longitude}, step = {$data->step}, is_active = 1, course = {$data->course}, speed = {$data->speed}
      WHERE bus_id = {$data->bus_id}";
      $result = $this->NonFetchQuery($querystring);
      return $result;
      
    }
    private function CheckBusLocationExist($bus_id){
      $querystring = "SELECT bus_id FROM ".self::BUSLOCATION_TABLE_NAME."
                      WHERE bus_id = {$bus_id} LIMIT 1";
      $result = $this->GetRowCount($querystring);
      if($result){
        return true;
      }
      else{
        return false;
      }
    }

    //Internal Usage
    public function _FindWaypoint($bus_id){
      $querystring = "SELECT route_id FROM ".self::BUSINROUTE_TABLE_NAME."
                      WHERE bus_id = {$bus_id}";
                      //JOIN busID with routeID then get step from that route
      $routeID = $this->SingleQuery($querystring);
      $waypointsData = $this->_GetWaypointInRouteMinimal($routeID->route_id);
      return $waypointsData;
    }
    private function _GetWaypointInRouteMinimal($route_id){
      //route in query will be removed later because this info is not needed.
      $querystring = "SELECT step, station_id, wp.route_id, s.latitude, s.longitude FROM ".self::WAYPOINT_TABLE_NAME." as wp
      INNER JOIN ".self::ROUTE_TABLE_NAME." as r ON wp.route_id = r.id
      INNER JOIN ".self::STATION_TABLE_NAME." as s ON wp.station_id = s.id
      WHERE route_id = {$route_id} ORDER BY step";
      $result = $this->Query($querystring);
      return $result;
    }
  }
  ?>