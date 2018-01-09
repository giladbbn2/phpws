<?php

class DB
{
	private $qs = array();
	public $db_connections;



	function __construct($db_connections){
		$this->db_connections = $db_connections;
	}

	public function connect($db_conn_name){
		if (array_key_exists($db_conn_name, $this->qs))
			return $this->qs[$db_conn_name];

		if (!array_key_exists($db_conn_name, $this->db_connections))
			throw new Exception("db conn not found");

		$conn = $this->db_connections[$db_conn_name];

		$q = new Queryable($conn["host"], $conn["user"], $conn["pass"], $conn["db_name"], $conn["port"]);

		$this->qs[$db_conn_name] = $q;

		return $q;
	}
}

class Queryable {
    private $conn;
	
	public $is_fetch_assoc = true;




    function __construct($host, $user, $pass, $db_name, $port = 3306) {

		$conn = @mysqli_connect($host, $user, $pass, $db_name, $port);

		@mysqli_set_charset($conn, "utf8");

		$this->conn = $conn;
    }

    public function query($sql, $assocKey = array()){
        $conn = $this->conn;

        $isArray = false;
        if (is_array($sql)){
            $sql = implode(";", $sql);
            $isArray = true;
        }

        $out = array();

        if (mysqli_multi_query($conn, $sql)){
            $sqlId = 0;
            do {

                $list = array();
                if ($result = mysqli_store_result($conn)){

                    // select

					if ($this->is_fetch_assoc){
						if (isset($assocKey[$sqlId]) && $assocKey[$sqlId] != "")
							while ($row = mysqli_fetch_assoc($result))
								$list[$row[$assocKey[$sqlId]]] = $row;
						else
							while ($row = mysqli_fetch_assoc($result))
								$list[] = $row;
					} else {
						while ($row = mysqli_fetch_array($result))
							$list[] = $row;
					}

                    $out[] = $list;
                    //echo "Selected rows = ".mysqli_num_rows($result)."<br><br>";
                    mysqli_free_result($result);

                }
                $sqlId++;

            } while(mysqli_more_results($conn) && mysqli_next_result($conn));
        } else
            throw new Exception(mysqli_error($conn), mysqli_errno($conn));

        if (!$isArray)
            if (is_array($out))
                if (isset($out[0]))
                    $out = $out[0];

        return $out;
    }

	public function q($sql, $assocKey = array()){
		return $this->query($sql, $assocKey);
	}

	public function close()
	{
		@mysqli_close($this->conn);
	}

    public function getErrorNum(){
        return mysqli_errno($this->conn);
    }

    public function getErrorDesc(){
        return mysqli_error($this->conn);
    }

    public function getLastInsertId(){
        return mysqli_insert_id($this->conn);
    }

    public function getLastAffectedRows(){
        return mysqli_affected_rows($this->conn);
    }
}