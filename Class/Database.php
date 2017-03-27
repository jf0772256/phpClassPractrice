<?php
//this is a class that will open, close db connections and other actions that should make the process simpler.
/**
 *
 */
class DatabaseClass
{
  private $dbhost;
  private $dbname;
  private $dbusername;
  private $dbuserpassword;
  private $dbtableprefix;
  private $dbport;
  protected $dbC;
  public function __construct($ndbhost = "localhost", $ndbname = "", $ndbusername = "", $ndbuserpassword = "", $ndbtableprefix = " ", $ndbport = 3600)
  {
    $this->dbhost = $ndbhost; // Default = localhost
    $this->dbname = $ndbname; // Default = None
    $this->dbusername = $ndbusername; //Default = None
    $this->dbuserpassword = $ndbuserpassword; // Default = None
    $this->dbtableprefix = $ndbtableprefix; //Default = None sets the prefix before the table name so that it can be used with the same db with different tables.
    $this->dbport = $ndbport; // Default  = 3600
    // starting the connection
    @ $this->dbC = new mysqli($this->dbhost, $this->dbusername, $this->dbuserpassword, $this->dbname);//, $this->dbport);
    if ($this->dbC->connect_error) {
      //catch error connecting.
      die("There was a connection error while attempting to connect to the database " . $this->dbname . " on " . $this->dbhost . ":" . $this->dbport . ". The following is the error that we received back: <strong>" . $this->dbC->connect_errno . ": " . $this->dbC->connect_error . "</strong>\n Please correct this issue, if you need assistance see your database or IT administrator.");
    }else{
      //echo "Connected to " . $this->dbname . " on " . $this->dbhost . ":" . $this->dbport;
    }
  }
  public function __destruct(){
    @ $this->dbC->close();
  }
  // get properties All but the prefix are unchangable until a new connection is made.
  public function getHost(){
    return $this->dbhost;
  }
  public function getDatabase(){
    return $this->dbname;
  }
  public function getUsername(){
    return $this->dbusername;
  }
  public function getPassword(){
    return $this->dbuserpassword;
  }
  public function getPort(){
    return $this->dbport;
  }
  public function getDBPrefix(){
    return $this->dbtableprefix;
  }
  // the one setter property
  public function setDBPrefix($prefixValue){
    $this->dbtableprefix = $prefixValue;
  }

  //now for other workhorses
  public function checkConnection(){
    return mysqli_ping($this->dbC);
  }

  public function create_newTable($tableName, $tableParams){
    //Table name expects a valid string, Table params requires an array, minus commas of strings for each column in the table.
    if (!is_array($tableParams)){
      throw new ErrorException("Expected array of strings. Review documentation for further information.");
      exit();
    }else{
      $tableName = $this->dbtableprefix . $tableName;
      $query="CREATE TABLE IF NOT EXISTS $tableName (";
      foreach ($tableParams as $params) {
        $query = $query . $params . ", ";
      }
      //now to crop the last comma off
      $lenofquery = strlen($query);
      echo var_dump($lenofquery);
      $query = substr($query, 0, ($lenofquery - 2));
      $query = $query . ")";
      echo var_dump(strlen($query));
      echo var_dump($query);
    }
  }
  
  public function dropTableByName($tableName){
    $tableName = $this->dbtableprefix . $tableName;
    $query = "DROP TABLE IF EXISTS $tableName";
    $stmnt = $this->dbC->prepare($query);
    $result = $stmnt->execute();
    if (!$result){
      return false;
    }else{
      return true;
    }
  }

  public function renameTableName($oldTableName, $newTableName){
    //assumes that you didnt include the prefix on the newTableName, You will need to have it for oldTableName
    // uses a simple request to change teh oldTableName to newTableName and prepend the prefix, This can be used individually
    // we will work on a renaming call for if you change the prefix.
    $table_exists = check_preexisting_tables($oldTableName);
    if ($table_exists) {
      $newTableName = $this->dbtableprefix . $newTableName;
      $query = "RENAME TABLE $oldTableName TO $newTableName";
      $stmnt = $this->dbC->prepare($query);
      $result = $stmnt->execute();
      if ($result) {
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }

  protected function check_preexisting_tables($tableNameVar){
    //query the database and if the table is there then retun true.
    //if the tables are not created then return false.
    $database = $this->dbname;
    $query = "SELECT COUNT(*) AS Count FROM information_schema.TABLES WHERE TABLE_Schema = '$database' AND TABLE_NAME=?";
    $stmnt = $db -> prepare($query);
    $stmnt -> bind_param("s",$tableNameVar);
    if (!$stmnt -> execute()){
      $error_message = "Something went wrong and the process has stopped, contact your support contact for further assistance.";
      include("error/db_error.php");
      exit();
    }
    $tblresult = $stmnt -> get_result();
    $tblCount = $tblresult -> fetch_assoc(); //non associated array , easier to get to result for this purpose.
    //if statements to return either true or false...
    if ($tblCount["Count"] === 1){
      //table exists and the database is dirty...
      return true;
    }elseif ($tblCount["Count"] === 0){
      //table does not exist and the database if clean...
      return false;
    }else{
      //something has gone wrong... horribly wrong...
      $error_message = "An unknown error has occured and we have stopped the script from running further, contact us through your POC.";
      include("error/db_error.php");
      exit();
    }
  }
}

?>